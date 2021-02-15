<?php

namespace App\Repository;

use App\CustomTypes\TableView;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Service\AppHelper;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    private $cstrRepo;
    private $roomRepo;

    public function __construct(
        ManagerRegistry $registry,
        ConstraintRepository $cstrRepo,
        RoomRepository $roomRepo
    ) {
        parent::__construct($registry, Reservation::class);
        $this->cstrRepo = $cstrRepo;
        $this->roomRepo = $roomRepo;
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
        ;
        if ($rsvn->getId() > 0) {
            $result->andWhere('rsvn.id != :id')->setParameter('id', $rsvn->getId());
        }

        return array_column($result->getQuery()->getScalarResult(), 'id');
    }

    public function getUserReservations(
        int $userId,
        DateTimeImmutable $beginTime,
        DateTimeImmutable $endTime,
        bool $selfAddedOnly = false
    ): array {
        $result = $this->createQueryBuilder('rsvn')
            ->select(['rsvn.id', 'rsvn.begin_time', 'rsvn.end_time',
                'room.id AS room_id', 'room.title AS room_title', ])
            ->innerJoin('rsvn.room', 'room')
            ->where('rsvn.requester = :userId')
            ->andWhere('rsvn.end_time > :beginTime')
            ->andWhere('rsvn.begin_time < :endTime')
            ->orderBy('rsvn.begin_time', 'ASC')
            ->setParameters([
                'userId' => $userId,
                'beginTime' => $beginTime,
                'endTime' => $endTime,
            ])
        ;
        if ($selfAddedOnly) {
            $result->andWhere('rsvn.requester = rsvn.editor');
        }

        return $result->getQuery()->getArrayResult();
    }

    public function getTableByUser(
        int $userId,
        DateTimeImmutable $beginTime,
        DateTimeImmutable $endTime
    ): TableView {
        $headers = $columns = [];
        $reservations = $this->createQueryBuilder('rsvn')
            ->select(['rsvn.id', 'room.title AS room_title', 'rsvn.begin_time', 'rsvn.end_time'])
            ->innerJoin('rsvn.requester', 'user')
            ->innerJoin('rsvn.room', 'room')
            ->where('user.id = :userId')
            ->andWhere('rsvn.end_time > :beginTime')
            ->andWhere('rsvn.begin_time < :endTime')
            ->orderBy('rsvn.begin_time', 'ASC')
            ->setParameters([
                'userId' => $userId,
                'beginTime' => $beginTime,
                'endTime' => $endTime,
            ])->getQuery()->getArrayResult();

        $beginDate = $endDate = $beginTime->setTime(0, 0);
        for (; $endDate < $endTime; $endDate = $endDate->modify('+1 day')) {
            $day = $endDate->format('z');
            $headers[$day] = ['date' => $endDate];
            $columns[$day] = [];
        }
        foreach ($reservations as $rsvn) {
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
        AppHelper::addOpeningHours($headers, $this->cstrRepo);

        return new TableView($headers, $columns);
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
        foreach ($reservations as $rsvn) {
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
        AppHelper::addOpeningHours($headers, $this->cstrRepo);

        return new TableView($headers, $columns);
    }

    public function getTableByDay(
        DateTimeImmutable $date,
        array $tagIds,
        bool $intersection = false
    ): TableView {
        $headers = $columns = $rooms = [];
        if (count($tagIds) > 0) {
            $rooms = $this->roomRepo->createQueryBuilder('room')
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
                ->select(['rsvn.id', 'room.id AS room_id', 'rsvn.begin_time', 'rsvn.end_time', 
                    'user.fullname AS user_fullname', ])
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

            foreach ($rooms as $room) {
                $headers[$room['id']] = [
                    'id' => $room['id'],
                    'date' => $beginTime,
                    'title' => $room['title'],
                ];
                $columns[$room['id']] = [];
            }
            foreach ($reservations as $rsvn) {
                $columns[$rsvn['room_id']][] = $rsvn;
            }
        }
        AppHelper::addOpeningHours($headers, $this->cstrRepo);

        return new TableView($headers, $columns);
    }
}