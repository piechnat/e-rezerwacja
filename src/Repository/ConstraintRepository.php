<?php

namespace App\Repository;

use App\CustomTypes\UserLevel;
use App\Entity\Reservation;
use App\Entity\Tag;
use App\Entity\TimeConstraint;
use App\Entity\User;
use App\Service\AppHelper;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Security\Core\Security;

/**
 * @method null|TimeConstraint find($id, $lockMode = null, $lockVersion = null)
 * @method null|TimeConstraint findOneBy(array $criteria, array $orderBy = null)
 * @method TimeConstraint[]    findAll()
 * @method TimeConstraint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConstraintRepository extends ServiceEntityRepository
{
    public const MIN_RSVN_LEN = 15;
    public const CLOSE_SCHEDULE = -1;
    public const VALID_SCHEDULE = 0;
    public const OPEN_SCHEDULE = 1;

    private $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, TimeConstraint::class);
        $this->security = $security;
    }

    public function removeExpired()
    {
        $this->createQueryBuilder('cstr')
            ->delete()
            ->where('cstr.end_date < CURRENT_TIMESTAMP()')
            ->getQuery()->getResult();
    }

    public function getReservationLimits(): array
    {
        $cache = new PdoAdapter($this->getEntityManager()->getConnection());

        return $cache->get('RSVN_LIMITS', function () {
            return [
                'MAX_ADVANCE_TIME_DAY' => 14,
                'MAX_RSVN_LENGTH_MIN' => 120,
                'MIN_RSVN_LENGTH_MIN' => 45,
                'RSVN_WEEK_LIMIT_HR' => 14,
            ];
        });
    }

    public function setReservationLimits(array $newList)
    {
        $cache = new PdoAdapter($this->getEntityManager()->getConnection());
        $rsvnLimits = $cache->getItem('RSVN_LIMITS');
        $list = $rsvnLimits->get() ?? [];
        foreach ($newList as $key => $value) {
            $list[$key] = $value;
        }
        $rsvnLimits->set($list);
        $cache->save($rsvnLimits);
    }

    public function isReservationOnSchedule(Reservation $rsvn): bool
    {
        $beginTime = $rsvn->getBeginTime();
        $openingHours = $this->getOpeningHours($beginTime, null, $rsvn->getEditor());
        $day = reset($openingHours);
        if (self::CLOSE_SCHEDULE === $day['state']) {
            return false;
        }
        if (self::OPEN_SCHEDULE === $day['state']) {
            return true;
        }

        return $beginTime >= $day['begin_time'] && $rsvn->getEndTime() <= $day['end_time'];
    }

    public function getOpeningHours(
        DateTimeImmutable $beginDate,
        DateTimeImmutable $endDate = null,
        User $user = null
    ): array {
        $beginDate = $beginDate->modify('today');
        $endDate = $endDate ? $endDate->modify('today') : $beginDate;
        
        $timeCstrs = $this->createQueryBuilder('cstr')
            ->leftJoin('cstr.exclusions', 'exclusions')
            ->addSelect('exclusions')
            ->where('cstr.end_date >= :beginDate')
            ->andWhere('cstr.begin_date <= :endDate')
            ->orderBy('cstr.begin_date', 'DESC')
            ->setParameters([
                'beginDate' => $beginDate,
                'endDate' => $endDate,
            ])->getQuery()->getResult();
        
        $user = $user ?? AppHelper::USR($this->security);
        if ($user) {
            $tags = $user->getTags();
            foreach ($timeCstrs as $index => $cstr) {
                foreach ($cstr->getExclusions() as $tag) {
                    if ($tags->contains($tag)) {
                        array_splice($timeCstrs, $index, 1);
                    }
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
                ++$index
            );
            $result[$beginDate->format('Y-m-d')] = $this->getOpeningHoursOfDay(
                $beginDate,
                ($index !== $cstrsLen) ? $timeCstrs[$index]->getSchedule() : null
            );
        }

        return $result;
    }

    private function getOpeningHoursOfDay(DateTimeImmutable $date, ?array $schedule): ?array
    {
        $result = ['state' => self::CLOSE_SCHEDULE, 'begin_time' => null, 'end_time' => null];
        if (!$schedule || 0 === count($schedule)) {
            return $result;
        }
        $hours = $schedule[$date->format('w') + 1] ?? $schedule[0];
        if ($hours['from'] === $hours['to']) {
            return $result;
        }
        if (!$hours['to'] || !$hours['from']) {
            $result['state'] = self::OPEN_SCHEDULE;

            return $result;
        }
        $result['state'] = self::VALID_SCHEDULE;
        $result['begin_time'] = $date->modify($hours['from']);
        $result['end_time'] = $date->modify($hours['to']);
        if ($result['end_time'] < $result['begin_time']) {
            $result['end_time'] = $result['end_time']->modify('+1 day');
        }

        return $result;
    }
}
