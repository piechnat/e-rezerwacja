<?php

namespace App\Service;

use App\CustomTypes\Lang;
use App\Entity\Request;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\ConstraintRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use IntlDateFormatter;
use InvalidArgumentException;
use Symfony\Component\Form\FormInterface;

class AppHelper
{
    public const DAYS_OF_WEEK = [
        'Niedziela', 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota',
    ];

    /**
     * @param Reservation|Request $object Instance of reservation or request entity
     */
    public static function term($object, User $user = null): string
    {
        if (!($object instanceof Reservation || $object instanceof Request)) {
            throw new InvalidArgumentException();
        }
        $locale = ($user ?? $object->getRequester())->getLang();

        return self::icuTerm($object->getBeginTime(), $object->getEndTime(), $locale);
    }

    public static function icuTerm(
        DateTimeInterface $beginTime,
        DateTimeInterface $endTime,
        $locale = null
    ): string {
        return self::icuDate($beginTime, $locale).$endTime->format('-H:i');
    }

    public static function icuDate(
        DateTimeInterface $datetime,
        $locale = null,
        int $datetype = 0,
        bool $time = true
    ): string {
        if (!$locale) {
            $locale = Lang::PL;
        } elseif ($locale instanceof User) {
            $locale = $locale->getLang();
        } else {
            $locale = Lang::valid($locale);
        }
        $result = (new IntlDateFormatter($locale, $datetype, -1))->format($datetime);
        if ($time) {
            $result .= $datetime->format(', H:i');
        }

        return $result;
    }

    // ---------------------------------------------------------------------------------------------

    public static function updateForm(
        FormInterface $form,
        string $childName,
        ?string $childType = null,
        array $options = []
    ) {
        $srcOptions = $form->get($childName)->getConfig()->getOptions();
        foreach ($options as $key => $val) {
            $srcOptions[$key] = $val;
        }
        $form->add($childName, $childType, $srcOptions);
    }

    // ---------------------------------------------------------------------------------------------

    public static function addOpeningHours(array &$headers, ConstraintRepository $cstrRepo)
    {
        if (count($headers) > 0) {
            $hours = $cstrRepo->getOpeningHours(reset($headers)['date'], end($headers)['date']);
            foreach ($headers as $key => $header) {
                $headers[$key]['hours'] = $hours[$header['date']->format('Y-m-d')];
            }
        }
    }

    // ---------------------------------------------------------------------------------------------

    public static function getMissingAccessLevel(User $user, Room $room): int
    {
        $result = 0;
        foreach ($room->getTags() as $tag) {
            if (!($user->getAccessLevel() > $tag->getLevel() || $user->getTags()->contains($tag))) {
                $result = max($result, ($tag->getLevel() - $user->getAccessLevel()) + 1);
            }
        }

        return $result;
    }

    /**
     * @param array|Collection $tags
     */
    public static function getUnauthorizedTags(User $user, $tags): array
    {
        if (!($tags instanceof Collection || is_array($tags))) {
            throw new InvalidArgumentException();
        }
        $result = [];
        foreach ($tags as $tag) {
            if ($tag->getLevel() >= $user->getAccessLevel()) {
                $result[] = $tag;
            }
        }

        return $result;
    }

    // ---------------------------------------------------------------------------------------------

    public static function USR($object): ?User
    {
        return $object->getUser();
    }
}
