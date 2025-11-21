<?php
namespace App\Controller;

use App\Entity\Site;
use App\Service\ValidateUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\UserRepository;
use Ramsey\Uuid\Uuid;

#[Route('/api/sites')]
class SiteController extends AbstractController
{

    #[Route('', name: 'site_create', methods: ['POST'])]
    public function create(Request $request, ValidateUserService $validateUserService, EntityManagerInterface $em, ValidatorInterface $validator, UserRepository $users): JsonResponse
    {
        $user = $validateUserService->getUserFromHeader($request, $users);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Ensure a unique slug exists: generate server-side if not provided
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $slug = Uuid::uuid4()->toString();
        $siteKey = Uuid::uuid4()->toString();
        $name = $data['name'] ?? null;
        $domain = $data['domain'] ?? null;

        if (!$name) {
            return new JsonResponse(['error' => 'Name is required.'], Response::HTTP_BAD_REQUEST);
        }

         if (!$domain) {
            return new JsonResponse(['error' => 'Domain is required.'], Response::HTTP_BAD_REQUEST);
        }

        $site = new Site();
        $site->setSlug($slug);
        $site->setName($name);
        $site->setSiteKey($siteKey);
        $site->setDomain($domain);
        if (isset($data['webhook_url'])) { $site->setWebhookUrl($data['webhook_url']); }
        if (isset($data['webhook_token'])) { $site->setWebhookToken($data['webhook_token']); }
        $site->setUser($user);

        $errors = $validator->validate($site);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $err) { $messages[] = $err->getMessage(); }
            return new JsonResponse(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($site);
        $em->flush();

        return new JsonResponse(['id' => $site->getId()], Response::HTTP_CREATED);
    }

    #[Route('', name: 'site_list', methods: ['GET'])]
    public function list(Request $request, ValidateUserService $validateUserService, EntityManagerInterface $em, UserRepository $users): JsonResponse
    {
        $user = $validateUserService->getUserFromHeader($request, $users);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $slug = $request->query->get('slug');
        $domain = $request->query->get('domain');

        $repo = $em->getRepository(Site::class);
        if ($slug) {
            $sites = $repo->findBy(['slug' => $slug, 'user' => $user]);
        } elseif ($domain) {
            $sites = $repo->findBy(['domain' => $domain, 'user' => $user]);
        } else {
            $sites = $repo->findBy(['user' => $user]);
        }

        $out = [];
        foreach ($sites as $s) {
            $out[] = [
                'id' => $s->getId(),
                'slug' => $s->getSlug(),
                'name' => $s->getName(),
                'domain' => $s->getDomain(),
            ];
        }

        return new JsonResponse($out);
    }

    #[Route('/{id}', name: 'site_update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request, ValidateUserService $validateUserService, EntityManagerInterface $em, ValidatorInterface $validator,  UserRepository $users): JsonResponse
    {
        $user = $validateUserService->getUserFromHeader($request, $users);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $site = $em->getRepository(Site::class)->find($id);
        if (!$site) {
            return new JsonResponse(['error' => 'Site not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        // Slug and SiteKey are immutable; do not allow updates
        if (array_key_exists('name', $data)) { $site->setName($data['name']); }
        if (array_key_exists('domain', $data)) { $site->setDomain($data['domain']); }
        if (array_key_exists('webhook_url', $data)) { $site->setWebhookUrl($data['webhook_url']); }
        if (array_key_exists('webhook_token', $data)) { $site->setWebhookToken($data['webhook_token']); }

        $errors = $validator->validate($site);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $err) { $messages[] = $err->getMessage(); }
            return new JsonResponse(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return new JsonResponse(['id' => $site->getId()]);
    }

    #[Route('/{id}', name: 'site_delete', methods: ['DELETE'])]
    public function delete(string $id, Request $request, ValidateUserService $validateUserService, EntityManagerInterface $em,  UserRepository $users): JsonResponse
    {
        $user = $validateUserService->getUserFromHeader($request, $users);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $site = $em->getRepository(Site::class)->find($id);
        if (!$site) {
            return new JsonResponse(['error' => 'Site not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($site);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
