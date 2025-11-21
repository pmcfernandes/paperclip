<?php
namespace App\Controller;

use App\Entity\Form;
use App\Entity\FormData;
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

#[Route('/api/forms/submissions')]
class FormDataController extends AbstractController
{

    #[Route('', name: 'list_submissions', methods: ['GET'])]
    public function list_submissions(Request $request, ValidateUserService $validateUserService, EntityManagerInterface $em, UserRepository $users): JsonResponse
    {
        $user = $validateUserService->getUserFromHeader($request, $users);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $formId = $request->query->getInt('form_id') ?: null;

        $form = $em->getRepository(Form::class)->find($formId);
        if (!$form) {
            return new JsonResponse(['error' => 'Form not found'], Response::HTTP_NOT_FOUND);
        }

        $repo = $em->getRepository(FormData::class);
        $filterSubmitId = $request->query->getInt('submit_id') ?: null;

        $criteria = ['form' => $form];
        if ($filterSubmitId !== null) {
            $criteria['submitId'] = $filterSubmitId;
        }

        // Fetch field rows for this form (optionally filtered). Order ensures grouping stability.
        $submissions = $repo->findBy($criteria, ['submitId' => 'DESC']);

        // Group rows by submit_id and merge name=>value pairs into one table row per submit_id
        $grouped = [];
        foreach ($submissions as $fdRow) {
            $sid = $fdRow->getSubmitId();
            if (!isset($grouped[$sid])) {
                $grouped[$sid] = [
                    'submit_id' => $sid,
                    'ids' => [],
                    'fields' => [],
                    'submitted_at' => null,
                ];
            }

            $grouped[$sid]['ids'][] = $fdRow->getId();
            $fieldName = $fdRow->getName();
            $grouped[$sid]['fields'][$fieldName] = $fdRow->getValue();

            if ($grouped[$sid]['submitted_at'] === null && $fdRow->getWhen() !== null) {
                $grouped[$sid]['submitted_at'] = $fdRow->getWhen()->format('c');
            }
        }

        $result = [];
        foreach ($grouped as $sid => $g) {
            $fields = $g['fields'];
            $name = $fields['name'] ?? $fields['full_name'] ?? ($fields['first_name'] ?? null);

            $row = [
                'id' => $g['ids'][0] ?? null,
                'submit_id' => $sid,
                'name' => $name,
                'submitted_at' => $g['submitted_at'],
            ];

            foreach ($fields as $k => $v) {
                if (!array_key_exists($k, $row)) {
                    $row[$k] = $v;
                }
            }

            $result[] = $row;
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }
}
