<?php

namespace App\Entity;

use App\Repository\ConstraintRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConstraintRepository::class)
 * @ORM\Table(name="constraints")
 */
class TimeConstraint
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     * @ORM\JoinTable(name="constraint_tag")
     */
    private $exclusions;

    /**
     * @ORM\Column(type="date_immutable")
     */
    private $begin_date;

    /**
     * @ORM\Column(type="date_immutable")
     */
    private $end_date;

    /**
     * @ORM\Column(type="json")
     */
    private $schedule = [];

    public function __construct()
    {
        $this->exclusions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getExclusions(): Collection
    {
        return $this->exclusions;
    }

    public function addExclusion(Tag $exclusion): self
    {
        if (!$this->exclusions->contains($exclusion)) {
            $this->exclusions[] = $exclusion;
        }

        return $this;
    }

    public function removeExclusion(Tag $exclusion): self
    {
        $this->exclusions->removeElement($exclusion);

        return $this;
    }

    public function getBeginDate(): ?\DateTimeImmutable
    {
        return $this->begin_date;
    }

    public function setBeginDate(\DateTimeImmutable $begin_date): self
    {
        $this->begin_date = $begin_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->end_date;
    }

    public function setEndDate(\DateTimeImmutable $end_date): self
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function getSchedule(): ?array
    {
        return $this->schedule;
    }

    public function setSchedule(array $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }
}
