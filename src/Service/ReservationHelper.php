<?php

namespace App\Service;

use App\CustomTypes\ReservationConflictException;
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
        $content = '';
        if ($exc instanceof ReservationNotAllowedException) {
            $content .= $this->trans->trans(RsvnErr::getValue($exc->getMessage()));
        } elseif ($exc instanceof ReservationConflictException) {
            $content .= $this->trans->trans(RsvnErr::getValue(RsvnErr::RSVN_CONFLICT));
            $url = $this->generator->generate('reservation_show', ['id' => $exc->getCode()]);
            $text = $this->trans->trans('Zobacz konflikt');
            $content .= ' <a href="'.$url.'">'.$text.'</a>.';
        }

        return new FormError($content);
    }

    /**
     * @throws ReservationConflictException|ReservationNotAllowedException
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
     * @throws ReservationConflictException
     */
    public function checkConflicts(Reservation $rsvn)
    {
        $conflictIds = $this->rsvnRepo->getConflictIds($rsvn);
        if (count($conflictIds) > 0) {
            throw new ReservationConflictException(RsvnErr::RSVN_CONFLICT, $conflictIds[0]);
        }
    }

    public function getNotAllowedException(Reservation $rsvn): ?Exception
    {
        // --------------------------------------------------------------------------- NO_PRIVILEGES
        foreach ($rsvn->getRoom()->getTags() as $tag) {
            if (
                $tag->getLevel() >= $rsvn->getEditor()->getAccessLevel()
                && false === $rsvn->getEditor()->getTags()->contains($tag)
            ) {
                return new ReservationNotAllowedException(RsvnErr::NO_PRIVILEGES);
            }
        }

        // --------------------------------------------------------------------- that's all if admin
        if ($this->security->isGranted(UserLevel::ADMIN, $rsvn->getEditor())) {
            return null;
        }

        // ------------------------------------------------------------------------ MAX_ADVANCE_TIME
        $rsvnBT = $rsvn->getBeginTime();
        if ($rsvn->getEditTime() < $rsvnBT->modify('-2 weeks')) {
            return new ReservationNotAllowedException(RsvnErr::MAX_ADVANCE_TIME);
        }

        // ------------------------------------------------------------------------- MAX_RSVN_LENGTH
        $rsvnET = $rsvn->getEndTime();
        $rsvnLength = floor(($rsvnET->getTimestamp() - $rsvnBT->getTimestamp()) / 60);
        if ($rsvnLength > 120) {
            return new ReservationNotAllowedException(RsvnErr::MAX_RSVN_LENGTH);
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
                $weekLength += floor($secs / 60);
            }
        }

        // ---------------------------------------------------------------------- RSVN_SELF_CONFLICT
        foreach ($weekRsvns as $rec) {
            if ($rec['end_time'] > $rsvnBT && $rec['begin_time'] < $rsvnET) {
                return new ReservationConflictException(RsvnErr::RSVN_SELF_CONFLICT, $rec['id']);
            }
        }

        // --------------------------------------------------------------------- WEEK_LIMIT_EXCEEDED
        if ($weekLength + $rsvnLength > 840) {
            return new ReservationNotAllowedException(RsvnErr::WEEK_LIMIT_EXCEEDED);
        }

        // --------------------------------------------------------------------- ROOM_BREAK_VIOLATED
        $roomId = $rsvn->getRoom()->getId();
        $beginTime = $rsvnBT->modify('-2 hours');
        $endTime = $rsvnET->modify('+2 hours');
        foreach ($weekRsvns as $rec) {
            if (
                $rec['room_id'] === $roomId
                && $rec['end_time'] > $beginTime
                && $rec['begin_time'] < $endTime
            ) {
                return new ReservationNotAllowedException(RsvnErr::ROOM_BREAK_VIOLATED);
            }
        }

        // ------------------------------------------------------------------- RSVN_OUTSIDE_SCHEDULE
        if (false === $this->cstrRepo->isReservationOnSchedule($rsvn)) {
            return new ReservationNotAllowedException(RsvnErr::RSVN_OUTSIDE_SCHEDULE);
        }

        // -------------------------------------------------------------------------------------- OK
        return null;
    }
}
