<?php
namespace App\Service;

use App\Entity\Site;
use App\Entity\Form;
use App\Entity\FormData;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ValidateUserService
{

    /**
     * Get user from Authorization header
     *
     * @param Request $request
     * @param UserRepository $users
     * @return object|null
     */
    public function getUserFromHeader(Request $request, UserRepository $users): ?object
    {
        $auth = $request->headers->get('Authorization');
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
            return $token ? $users->findOneBy(['apiToken' => $token]) : null;
        }

        return null;
    }

}