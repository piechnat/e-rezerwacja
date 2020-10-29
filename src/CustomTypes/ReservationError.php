<?php

namespace App\CustomTypes;

abstract class ReservationError extends CustomType
{
    const RSVN_ALLOWED = 'RE_RSVN_ALLOWED';
    const RSVN_CONFLICT = 'RE_RSVN_CONFLICT';
    const NO_PRIVILEGES = 'RE_NO_PRIVILEGES';

    protected const VALUES = [
        self::RSVN_ALLOWED => 'Rezerwacja sali jest dozwolona.',
        self::RSVN_CONFLICT => 'W podanym terminie sala jest już zarezerwowana.',
        self::NO_PRIVILEGES => 'Nie posiadasz wystarczających uprawnień do zarezerwowania sali.',
    ];
}