<?php
namespace App\Controller;

use App\Entity\Form as FormEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/forms')]
class FormRenderController extends AbstractController
{
    #[Route('/{slug}', name: 'form_render', methods: ['GET'])]
    public function show(string $slug, EntityManagerInterface $em, RequestStack $requestStack): Response
    {
        $formEntity = $em->getRepository(FormEntity::class)->findOneBy(['slug' => $slug]);

        if (!$formEntity) {
            throw $this->createNotFoundException('Form not found');
        }

        $action = '/forms/' . $slug . '/submit';
        $method = 'POST';

        // Optional: include current request uri or referer if needed
        $request = $requestStack->getCurrentRequest();

        return $this->render('forms/render.html.twig', [
            'formEntity' => $formEntity,
            'slug' => $slug,
            'action' => $action,
            'method' => $method,
            'request' => $request,
        ]);
    }
}
