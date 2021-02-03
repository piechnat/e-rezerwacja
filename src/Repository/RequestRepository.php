<?php

namespace App\Repository;

use App\Entity\Request;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|Request find($id, $lockMode = null, $lockVersion = null)
 * @method null|Request findOneBy(array $criteria, array $orderBy = null)
 * @method Request[]    findAll()
 * @method Request[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Request::class);
    }

    public function getUserRequestsCount(int $userId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r)')
            ->andWhere('r.requester = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()->getSingleScalarResult()
        ;
    }
}
