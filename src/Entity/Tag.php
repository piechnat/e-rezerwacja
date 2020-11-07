<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=TagRepository::class)
 * @ORM\Table(name="tags")
 * @UniqueEntity(fields={"name"}, groups={"tag"})
 */
class Tag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $search = false;

    /**
     * @ORM\Column(type="smallint")
     */
    private $access = 0;

    /**
     * @ORM\ManyToMany(targetEntity=Room::class, inversedBy="tags")
     */
    private $rooms;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="tags")
     */
    private $users;

    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name; 
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

    public function getSearch(): ?bool
    {
        return $this->search;
    }

    public function setSearch(bool $search): self
    {
        $this->search = $search;

        return $this;
    }

    public function getAccess(): ?int
    {
        return $this->access;
    }

    public function setAccess(int $access): self
    {
        $this->access = $access;

        return $this;
    }

    /**
     * @return Collection|Room[]
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Room $room): self
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms[] = $room;
        }

        return $this;
    }

    public function removeRoom(Room $room): self
    {
        $this->rooms->removeElement($room);

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }
}
