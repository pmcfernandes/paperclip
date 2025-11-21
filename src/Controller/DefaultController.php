<?php
namespace App\Controller;

#Route('/', name: 'default_home', methods: ['GET'])]
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function index(): Response
    {
        return new Response("", Response::HTTP_NOT_FOUND);
    }
}