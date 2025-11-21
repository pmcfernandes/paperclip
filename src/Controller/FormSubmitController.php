<?php
namespace App\Controller;

use App\Entity\FormData;
use App\Repository\FormRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;
use App\Service\PrepareWebhook;

class FormSubmitController extends AbstractController
{
    private ?MailerInterface $mailer;

    public function __construct(?MailerInterface $mailer = null, ?LoggerInterface $logger = null)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    #[Route('/forms/{slug}/submit', name: 'form_submit', methods: ['POST'])]
    public function submit(string $slug, Request $request, FormRepository $forms, EntityManagerInterface $em, PrepareWebhook $webhook): Response {
        $form = $forms->findOneBy(['slug' => $slug]);
        if (!$form) {
            return new JsonResponse(['error' => 'Form not found'], Response::HTTP_NOT_FOUND);
        }

        // Persist regular form fields (excluding _csrf_token, form_slug)
        $data = $request->request->all();
        unset($data['_csrf_token']);
        unset($data['form_slug']);

        // Do this inside a transaction with a SELECT ... FOR UPDATE to reduce race conditions.
        $conn = $em->getConnection();
        $startedTransaction = false;
        try {
            $conn->beginTransaction();
            $startedTransaction = true;
            $max = $conn->executeQuery('SELECT MAX(submit_id) +1 AS maxid FROM `form_data` FOR UPDATE')->fetchOne();
            if ($max === null) {
                $submitId = 1;
            } else {
                $submitId = (int)$max;
            }
        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            throw $e;
        }

        foreach ($data as $name => $value) {
            if ($name === '') {
                continue;
            }

            // simple validation rules: NotBlank for `name`, Email for fields containing 'email'
            if (strtolower($name) === 'name') {
                if (is_array($value)) {
                    $v = implode('', $value);
                } else {
                    $v = (string) $value;
                }
                if (trim($v) === '') {
                    return new JsonResponse(['error' => 'Field "name" cannot be blank'], Response::HTTP_BAD_REQUEST);
                }
            }

            if (stripos($name, 'email') !== false) {
                if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return new JsonResponse(['error' => 'Field "' . $name . '" must be a valid email'], Response::HTTP_BAD_REQUEST);
                }
            }

            if (is_array($value)) {
                $value = implode(';', $value);
            }

            $fd = new FormData();
            $fd->setForm($form);
            $fd->setName((string)$name);
            $fd->setSubmitId($submitId);
            $fd->setValue($value !== null ? (string)$value : null);
            $fd->setWhen(new \DateTime());
            $em->persist($fd);
        }

        $em->flush();

        // commit the transaction if we started one
        if ($startedTransaction) {
            try {
                $conn->commit();
            } catch (\Throwable $e) {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }

                 if($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                      'success' => false,
                      'error' => [
                        'reason' => $e->getMessage()
                      ],
                      'redirectUrl' => $form->getUrlOnError() ?? ''
                    ], Response::HTTP_BAD_REQUEST);
                 } else {
                    $urlOnError = $form->getUrlOnError();
                    if (!empty($urlOnError)) {
                      return new RedirectResponse($urlOnError);
                    } else {
                      throw $e;
                    }
                 }
            }
        }

        // Prepare and send webhook if configured
        try {
            $webhook->send($request);
        } catch (\Throwable $e) {
            $this->logger->error('Webhook sending failed: ' . $e->getMessage());
        }


        // If configured, send an email on submit
        if ($form->isEmailOnSubmit()) {
            $sendTo = $form->getSendOnSubmit() ?? null;
            if (!empty($sendTo)) {
                // build a simple text body from submitted fields
                $lines = [];
                $lines[] = 'Form: ' . $form->getName();
                $lines[] = 'Submitted: ' . (new \DateTime())->format('Y-m-d H:i:s');
                $lines[] = '';
                foreach ($data as $k => $v) {
                    if (is_array($v)) {
                        $v = implode(';', $v);
                    }
                    $lines[] = $k . ': ' . (string)$v;
                }
                $body = implode("\n", $lines);
                $subject = 'New submission: ' . $form->getName();

                // support comma-separated recipient list
                $recipients = array_map('trim', explode(',', $sendTo));
                $sent = false;

                if ($this->mailer !== null && class_exists(MimeEmail::class)) {
                    try {
                        $email = new MimeEmail();

                        // validate from email format
                        $from = $_ENV['MAIL_FROM'] ?? getenv('MAIL_FROM') ?: null;
                        if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
                          $email->from($from);
                        } else {
                          throw new \RuntimeException('Invalid MAIL_FROM address configured.');
                        }

                        foreach ($recipients as $r) {
                            if (filter_var($r, FILTER_VALIDATE_EMAIL)) {
                                $email->to($r);
                            }
                        }
                        $email->subject($subject);
                        $email->text($body);
                        $this->mailer->send($email);
                        $sent = true;
                    } catch (\Throwable $e) {
                        $this->logger->error('Email sending failed: ' . $e->getMessage());
                        $sent = false;
                    }
                }
            }
        }

        if($request->isXmlHttpRequest()) {
            return new JsonResponse([
              'success' => true,
              'redirectUrl' => $form->getUrlOnOk() ?? ''
            ], Response::HTTP_CREATED);
        } else {
            $url = $form->getUrlOnOk();
             return new RedirectResponse(!empty($url) ? $url : '/');
        }
    }
}
