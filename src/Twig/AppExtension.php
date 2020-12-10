<?php

namespace App\Twig;

use DateTime;
use DateTimeInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private const MIN_TOP = -0.3;

    private const DAYS_OF_WEEK = [
        'Niedziela', 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota', ];

    public function getFilters()
    {
        return [
            new TwigFilter('col_css', [$this, 'getColumnCss']),
            new TwigFilter('week_day', [$this, 'getWeekDay']),
            new TwigFilter('ico', [$this, 'getIconTag'], ['is_safe' => ['html']]),
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

        return "top: {$top}em; height: {$height}em;";
    }

    public function getWeekDay($date): string
    {
        if (!($date instanceof DateTimeInterface)) {
            $date = new DateTime($date);
        }

        return self::DAYS_OF_WEEK[$date->format('w')];
    }

    public function getIconTag($class) {
        return '<i class="'. $class .'"></i>';
    }
}
