<?php
namespace App\Controller;

use App\Entity\Form;
use App\Entity\Site;
use App\Service\ValidateUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ramsey\Uuid\Uuid;
use App\Repository\UserRepository;

#[Route('/api/forms')]
class FormController extends AbstractController
{

    #[Route('', name: 'form_create', methods: ['POST'])]
    public function create(Request $request, ValidateUserService $validateUserService, EntityManagerInterface $em, ValidatorInterface $validator, UserRepository $users): JsonResponse
    {
        $user = $validateUserService->getUserFromHeader($request, $users);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

         // Ensure a unique slug exists: generate server-side if not provided
        $slug = Uuid::uuid4()->toString();
        $name = $data['name'] ?? null;

        if (!$name) {
            return new JsonResponse(['error' => 'Name is required.'], Response::HTTP_BAD_REQUEST);
        }

        // Resolve site by id or slug
        $site = null;
        if (!empty($data['site_id'])) {
            $site = $em->getRepository(Site::class)->find((int)$data['site_id']);
        } elseif (!empty($data['site_slug'])) {
            $site = $em->getRepository(Site::class)->findOneBy(['slug' => $data['site_slug']]);
        } elseif (!empty($data['site'])) {
            $site = is_numeric($data['site']) ? $em->getRepository(Site::class)->find((int)$data['site']) : null;
        }

        if (!$site) {
            return new JsonResponse(['error' => 'Site not found. Provide site_id or site_slug.'], Response::HTTP_BAD_REQUEST);
        }

        $form = new Form();
        $form->setSite($site);
        $form->setSlug($slug);
        $form->setName($name);
        if (array_key_exists('emailOnSubmit', $data)) { $form->setEmailOnSubmit((bool)$data['emailOnSubmit']); }
        if (array_key_exists('sendOnSubmit', $data)) { $form->setSendOnSubmit((bool)$data['sendOnSubmit']); }
        if (array_key_exists('urlOnOk', $data)) { $form->setUrlOnOk((bool)$data['urlOnOk']); }
        if (array_key_exists('urlOnError', $data)) { $form->setUrlOnError((bool)$data['urlOnError']); }

        $errors = $validator->validate($form);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $err) { $messages[] = $err->getMessage(); }
            return new JsonResponse(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($form);
        $em->flush();

        return new JsonResponse(['id' => $form->getId()], Response::HTTP_CREATED);
    }

    #[Route('', name: 'form_list', methods: ['GET'])]
    public function list(Request $request, ValidateUserService $validateUserService, EntityManagerInterface $em, UserRepository $users): JsonResponse
    {
        $user = $validateUserService->getUserFromHeader($request, $users);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $siteId = $request->query->getInt('site_id') ?: null;
        $siteSlug = $request->query->get('site_slug');
        $repo = $em->getRepository(Form::class);

        if ($siteId) {
            $forms = $repo->findBy(['site' => $siteId]);
        } elseif ($siteSlug) {
            // resolve site id then find
            $site = $em->getRepository(Site::class)->findOneBy(['slug' => $siteSlug, 'user' => $user]);
            if (!$site) {
                return new JsonResponse(['error' => 'Site not found'], Response::HTTP_BAD_REQUEST);
            }
            $forms = $repo->findBy(['site' => $site->getId()]);
        } else {
            $forms = $repo->findAll();
        }

        $out = [];
        foreach ($forms as $f) {
            $out[] = [
                'id' => $f->getId(),
                'slug' => $f->getSlug(),
                'name' => $f->getName(),
                'site_id' => $f->getSite() ? $f->getSite()->getId() : null,
            ];
        }

        return new JsonResponse($out);
    }

    #[Route('/{id}', name: 'form_update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request, ValidateUserService $validateUserService, EntityManagerInterface $em, ValidatorInterface $validator, UserRepository $users): JsonResponse
    {
        $user = $validateUserService->getUserFromHeader($request, $users);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $form = $em->getRepository(Form::class)->find($id);
        if (!$form) {
            return new JsonResponse(['error' => 'Form not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        // Slug and site_id is immutable; do not allow updates
        if (array_key_exists('name', $data)) { $form->setName($data['name']); }
        if (array_key_exists('emailOnSubmit', $data)) { $form->setEmailOnSubmit((bool)$data['emailOnSubmit']); }
        if (array_key_exists('sendOnSubmit', $data)) { $form->setSendOnSubmit((bool)$data['sendOnSubmit']); }
        if (array_key_exists('urlOnOk', $data)) { $form->setUrlOnOk($data['urlOnOk']); }
        if (array_key_exists('urlOnError', $data)) { $form->setUrlOnError($data['urlOnError']); }

        $errors = $validator->validate($form);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $err) {
              $messages[] = $err->getMessage();
            }

            return new JsonResponse(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return new JsonResponse(['id' => $form->getId()]);
    }

    #[Route('/{id}', name: 'form_delete', methods: ['DELETE'])]
    public function delete(string $id, Request $request, ValidateUserService $validateUserService, EntityManagerInterface $em, UserRepository $users): JsonResponse
    {
        $user = $validateUserService->getUserFromHeader($request, $users);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $form = $em->getRepository(Form::class)->find($id);
        if (!$form) {
            return new JsonResponse(['error' => 'Form not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($form);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
