<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{

    #[Route('', name: 'admin_login', methods: ['GET'])]
    public function logon(): Response
    {
       return $this->render('admin/login.html.twig');
    }

    #[Route('/register', name: 'admin_register', methods: ['GET'])]
    public function register(): Response
    {
        return $this->render('admin/register.html.twig');
    }

    #[Route('/sites', name: 'admin_sites', methods: ['GET'])]
    public function sites(): Response
    {
        return $this->render('admin/sites.html.twig');
    }

    #[Route('/forms', name: 'admin_forms', methods: ['GET'])]
    public function forms(): Response
    {
        return $this->render('admin/forms.html.twig');
    }

    #[Route('/submissions', name: 'admin_submissions', methods: ['GET'])]
    public function submissions(): Response
    {
        return $this->render('admin/submissions.html.twig');
    }
}
