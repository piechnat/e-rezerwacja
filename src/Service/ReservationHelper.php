<?php

namespace App\Service;

use App\CustomTypes\ReservationNotPossibleException;
use App\CustomTypes\ReservationError as RsvnErr;
use App\CustomTypes\ReservationNotAllowedException;
use App\CustomTypes\UserLevel;
use App\Entity\Reservation;
use App\Repository\ConstraintRepository;
use App\Repository\ReservationRepository;
use Exception;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReservationHelper
{
    private $security;
    private $trans;
    private $generator;
    private $rsvnRepo;
    private $cstrRepo;

    public function __construct(
        Security $security,
        TranslatorInterface $translator,
        UrlGeneratorInterface $generator,
        ReservationRepository $repo,
        ConstraintRepository $cstrRepo
    ) {
        $this->security = $security;
        $this->trans = $translator;
        $this->generator = $generator;
        $this->rsvnRepo = $repo;
        $this->cstrRepo = $cstrRepo;
    }

    public function createFormError(Exception $exc): FormError
    {
        $message = $exc->getMessage();
        $code = $exc->getCode();
        $content = $this->trans->trans(RsvnErr::getValue($message));

        switch ($message) {
            case RsvnErr::RSVN_CONFLICT:
            case RsvnErr::RSVN_SELF_CONFLICT:
                $url = $this->generator->generate('reservation_show', ['id' => $code]);
                $text = $this->trans->trans('Zobacz konflikt');
                $content .= " <a href=\"{$url}\">{$text}</a>.";
                break;
            case RsvnErr::MAX_ADVANCE_TIME:
            case RsvnErr::MAX_RSVN_LENGTH:
            case RsvnErr::WEEK_LIMIT_EXCEEDED:
            case RsvnErr::ROOM_BREAK_VIOLATED:
                $content .= " ({$code})";
                break;
        }

        return new FormError($content);
    }

    /**
     * @throws ReservationNotPossibleException|ReservationNotAllowedException
     */
    public function checkConstraints(Reservation $rsvn)
    {
        $exception = $this->getNotAllowedException($rsvn);
        if (null !== $exception) {
            $this->checkConflicts($rsvn);

            throw $exception;
        }
    }

    /**
     * @throws ReservationNotPossibleException
     */
    public function checkConflicts(Reservation $rsvn)
    {
        $conflictIds = $this->rsvnRepo->getConflictIds($rsvn);
        if (count($conflictIds) > 0) {
            throw new ReservationNotPossibleException(RsvnErr::RSVN_CONFLICT, $conflictIds[0]);
        }
    }

    public function getNotAllowedException(Reservation $rsvn): ?Exception
    {
        // --------------------------------------------------------------------------- NO_PRIVILEGES
        foreach ($rsvn->getRoom()->getTags() as $tag) {
            if (
                $tag->getLevel() >= $rsvn->getEditor()->getAccessLevel()
                && !$rsvn->getEditor()->getTags()->contains($tag)
            ) {
                return new ReservationNotAllowedException(RsvnErr::NO_PRIVILEGES);
            }
        }

        // --------------------------------------------------------------------- that's all if admin
        if ($this->security->isGranted(UserLevel::ADMIN, $rsvn->getEditor())) {
            return null;
        }

        // ------------------------------------------------------------------------ MAX_ADVANCE_TIME
        $rsvnLimits = $this->cstrRepo->getReservationLimits();
        $rsvnBT = $rsvn->getBeginTime();
        if ($rsvn->getEditTime() < $rsvnBT->modify("-{$rsvnLimits['MAX_ADVANCE_TIME_DAY']} days")) {
            return new ReservationNotAllowedException(
                RsvnErr::MAX_ADVANCE_TIME,
                $rsvnLimits['MAX_ADVANCE_TIME_DAY']
            );
        }

        // ------------------------------------------------------------------------- MAX_RSVN_LENGTH
        $rsvnET = $rsvn->getEndTime();
        $rsvnLength = (int)(($rsvnET->getTimestamp() - $rsvnBT->getTimestamp()) / 60);
        if ($rsvnLength > $rsvnLimits['MAX_RSVN_LENGTH_MIN']) {
            return new ReservationNotAllowedException(
                RsvnErr::MAX_RSVN_LENGTH, 
                $rsvnLimits['MAX_RSVN_LENGTH_MIN']
            );
        }

        // ------------------------------------------------------------------------- MIN_RSVN_LENGTH
        if ($rsvnLength < $rsvnLimits['MIN_RSVN_LENGTH_MIN']) {
            return new ReservationNotPossibleException(
                RsvnErr::MIN_RSVN_LENGTH, 
                $rsvnLimits['MIN_RSVN_LENGTH_MIN']
            );
        }

        // -------------------------------------------------------------- get reservations from week
        $requesterId = $rsvn->getRequester()->getId();
        $endTime = $rsvnBT->modify('next monday');
        $beginTime = $endTime->modify('last monday');
        $weekRsvns = $this->rsvnRepo->getUserReservations($requesterId, $beginTime, $endTime, true);
        $weekLength = 0;
        foreach ($weekRsvns as $key => $rec) {
            if ($rec['id'] === $rsvn->getId()) {
                unset($weekRsvns[$key]); // remove reservation if is edited
            } else {
                $secs = $rec['end_time']->getTimestamp() - $rec['begin_time']->getTimestamp();
                $weekLength += (int)($secs / 60);
            }
        }

        // ---------------------------------------------------------------------- RSVN_SELF_CONFLICT
        foreach ($weekRsvns as $rec) {
            if ($rec['end_time'] > $rsvnBT && $rec['begin_time'] < $rsvnET) {
                return new ReservationNotAllowedException(RsvnErr::RSVN_SELF_CONFLICT, $rec['id']);
            }
        }

        // --------------------------------------------------------------------- WEEK_LIMIT_EXCEEDED
        if ($weekLength + $rsvnLength > ($rsvnLimits['RSVN_WEEK_LIMIT_HR'] * 60)) {
            return new ReservationNotAllowedException(
                RsvnErr::WEEK_LIMIT_EXCEEDED,
                $rsvnLimits['RSVN_WEEK_LIMIT_HR'],
            );
        }

        // --------------------------------------------------------------------- ROOM_BREAK_VIOLATED
        $roomId = $rsvn->getRoom()->getId();
        $beginTime = $rsvnBT->modify("-{$rsvnLimits['MAX_RSVN_LENGTH_MIN']} minutes");
        $endTime = $rsvnET->modify("+{$rsvnLimits['MAX_RSVN_LENGTH_MIN']} minutes");
        foreach ($weekRsvns as $rec) {
            if (
                $rec['room_id'] === $roomId
                && $rec['end_time'] > $beginTime
                && $rec['begin_time'] < $endTime
            ) {
                return new ReservationNotAllowedException(
                    RsvnErr::ROOM_BREAK_VIOLATED,
                    $rsvnLimits['MAX_RSVN_LENGTH_MIN']
                );
            }
        }

        // ------------------------------------------------------------------- RSVN_OUTSIDE_SCHEDULE
        if (!$this->cstrRepo->isReservationOnSchedule($rsvn)) {
            return new ReservationNotAllowedException(RsvnErr::RSVN_OUTSIDE_SCHEDULE);
        }

        // -------------------------------------------------------------------------------------- OK
        return null;
    }
}
