# paperclip

Paperclip is a small PHP library that receives form submissions (regular POST or AJAX) and:

- Validates the request against a configured site key and domain
- Saves submitted form fields into a database (`form_data` table)
- Optionally emails the submission to one or more recipients using PHPMailer
- Optionally POST a webhook payload to a configured URL

The project includes a minimal demo under `src/demo/` and a single endpoint implementation at `src/pageclip.php`.

## Main files

- `src/pageclip.php` — core request handling: config loading, DB connection (Illuminate Database Capsule), saving form data, sending email (PHPMailer), and submitting webhooks.
- `settings.json` — local configuration used by `pageclip.php` (database + SMTP settings).
- `src/demo/index.html` — example HTML form and JavaScript usage showing how to call the endpoint.

## Quick start

1. Install dependencies (the repo already includes `vendor/`, but if you need to reinstall using Composer):

	composer install

2. Configure database and mail in `settings.json` (example values are present). Required keys:

	- connection.type (e.g. `mysql`)
	- connection.host
	- connection.username
	- connection.password
	- connection.database
	- mail.mailserver (SMTP host)
	- mail.email (default 'from' address)
	- mail.username (SMTP user)
	- mail.password (SMTP password)

3. Create the necessary database schema. The code expects tables like `sites`, `forms`, and `form_data`. A minimal schema should include columns referenced from `pageclip.php` such as:

	- `sites` with columns: `id`, `key`, `domain`, `webhook_url`, `webhook_token`, `slug`, `name`, etc.
	- `forms` with columns: `id`, `site_id`, `slug`, `name`, `emailOnSubmit`, `sendOnSubmit`, `urlOnOK`, `urlOnError`, etc.
	- `form_data` with columns: `form_id`, `name`, `value`, `when` (timestamp) — used to store submitted values.

	(This project does not include migrations; create the tables manually or adapt to your preferred migration tool.)

4. Serve the project from a web-accessible folder (for example using PHP built-in server for testing):

	php -S localhost:8000 -t src

	Then open the demo at http://localhost:8000/demo/index.html (adjust paths if you serve from a different document root).

## How it works

- The demo form posts to `../pageclip.php?key=aaa&form=bbb`. The `key` identifies the site and `form` identifies the form slug in the database.
- `pageclip.php` loads `settings.json` and uses Illuminate Database Capsule to connect to the configured DB.
- It validates the site key and form slug, then saves each POSTed field to `form_data`.
- If the form is configured to send email (`emailOnSubmit`), PHPMailer is used to send an HTML email with the form fields. The `subject` field (if present) will be used as the email subject. The `email` field (if present) will be used as the reply-to.
- If the site has a webhook configured (`webhook_url` and `webhook_token`), a webhook payload will be built and POSTed to that URL.
- The endpoint supports both AJAX and normal form submissions. For AJAX requests (checks `X-Requested-With: XMLHttpRequest`), it returns JSON with `isSuccess` and an optional `redirectUrl`. For normal requests it redirects to configured `urlOnOK` / `urlOnError` or returns a textual error.

## Demo usage (copy/paste)

Place the demo files under your web root and open `src/demo/index.html`. The demo contains a small script that uses `Pageclip.form(...).send('aaa', 'bbb')` to submit the form to `pageclip.php?key=aaa&form=bbb`.

Important: replace `aaa` and `bbb` with the actual `sites.key` and `forms.slug` values from your database.

## Security & notes

- Ensure `settings.json` is not publicly accessible from the web. Keep it outside the document root or protect it with server rules.
- The demo uses example keys; production must use secure, unique site keys and webhook tokens.
- CSRF: This project trusts that form submissions come from allowed domains stored in `sites.domain` and validates against `$_SERVER['SERVER_NAME']`. Confirm this matches your deployment environment. You may want to add stronger CSRF protections depending on use.
- Input handling: submitted values are stored as-is. If you display stored values later, escape them appropriately to avoid XSS.
- Email credentials are stored in `settings.json` in plaintext. Use environment variables or a secure vault for production.

## Troubleshooting

- If emails fail to send, check SMTP config in `settings.json` and PHPMailer exceptions. The code will prepare an error output with reason `send_mail_failed` for AJAX calls.
- If webhooks fail, check the webhook URL and token stored in `sites` table; the code will return `webhook_failed` when sending fails.

