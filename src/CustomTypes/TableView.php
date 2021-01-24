<?php

namespace App\CustomTypes;

use App\Repository\ConstraintRepository as CstrRepo;
use DateTimeImmutable;

class TableView
{
    public $headers;
    public $columns;
    public $meta;

    public function __construct(array &$headers, array &$columns)
    {
        if (count($headers) !== count($columns)) {
            throw new \InvalidArgumentException();
        }
        $this->headers = &$headers;
        $this->columns = &$columns;
        $this->setTableBoundaries();
        $this->addEmptySpaces();
        $this->setCssDimensions();
    }

    private function setTableBoundaries()
    {
        $min = $max = '12:00';
        foreach ($this->headers as $key => $header) {
            if (CstrRepo::VALID_SCHEDULE === $header['hours']['state']) {
                $min = min($min, $header['hours']['begin_time']->format('H:i'));
                $max = max($max, $header['hours']['end_time']->format('H:i'));
            }
            if (count($this->columns[$key]) > 0) {
                $dayNumber = $header['date']->format('j');
                $rsvn = reset($this->columns[$key]);
                if ($rsvn['begin_time']->format('j') !== $dayNumber) {
                    $min = '00:00';
                } else {
                    $min = min($min, $rsvn['begin_time']->format('H:i'));
                }
                $rsvn = end($this->columns[$key]);
                if ($rsvn['end_time']->format('j') !== $dayNumber) {
                    $max = '24:00';
                } else {
                    $max = max($max, $rsvn['end_time']->format('H:i'));
                }
            }
            if ('00:00' === $min && '24:00' === $max) { //???
                break;
            }
        }
        $hoursList = [];
        $end = (int) substr($max, 0, 2);
        if ('00' === substr($max, 3, 2)) {
            --$end;
        }
        for ($pos = ((int) substr($min, 0, 2)) + 1; $pos <= $end; ++$pos) {
            $hoursList[] = sprintf('%02d:00', $pos);
        }
        $this->meta = ['min_hour' => $min, 'max_hour' => $max, 'hours_list' => $hoursList];
    }

    private function addEmptySpaces()
    {
        $now = new DateTimeImmutable();
        $today = $now->modify('today');
        foreach ($this->headers as $key => $header) {
            if (
                $header['date'] < $today
                || CstrRepo::VALID_SCHEDULE !== $header['hours']['state']
            ) {
                continue;
            }
            $newItems = [];
            $beginTime = max($now, $header['hours']['begin_time']);
            foreach ($this->columns[$key] as $rsvn) {
                if ($rsvn['begin_time'] >= $beginTime->modify('+15 minutes')) {
                    $newItems[] = [
                        'id' => 0,
                        'begin_time' => $beginTime,
                        'end_time' => $rsvn['begin_time'],
                    ];
                }
                $beginTime = max($now, $rsvn['end_time']);
                $newItems[] = $rsvn;
            }
            $endTime = $header['hours']['end_time'];
            if ($beginTime <= $endTime->modify('-15 minutes')) {
                $newItems[] = [
                    'id' => 0,
                    'begin_time' => $beginTime,
                    'end_time' => $endTime,
                ];
            }
            $this->columns[$key] = $newItems;
        }
    }

    private function setCssDimensions()
    {
        foreach ($this->columns as $key => &$items) {
            $base = substr($this->meta['min_hour'], 0, 2);
            $base = $this->headers[$key]['date']->modify("{$base}:00");
            foreach ($items as &$rsvn) {
                $rsvn['css_top'] = self::secDiff($base, $rsvn['begin_time']) / 960;
                $rsvn['css_height'] = self::secDiff($rsvn['begin_time'], $rsvn['end_time']) / 960;
            }
        }
    }

    private static function secDiff(\DateTimeInterface $d1, \DateTimeInterface $d2): int
    {
        return ($d2->getTimestamp() - $d1->getTimestamp()) +
               ($d2->getOffset() - $d1->getOffset()); // ignore daylight savings time
    }
}
