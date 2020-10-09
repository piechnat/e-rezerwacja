<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|Reservation find($id, $lockMode = null, $lockVersion = null)
 * @method null|Reservation findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function getConflictIds(Reservation $rsvn, int $limit = 1): array
    {
        $res = $this->createQueryBuilder('rsvn')
            ->select('rsvn.id')
            ->where('rsvn.room = :roomId')
            ->andWhere(':beginTime < rsvn.end_time')
            ->andWhere(':endTime > rsvn.begin_time')
            ->setMaxResults($limit)
            ->setParameters([
                'roomId' => $rsvn->getRoom()->getId(),
                'beginTime' => $rsvn->getBeginTime(),
                'endTime' => $rsvn->getEndTime(),
            ])
            ->getQuery()->getResult();

        return array_map('current', $res);
    }

    /**
     * Fetch data for calendar table view.
     *
     * @return array
     */
    public function getTableByRoom(
        int $room_id,
        \DateTimeInterface $begin_time,
        \DateTimeInterface $end_time
    ) {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT rsvn.id, rsvn.begin_time, rsvn.end_time, 
                users.username AS requester_username 
                FROM reservations AS rsvn 
                INNER JOIN users ON rsvn.requester_id = users.id 
                WHERE rsvn.room_id = ? AND rsvn.begin_time >= ? AND rsvn.end_time <= ?',
            [$room_id, $begin_time, $end_time],
            ['integer', 'datetime', 'datetime']
        );
    }

    /**
     * Fetch data for calendar table view.
     *
     * @return array
     */
    public function getTableByRequester(
        int $user_id,
        \DateTimeInterface $begin_time,
        \DateTimeInterface $end_time
    ) {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT rsvn.id, rsvn.begin_time, rsvn.end_time, rooms.title AS room_title
                FROM reservations AS rsvn 
                INNER JOIN rooms ON rsvn.room_id = rooms.id 
                WHERE rsvn.requester_id = ? AND rsvn.begin_time >= ? AND rsvn.end_time <= ?',
            [$user_id, $begin_time, $end_time],
            ['integer', 'datetime', 'datetime']
        );
    }
}
