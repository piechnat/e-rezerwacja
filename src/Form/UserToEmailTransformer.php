<?php

namespace App\Form;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Security\Core\Security;

class UserToEmailTransformer implements DataTransformerInterface
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function transform($user)
    {
        if (null === $user) {
            return '';
        }

        return $user->getEmail();
    }

    public function reverseTransform($userEmail)
    {
        /** @var User */
        $loggedUser = $this->security->getUser();

        if (!$userEmail || $userEmail === $loggedUser->getEmail()) {
            return $loggedUser;
        }
        $user = $this->entityManager
            ->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        if (null === $user) {
            throw new TransformationFailedException();
        }

        return $user;
    }
}
