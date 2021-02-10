<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function match(string $query, int $limit = 10): array
    {
        return  $this->createQueryBuilder('user')
            ->select(['user.id', 'user.fullname', 'user.email'])
            ->where('user.fullname LIKE :nameQuery')
            ->orWhere('user.email LIKE :emailQuery')
            ->setMaxResults($limit)
            ->setParameters([
                'nameQuery' => "%{$query}%",
                'emailQuery' => "%{$query}%@%",
            ])
            ->getQuery()->getScalarResult();
    }
}
