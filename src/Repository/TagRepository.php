<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|Tag find($id, $lockMode = null, $lockVersion = null)
 * @method null|Tag findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function getTagIdsByRoomId(int $roomId, bool $searchTags = false): array
    {
        $result = $this->createQueryBuilder('tag')
            ->select('tag.id')
            ->innerJoin('tag.rooms', 'room')
            ->where('room.id = :roomId')
            ->setParameter('roomId', $roomId)
        ;
        if ($searchTags) {
            $result->andWhere('tag.search = true');
        }

        return array_column($result->getQuery()->getScalarResult(), 'id');
    }
}
