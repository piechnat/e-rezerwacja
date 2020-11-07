<?php

namespace App\Form;

use App\Repository\RoomRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RoomToTitleTransformer implements DataTransformerInterface
{
    private $roomRepo;

    public function __construct(RoomRepository $roomRepo)
    {
        $this->roomRepo = $roomRepo;
    }

    public function transform($room)
    {
        if (null === $room) {
            return '';
        }

        return $room->getTitle();
    }

    public function reverseTransform($roomTitle)
    {
        if (!$roomTitle) {
            return;
        }
        $room = $this->roomRepo->findOneBy(['title' => $roomTitle]);

        if (null === $room) {
            throw new TransformationFailedException();
        }

        return $room;
    }
}
