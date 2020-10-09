<?php

namespace App\Form;

use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RoomToTitleTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
        $room = $this->entityManager
            ->getRepository(Room::class)->findOneBy(['title' => $roomTitle]);

        if (null === $room) {
            throw new TransformationFailedException();
        }

        return $room;
    }
}
