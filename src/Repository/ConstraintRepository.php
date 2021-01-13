<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\TimeConstraint;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|TimeConstraint find($id, $lockMode = null, $lockVersion = null)
 * @method null|TimeConstraint findOneBy(array $criteria, array $orderBy = null)
 * @method TimeConstraint[]    findAll()
 * @method TimeConstraint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConstraintRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeConstraint::class);
    }

    public function removeExpired()
    {
        $this->createQueryBuilder('cstr')
            ->delete()
            ->where('cstr.end_date < CURRENT_TIMESTAMP()')
            ->getQuery()->getResult();
    }

    public function isReservationOnSchedule(Reservation $rsvn): bool
    {
        $beginTime = $rsvn->getBeginTime();
        $openingHours = $this->getOpeningHours($rsvn->getEditor(), $beginTime);
        $hours = reset($openingHours);
        if (!$hours) {
            return false;
        }
        if ($hours['begin_time'] && $beginTime < $hours['begin_time']) {
            return false;
        }
        if ($hours['end_time'] && $rsvn->getEndTime() > $hours['end_time']) {
            return false;
        }
        return true;
    }

    public function getOpeningHours(
        User $user,
        DateTimeImmutable $beginDate,
        DateTimeImmutable $endDate = null
    ): array {
        $beginDate = $beginDate->modify('today');
        $endDate = (null !== $endDate) ? $endDate->modify('today') : $beginDate;

        $timeCstrs = $this->createQueryBuilder('cstr')
            ->innerJoin('cstr.exclusions', 'exclusions')
            ->addSelect('exclusions')
            ->where('cstr.end_date >= :beginDate')
            ->andWhere('cstr.begin_date <= :endDate')
            ->orderBy('cstr.begin_date', 'DESC')
            ->setParameters([
                'beginDate' => $beginDate,
                'endDate' => $endDate,
            ])->getQuery()->getResult();

        foreach ($timeCstrs as $index => $cstr) {
            foreach ($cstr->getExclusions() as $tag) {
                if ($user->getTags()->contains($tag)) {
                    array_splice($timeCstrs, $index, 1);
                }
            }
        }

        $result = [];
        $cstrsLen = count($timeCstrs);
        for (; $beginDate <= $endDate; $beginDate = $beginDate->modify('+1 day')) {
            $index = 0;
            for (; 
                $index < $cstrsLen
                    && ($beginDate < $timeCstrs[$index]->getBeginDate()
                    || $beginDate > $timeCstrs[$index]->getEndDate());
                $index++
            );
            $result[$beginDate->format('Y-m-d')] = ($index !== $cstrsLen) ?
                $this->getOpeningHoursOfDay($beginDate, $timeCstrs[$index]->getSchedule()) : null;
        }

        return $result;
    }

    private function getOpeningHoursOfDay(DateTimeImmutable $date, ?array $schedule): ?array
    {
        if (!$schedule || 0 === count($schedule)) {
            return null;
        }
        $hours = $schedule[$date->format('w') + 1] ?? $schedule[0];
        if ($hours['from'] === $hours['to']) {
            return null;
        }
        if (!$hours['to'] || !$hours['from']) {
            return ['begin_time' => null, 'end_time' => null];
        }
        $beginTime = $date->modify($hours['from']);
        $endTime = $date->modify($hours['to']);
        if ($endTime < $beginTime) {
            $endTime = $endTime->modify('+1 day');
        }

        return ['begin_time' => $beginTime, 'end_time' => $endTime];
    }
}
