<?php

namespace App\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Room|null find($id, $lockMode = null, $lockVersion = null)
 * @method Room|null findOneBy(array $criteria, array $orderBy = null)
 * @method Room[]    findAll()
 * @method Room[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    public function match(string $query, int $limit = 10): array
    {
        return  $this->createQueryBuilder('room')
            ->select(['room.id', 'room.title'])
            ->where('room.title LIKE :titleQuery')
            ->setMaxResults($limit)
            ->setParameter('titleQuery', "%{$query}%")
            ->getQuery()->getScalarResult();
    }
}
