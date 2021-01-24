<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ReservationRepository::class)
 * @ORM\Table(name="reservations")
 */
class Reservation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Room::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $room;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $begin_time;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $end_time;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $origin_id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $requester;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $editor;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $edit_time;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $details;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): self
    {
        $this->room = $room;

        return $this;
    }

    public function getBeginTime(): ?DateTimeImmutable
    {
        return $this->begin_time;
    }

    public function setBeginTime(DateTimeImmutable $begin_time): self
    {
        $this->begin_time = $begin_time;

        return $this;
    }

    public function getEndTime(): ?DateTimeImmutable
    {
        return $this->end_time;
    }

    public function setEndTime(DateTimeImmutable $end_time): self
    {
        $this->end_time = $end_time;

        return $this;
    }

    public function getOriginId(): ?int
    {
        return $this->origin_id;
    }

    public function setOriginId(?int $origin_id): self
    {
        $this->origin_id = $origin_id;

        return $this;
    }

    public function getRequester(): ?User
    {
        return $this->requester;
    }

    public function setRequester(User $requester): self
    {
        $this->requester = $requester;

        return $this;
    }

    public function getEditor(): ?User
    {
        return $this->editor;
    }

    public function setEditor(User $editor): self
    {
        $this->editor = $editor;

        return $this;
    }

    public function getEditTime(): ?DateTimeImmutable
    {
        return $this->edit_time;
    }

    public function setEditTime(DateTimeImmutable $edit_time): self
    {
        $this->edit_time = $edit_time;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }
}
