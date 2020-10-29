<?php

namespace App\CustomTypes;

use DateTimeImmutable;

class TableView
{
    private const MIN = 6;
    private const MAX = 22;

    public $headers = [];
    public $columns = [];
    public $meta = [];

    public function __construct(array &$headers, array &$columns)
    {
        if (count($headers) !== count($columns)) {
            throw new \InvalidArgumentException();
        }
        $this->headers = $headers;
        $this->columns = $columns;

        // set hours boundaries
        $min = $this::MIN;
        $max = $this::MAX;
        foreach ($this->columns as $key => &$items) {
            if (0 === count($items)) {
                continue;
            }
            $day = $this->headers[$key]['date']->format('j');
            $rsvn = reset($items);
            if ($rsvn['begin_time']->format('j') !== $day) {
                $min = 0;
            } else {
                $min = min($min, (int) $rsvn['begin_time']->format('G'));
            }
            $rsvn = end($items);
            if ($rsvn['end_time']->format('j') !== $day) {
                $max = 24;
            } else {
                $max = max($max, ((int) $rsvn['end_time']->format('G')) + (
                    '00' !== $rsvn['end_time']->format('i') ? 1 : 0
                ));
            }
            if (0 === $min && 24 === $max) {
                break;
            }
        }
        $this->meta = ['min_hour' => $min, 'max_hour' => $max];

        $this->addEmptySpaces();

        // set css dimensions
        foreach ($this->columns as $key => &$items) {
            $base = $this->headers[$key]['date']->setTime($this->meta['min_hour'], 0);
            foreach ($items as &$rsvn) {
                $rsvn['css_top'] = $this->secDiff($base, $rsvn['begin_time']) / 960;
                $rsvn['css_height'] = $this->secDiff($rsvn['begin_time'], $rsvn['end_time']) / 960;
            }
        }
    }

    private function secDiff(\DateTimeInterface $d1, \DateTimeInterface $d2): int
    {
        return ($d2->getTimestamp() - $d1->getTimestamp()) +
               ($d2->getOffset() - $d1->getOffset()); // ignore daylight savings time
    }

    private function addEmptySpaces()
    {
        $now = new DateTimeImmutable();
        foreach ($this->columns as $key => &$items) {
            $date = $this->headers[$key]['date'];
            if ($date < $now->modify('today')) {
                continue;
            }
            $newItems = [];
            $beginTime = max($now, $date->setTime($this->meta['min_hour'], 0));
            foreach ($items as &$rsvn) {
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
            $endTime = $date->setTime($this->meta['max_hour'], 0);
            if ($beginTime <= $endTime->modify('-15 minutes')) {
                $newItems[] = [
                    'id' => 0,
                    'begin_time' => $beginTime,
                    'end_time' => $endTime,
                ];
            }
            $items = $newItems;
        }
    }
}
