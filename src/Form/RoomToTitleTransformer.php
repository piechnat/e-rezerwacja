<?php

namespace App\Form;

use App\Entity\Room;
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

    public function transform($room): string
    {
        if (!$room) {
            return '';
        }

        return $room->getTitle();
    }

    public function reverseTransform($roomTitle): ?Room
    {
        if (!$roomTitle) {
            return null;
        }
        $room = $this->roomRepo->findOneBy(['title' => $roomTitle]);

        if (!$room) {
            throw new TransformationFailedException();
        }

        return $room;
    }
}
