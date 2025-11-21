<?php
namespace App\Service;

use App\Entity\Site;
use App\Entity\Form;
use App\Entity\FormData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PrepareWebhook
{

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function createPayload(Request $request): string
    {
        $dateTime = new \DateTime();

        $form = $forms->findOneBy(['slug' => $data['form_slug']]);
        $site = $form->getSite();

        $data = $request->request->all();
        unset($data['_csrf_token']);
        unset($data['form_slug']);

        return json_encode([
          'action' => 'newSubmission',
          'isTest' => false,
          'token' => '',
          'site' => [
              'eid' => $site->getSiteKey(),
              'slug' => $site->getSlug(),
              'name' => $site->getName(),
              'domain' => $site->getDomain(),
          ],
          'form' => [
              'slug' => $form->getSlug(),
              'displayName' => $form->getName(),
              'items' => [
                'when' => $dateTime->format('c'),
                'fields' => $data,
              ]
          ],
        ]);
    }

     public function send(Request $request): string|null
     {
        $token = $site->getWebhookToken();
        $url = $site->getWebhookUrl();

        if (empty($url)) {
            return null;
        }

        // Implement the actual sending logic here
        $hook = $this->create($request);

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
             ],
            'body' => $hook
        ]);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();

        if ($statusCode !== 200) {
            throw new \Exception("Webhook failed with status code $statusCode: $content");
        }

        return $content;
     }
}
