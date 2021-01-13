<?php

namespace App\CustomTypes;

abstract class ReservationError extends CustomType
{
    const RSVN_ALLOWED = 'RE_RSVN_ALLOWED';
    const RSVN_CONFLICT = 'RE_RSVN_CONFLICT';
    const NO_PRIVILEGES = 'RE_NO_PRIVILEGES';
    const MAX_ADVANCE_TIME = 'RE_MAX_ADVANCE_TIME';
    const MAX_RSVN_LENGTH = 'RE_MAX_RSVN_LENGTH';
    const RSVN_SELF_CONFLICT = 'RE_RSVN_SELF_CONFLICT';
    const WEEK_LIMIT_EXCEEDED = 'RE_WEEK_LIMIT_EXCEEDED';
    const ROOM_BREAK_VIOLATED = 'RE_ROOM_BREAK_VIOLATED';
    const RSVN_OUTSIDE_SCHEDULE = 'RE_RSVN_OUTSIDE_SCHEDULE';

    protected const VALUES = [
        self::RSVN_ALLOWED => 'Rezerwacja sali jest dozwolona.',
        self::RSVN_CONFLICT => 'Podany termin pokrywa się z inną rezerwacją w tej sali.',
        self::NO_PRIVILEGES => 'Nie posiadasz wystarczających uprawnień do zarezerwowania sali.',
        self::MAX_ADVANCE_TIME => 'Przekroczono maksymalne wyprzedzenie terminu rezerwacji.',
        self::MAX_RSVN_LENGTH => 'Przekroczono limit długości pojedynczej rezerwacji.',
        self::RSVN_SELF_CONFLICT => 'Posiadasz inną rezerwację w proponowanym terminie.',
        self::WEEK_LIMIT_EXCEEDED => 'Przekroczono limit długości wszystkich rezerwacji w tygodniu.',
        self::ROOM_BREAK_VIOLATED => 'Za krótka przerwa między rezerwacjami w tej samej sali.',
        self::RSVN_OUTSIDE_SCHEDULE => 'Rezerwacja wykracza poza harmonogram pracy uczelni.',
    ];
}
