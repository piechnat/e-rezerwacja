<?php

namespace App\Twig;

use App\CustomTypes\ReservationError;
use App\Service\AppHelper;
use DateTime;
use DateTimeInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private const MIN_TOP = -0.3;

    public function getFilters()
    {
        return [
            new TwigFilter('col_css', [$this, 'getColumnCss']),
            new TwigFilter('week_day', [$this, 'getWeekDay']),
            new TwigFilter('ico', [$this, 'getIconTag'], ['is_safe' => ['html']]),
            new TwigFilter('rsvn_err_val', [$this, 'getReservationError']),
            new TwigFilter('icu_term', [$this, 'getIcuTerm']),
            new TwigFilter('icu_date', [$this, 'getIcuDate']),
        ];
    }

    public function getColumnCss(array $rsvn): string
    {
        $top = $rsvn['css_top'];
        $height = $rsvn['css_height'];
        if ($top < self::MIN_TOP) {
            $height = $height + ($top - self::MIN_TOP);
            $top = self::MIN_TOP;
        }
        $height -= 0.1;

        return "top: {$top}em; height: {$height}em;";
    }

    public function getWeekDay($param): string
    {
        if (is_numeric($param) && $param >= 0 && $param <= 6) {
            return AppHelper::DAYS_OF_WEEK[$param];
        }
        if (!($param instanceof DateTimeInterface)) {
            $param = new DateTime($param);
        }

        return AppHelper::DAYS_OF_WEEK[$param->format('w')];
    }

    public function getIconTag($class)
    {
        return '<i class="'.$class.'"></i>';
    }

    public function getReservationError(string $error): string
    {
        return ReservationError::getValue($error);
    }

    public function getIcuTerm(string $locale, DateTimeInterface $d1, DateTimeInterface $d2): string
    {
        return AppHelper::icuTerm($d1, $d2, $locale);
    }

    public function getIcuDate(
        string $locale,
        DateTimeInterface $d1,
        bool $time = true,
        int $datetype = 0
    ): string
    {
        return AppHelper::icuDate($d1, $locale, $datetype, $time);
    }
}
