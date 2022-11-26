<?php
require_once ('../vendor/autoload.php');

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

Requests::register_autoloader();

global $config;

/**
 * Load config from settings.json
 *
 * @return void
 */
function load_config() {
    global $config;
    $cfgConn = $params['connection'];
    $cfgEmail = $params['mail'];

    $params = json_decode(file_get_contents('../settings.json'), true);
    $config['driver'] = $cfgConn['type'];
    $config['host'] = $cfgConn['host'];
    $config['username'] = $cfgConn['username'];
    $config['password'] = $cfgConn['password'];
    $config['database'] = $cfgConn['database'];
    $config['mail_server'] = $cfgEmail['mailserver'];
    $config['mail_email'] = $cfgEmail['email'];
    $config['mail_user'] = $cfgEmail['username'];
    $config['mail_password'] = $cfgEmail['password'];
}

/**
 * Connect do MySQL server and database
 *
 */
function connect_to_db() {
    global $config;
    $capsule = new Capsule;

    $capsule->addConnection([
        'driver'    => $config['driver'],
        'host'      => $config['host'],
        'database'  => $config['database'],
        'username'  => $config['username'],
        'password'  => $config['password'],
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
    ]);

    $capsule->setAsGlobal();

    // Boot Eloquent in case models or some capsule features are used
    if (method_exists($capsule, 'bootEloquent')) {
        $capsule->bootEloquent();
    }
}

/**
 * Get site id and validate if domain is same as sent by $_SERVER
 *
 * @param string $key
 * @return int
 */
function get_siteid_by_key($key) {
    $results = Capsule::select('SELECT id, domain FROM sites WHERE `key` = ? LIMIT 1', [$key]);

    if (count($results) == 1) {
        if (domain_validate($results[0]->domain)) {
            return $results[0]->id;
        }
    }

    return 0;
}

/**
 * Get form id from site id value
 *
 * @param int $site_id
 * @param string $slug
 * @return int
 */
function get_formid_by_siteid($site_id, $slug) {
    $results = Capsule::select('SELECT id FROM forms WHERE site_id = ? AND `slug` = ? LIMIT 1', [$site_id, $slug]);

    if (count($results) == 1) {
        return $results[0]->id;
    } else {
        return 0;
    }
}

/**
 * Insert form data received by $_POST to form_data table
 *
 * @param int $form_id
 * @param string $name
 * @param string $value
 * @return void
 */
function save_form_data($form_id, $name, $value) {
    $results = Capsule::insert('INSERT INTO form_data (`form_id`, `name`, `value`) VALUES (?, ?, ?)', [$form_id, $name, $value]);
}

/**
 * Get urls from database
 *
 * @param string $key
 * @param string $form_slug
 * @param string $ok_url
 * @param string $error_url
 * @return void
 */
function set_redirect_urls($key, $form_slug, &$ok_url, &$error_url) {
    $ok_url = '';
    $error_url = '';

    $results = Capsule::select('SELECT urlOnOK, urlOnError FROM forms INNER JOIN sites ON sites.id = forms.site_id WHERE sites.key = ? AND forms.slug = ?', [$key, $form_slug]);

    if (count($results) == 1) {
        if (!empty($results[0]->urlOnOK)) {
            $ok_url = $results[0]->urlOnOK;
        }

        if (!empty($results[0]->urlOnError)) {
            // assign to the referenced variable
            $error_url = $results[0]->urlOnError;
        }
    }
}

/**
 * Parse $_POST form values and save in database
 *
 * @param int $form_id
 * @return void
 */
function prepare_and_save_form_data($form_id) {
    foreach ($_POST as $name => $value) {
        $data = '';
        // If the field is an array (checkboxes, multi-select), save the values
        if (is_array($value)) {
            foreach ($value as $name2 => $value2) { // TODO: need validation?
                // append the value, not the key
                $data .= $value2 . ';';
            }
            // trim trailing semicolon
            $data = rtrim($data, ';');
        } else {
            $data = $value;
        }

        save_form_data($form_id, $name, $data);
    }
}

/**
 * Validate if $_SERVER is same domain passed as parameter
 *
 * @param string $domain
 * @return bool
 */
function domain_validate($domain) {
    if (in_array($_SERVER['SERVER_NAME'], explode(',', $domain))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Redirect to success or error page
 *
 * @param string $url
 * @param string $reason
 * @return void
 */
function redirect_url($url, $reason = '') {
    header("Location: " . str_replace('{reason}', $reason, $url));
    exit();
}

/**
 * Get field value from $_POST request
 *
 * @param [type] $name
 * @return void
 */
function get_field($name) {
    foreach ($_POST as $name2 => $value) {
        if ($name === $name2) {
            return $value;
        }
    }

    return '';
}

/**
 * Build a message body to send in email HTML format
 *
 * @return void
 */
function build_mail_body() {
    $body = '';

    foreach ($_POST as $name => $value) {
        $data = '';

        // Skip subject field as it's used in email subject
        if (!in_array($name, ['subject'])) {
            if (is_array($value)) {
                foreach ($value as $name2 => $value2) {
                    // append actual values, not the keys
                    $data .= $value2 . ';';
                }
                $data = rtrim($data, ';');
            } else {
                $data = $value;
            }

            $body .= sprintf('%s: %s <br/>', htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8'));
        }
    }

    return $body;
}

/**
 * Send email to owner
 *
 * @param int $site_id
 * @param int $form_id
 * @return void
 */
function send_email($site_id, $form_id) {
    $form = Capsule::select('SELECT id, emailOnSubmit, sendOnSubmit FROM forms WHERE site_id = ? AND id = ? LIMIT 1', [$site_id, $form_id]);

    global $config;

    // Ensure we have a form row
    if (!is_array($form) || count($form) != 1) {
        return false;
    }

    if ($form[0]->emailOnSubmit === 1) {
        if (!empty($form[0]->sendOnSubmit)) {
            $addresses = explode(',', $form[0]->sendOnSubmit);
        } else {
            $addresses = array($config['mail_email']);
        }

        $mail = new PHPMailer(true);

        try {
            $replyTo = get_field('email');
            $subject = get_field('subject');

            if ($subject === '') {
                $subject = sprintf('You have a new message from %s', $replyTo);
            }

            $mail->isSMTP();
            $mail->Host       = $config['mail_server'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['mail_user'];
            $mail->Password   = $config['mail_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Use configured from address if available, otherwise fall back to SMTP username
            $fromEmail = !empty($config['mail_email']) ? $config['mail_email'] : $mail->Username;
            $mail->setFrom($fromEmail);

            if ($replyTo !== '') {
                $mail->addReplyTo($replyTo);
            }

            foreach ($addresses as $addr) {
                $mail->addAddress($addr);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = build_mail_body();

            $mail->send();
        } catch (Exception $e) {
            prepare_output(false, 'send_mail_failed');
        }

        return true;
    }

    return false;
}

/**
 * Create webhook string to send in request
 *
 * @param int $site_id
 * @param int $form_id
 * @param string $token
 * @return void
 */
function create_webhook($site_id, $form_id, $token) {
    $site = Capsule::select('SELECT id, `slug`, `name`, `domain`, `key` FROM sites WHERE id = ? LIMIT 1', [$site_id]);
    $form = Capsule::select('SELECT id, `slug`, `name` FROM forms WHERE site_id = ? AND id = ? LIMIT 1', [$site_id, $form_id]);
    $form_data = Capsule::select('SELECT `name`, `value`, `when` FROM form_data WHERE form_id = ?', [$form_id]);

    $data = array();

    if (is_array($form_data)) {
        foreach ($form_data as $item) {
            $data[$item->name] = $item->value;
        }
    }

    // Validate we have site and form rows
    if (!is_array($site) || count($site) != 1 || !is_array($form) || count($form) != 1) {
        return null;
    }

    $hook = array(
        'action' => 'newItem',
        'isTest' => false,
        'token'  => $token,
        'site'   => array(
            'eid'    => $site[0]->key,
            'slug'   => $site[0]->slug,
            'name'   => $site[0]->name,
            'domain' => $site[0]->domain
        ),
        'form'  => array(
            'slug'        => $form[0]->slug,
            'displayName' => $form[0]->name,
            'items'       => array(
                'when'    => (isset($form_data[0]) ? $form_data[0]->when : null),
                'payload' => $data
            )
        )
    );

    return $hook;
}

/**
 * Create Webhook request and send.
 *
 * @param int $site_id
 * @param int $form_id
 * @return void
 */
function submit_webhook($site_id, $form_id) {
    $results = Capsule::select('SELECT webhook_url, webhook_token FROM sites WHERE id = ? LIMIT 1', [$site_id]);

    if (count($results) == 1) {
        $token = $results[0]->webhook_token;

        if ($results[0]->webhook_url !== NULL && $token !== NULL) {
            if (filter_var($results[0]->webhook_url, FILTER_VALIDATE_URL)) {
                $hook = create_webhook($site_id, $form_id, $token);
                $response = Requests::post($results[0]->webhook_url, array(), $hook);

                if ($response->success) {
                    return true;
                } else {
                    prepare_output(false, 'webhook_failed');
                }
            }
        }
    }

    return false;
}

/**
 * Check if is is_ajax
 */

function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}

/**
 * Prepare output can be three types: AJAX, a redirect or just a text response
 * depending of request type or database configuration
 *
 * @param boolean $isok
 * @param string $error_reason
 * @return void
 */
function prepare_output($isok = true, $error_reason = '') {
    $ok_url = '';
    $error_url = '';
    set_redirect_urls($_GET['key'], $_GET['form'], $ok_url, $error_url);

    if (!filter_var($ok_url, FILTER_VALIDATE_URL)) {
        $ok_url = '';
    }

    if (!filter_var($error_url, FILTER_VALIDATE_URL)) {
        $error_url = '';
    }

    if (is_ajax()) {
        if ($isok === true) {
            echo json_encode(array(
                'isSuccess' => true,
                'redirectUrl' => $ok_url
            ));
        } else {
            echo json_encode(array(
                'isSuccess' => false,
                'redirectUrl' => $error_url,
                'error' => array(
                    'reason' => $error_reason
                )
            ));
        }
    } else {
        if ($isok === true) {
            if ($ok_url !== '') {
                redirect_url($ok_url);
            }
        } else {
            if ($error_url == '') {
                switch ($error_reason) {
                    case 'key_is_invalid':
                        $text = sprintf('The site key "%s" is not available.', $_GET['key']);
                        break;
                    case 'form_slug_is_invalid':
                        $text = sprintf('The form slug "%s" is not available.', $_GET['form']);
                        break;
                    default:
                        $text = sprintf('An error occurred with code "%s".', $error_reason);
                }

                die($text);
            } else {
                redirect_url($error_url, $error_reason);
            }
        }
    }

    exit();
}

/**
 * Process form data from request
 *
 * @return void
 */
function processForm() {
    connect_to_db();

    if (!isset($_GET['key'])) {
        die('The site key is not defined.');
    } else {
        if (!isset($_GET['form'])) {
            die('The form slug is not defined.');
        } else {
            $site_id = get_siteid_by_key($_GET['key']);

            if ($site_id == 0) {
                prepare_output(false, 'key_is_invalid');
            } else {
                $form_id = get_formid_by_siteid($site_id, $_GET['form']);

                if ($form_id == 0) {
                    prepare_output(false, 'form_slug_is_invalid');
                } else {
                    prepare_and_save_form_data($form_id);

                    send_email($site_id, $form_id);
                    submit_webhook($site_id, $form_id);
                }
            }
        }
    }

    prepare_output(); // OK
}

load_config();
processForm();
