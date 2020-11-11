<?php

namespace App\Entity;

use App\CustomTypes\Lang;
use App\CustomTypes\UserLevel;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="users")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fullname;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=2)
     */
    private $lang = Lang::PL;

    /**
     * @ORM\Column(type="string", length=24)
     */
    private $access;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class, mappedBy="users")
     */
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->email; 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function getTitle()
    {
        return "{$this->fullname} <{$this->email}>"; 
    }

    public function setFullname(string $fullname): self
    {
        $this->fullname = trim(preg_replace('/\s+/', ' ', $fullname));

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getAccess(): ?string
    {
        return $this->access;
    }

    public function setAccess(string $access): self
    {
        $this->access = UserLevel::valid($access);

        return $this;
    }

    public function getAccessLevel(): int
    {
        return UserLevel::getIndex($this->access);
    }

    public function getAccessName(): string
    {
        return UserLevel::getValue($this->access);
    }

    public function getRoles(): ?array
    {
        $roles = $this->roles;
        $roles[] = $this->access;
        $roles[] = UserLevel::USER;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
            $tag->addUser($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeUser($this);
        }

        return $this;
    }

    public function getUsername()
    {
        return $this->email;
    }

    public function getPassword()
    {
        // for "remember me" cookie hash
        return $this->fullname;
    }

    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {
        return null;
    }
}
