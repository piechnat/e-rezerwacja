<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AppHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Security\Core\Security;

class UserToEmailTransformer implements DataTransformerInterface
{
    private $userRepo;
    private $security;

    public function __construct(UserRepository $userRepo, Security $security)
    {
        $this->userRepo = $userRepo;
        $this->security = $security;
    }

    public function transform($user): string
    {
        if (!$user) {
            return '';
        }

        return $user->getEmail();
    }

    public function reverseTransform($userEmail): User
    {
        $selfUser = AppHelper::USR($this->security);

        if (!$userEmail || $userEmail === $selfUser->getEmail()) {
            return $selfUser;
        }
        $user = $this->userRepo->findOneBy(['email' => $userEmail]);

        if (!$user) {
            throw new TransformationFailedException();
        }

        return $user;
    }
}
