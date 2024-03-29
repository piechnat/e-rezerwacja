<?php

namespace App\Entity;

use App\Repository\RequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RequestRepository::class)
 * @ORM\Table(name="requests")
 */
class Request
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $requester;

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
     * @ORM\Column(type="datetime_immutable")
     */
    private $create_time;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $details;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $error;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $reservation_id;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): self
    {
        $this->room = $room;

        return $this;
    }

    public function getBeginTime(): ?\DateTimeImmutable
    {
        return $this->begin_time;
    }

    public function setBeginTime(\DateTimeImmutable $begin_time): self
    {
        $this->begin_time = $begin_time;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->end_time;
    }

    public function setEndTime(\DateTimeImmutable $end_time): self
    {
        $this->end_time = $end_time;

        return $this;
    }

    public function getCreateTime(): ?\DateTimeImmutable
    {
        return $this->create_time;
    }

    public function setCreateTime(\DateTimeImmutable $create_time): self
    {
        $this->create_time = $create_time;

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

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getReservationId(): ?int
    {
        return $this->reservation_id;
    }

    public function setReservationId(?int $reservation_id): self
    {
        $this->reservation_id = $reservation_id;

        return $this;
    }
}
