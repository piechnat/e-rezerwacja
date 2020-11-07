<?php

namespace App\Repository;

use App\CustomTypes\TableView;
use App\Entity\Reservation;
use App\Entity\Room;
use DateTimeImmutable;
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
        $result = $this->createQueryBuilder('rsvn')
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
            ->getQuery()->getScalarResult();

        return array_column($result, 'id');
    }

    public function getTableByRoom(
        int $roomId,
        DateTimeImmutable $beginTime,
        DateTimeImmutable $endTime
    ): TableView {
        $headers = $columns = [];
        $reservations = $this->createQueryBuilder('rsvn')
            ->select(['rsvn.id', 'rsvn.begin_time', 'rsvn.end_time',
                'user.fullname AS user_fullname', ])
            ->innerJoin('rsvn.requester', 'user')
            ->where('rsvn.room = :roomId')
            ->andWhere('rsvn.end_time > :beginTime')
            ->andWhere('rsvn.begin_time < :endTime')
            ->orderBy('rsvn.begin_time', 'ASC')
            ->setParameters([
                'roomId' => $roomId,
                'beginTime' => $beginTime,
                'endTime' => $endTime,
            ])->getQuery()->getArrayResult();

        $beginDate = $endDate = $beginTime->setTime(0, 0);
        for (; $endDate < $endTime; $endDate = $endDate->modify('+1 day')) {
            $day = $endDate->format('z');
            $headers[$day] = ['id' => $roomId, 'date' => $endDate];
            $columns[$day] = [];
        }
        foreach ($reservations as &$rsvn) {
            $day = $rsvn['begin_time']->format('z');
            if ($day === $rsvn['end_time']->format('z')) {
                $columns[$day][] = $rsvn;
            } else { // reservation which overlaps two days
                if ($rsvn['begin_time'] >= $beginDate) {
                    $columns[$day][] = $rsvn;
                }
                if ($rsvn['end_time'] < $endDate) {
                    $columns[$rsvn['end_time']->format('z')][] = $rsvn;
                }
            }
        }

        return new TableView($headers, $columns);
    }

    public function getDayTable(
        DateTimeImmutable $date,
        array $tagIds,
        bool $intersection = false
    ): TableView {
        $headers = $columns = $rooms = [];
        if (count($tagIds) > 0) {
            /** @var RoomRepository */
            $roomRepo = $this->getEntityManager()->getRepository(Room::class);
            $rooms = $roomRepo->createQueryBuilder('room')
                ->select(['room.id', 'room.title'])
                ->innerJoin('room.tags', 'tag')
                ->where('tag.id IN (:tagIds)')
                ->groupBy('room.id')
                ->orderBy('room.title', 'ASC')
                ->setParameter('tagIds', $tagIds)
            ;
            if ($intersection) {
                $rooms->having('COUNT(DISTINCT tag.id) = :tagCount')
                    ->setParameter('tagCount', count($tagIds))
                ;
            }
            $rooms = $rooms->getQuery()->getArrayResult();
        }
        if (count($rooms) > 0) {
            $beginTime = $date->modify('today');
            $endTime = $date->modify('next day');
            $reservations = $this->createQueryBuilder('rsvn')
                ->select(['rsvn.id', 'rsvn.begin_time', 'rsvn.end_time',
                    'user.fullname AS user_fullname', 'room.id AS room_id'])
                ->innerJoin('rsvn.requester', 'user')
                ->innerJoin('rsvn.room', 'room')
                ->where('rsvn.room IN (:roomIds)')
                ->andWhere('rsvn.end_time > :beginTime')
                ->andWhere('rsvn.begin_time < :endTime')
                ->orderBy('rsvn.begin_time', 'ASC')
                ->setParameters([
                    'roomIds' => array_column($rooms, 'id'),
                    'beginTime' => $beginTime,
                    'endTime' => $endTime,
                ])->getQuery()->getArrayResult();

            foreach ($rooms as &$room) {
                $headers[$room['id']] = [
                    'id' => $room['id'],
                    'date' => $beginTime,
                    'title' => $room['title'],
                ];
                $columns[$room['id']] = [];
            }
            foreach ($reservations as &$rsvn) {
                $columns[$rsvn['room_id']][] = $rsvn;
            }
        }

        return new TableView($headers, $columns);
    }
}
