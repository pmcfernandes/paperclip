<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    #[Route('/register', name: 'api_user_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserRepository $users): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $username = trim((string)($data['username'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if ($username === '' || $email === '' || $password === '') {
            return new JsonResponse(['error' => 'username, email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        if ($users->findOneBy(['username' => $username]) || $users->findOneBy(['email' => $email])) {
            return new JsonResponse(['error' => 'username or email already taken'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword(password_hash($password, PASSWORD_DEFAULT));

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['id' => $user->getId(), 'username' => $user->getUsername(), 'email' => $user->getEmail()], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_user_login', methods: ['POST'])]
    public function login(Request $request, UserRepository $users, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $usernameOrEmail = $data['username'] ?? $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$usernameOrEmail || !$password) {
            return new JsonResponse(['error' => 'username (or email) and password required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $users->findOneBy(['username' => $usernameOrEmail]) ?: $users->findOneBy(['email' => $usernameOrEmail]);
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        if (!password_verify($password, $user->getPassword())) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

                // Generate a persistent API token for the user and return it
                $token = bin2hex(random_bytes(32));
                $user->setApiToken($token);
                $em->persist($user);
                $em->flush();

                return new JsonResponse([
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'token' => $token
                ]);
    }
}
