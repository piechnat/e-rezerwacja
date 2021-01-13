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

    public function getWeekDay($param): string
    {
        if (is_numeric($param) && $param >= 0 && $param <= 6) {
            return self::DAYS_OF_WEEK[$param]; 
        }
        if (!($param instanceof DateTimeInterface)) {
            $param = new DateTime($param);
        }

        return self::DAYS_OF_WEEK[$param->format('w')];
    }

    public function getIconTag($class) {
        return '<i class="'. $class .'"></i>';
    }
}
