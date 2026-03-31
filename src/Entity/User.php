<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:info'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:info'])]
    private ?string $Nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:info'])]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:info'])]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['user:info'])]
    private ?string $photoProfil = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:info'])]
    private array $roles = [];

    #[ORM\Column]
    #[Groups(['user:info'])]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:info'])]
    private ?string $token = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:info'])]
    private ?\DateTimeImmutable $tokenCreatedAt = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:info'])]
    private ?string $prenom = null;

    /**
     * @var Collection<int, Annonce>
     */
    #[ORM\OneToMany(targetEntity: Annonce::class, mappedBy: 'user_annonce')]
    #[Groups(['user:info'])]
    private Collection $annonces;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $role = [];

    public function __construct()
    {
        $this->annonces = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(string $Nom): static
    {
        $this->Nom = $Nom;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photoProfil;
    }

    public function setPhotoProfil(?string $photoProfil): static
    {
        $this->photoProfil = $photoProfil;

        return $this;
    }

    public function getRole(): array
    {
        return $this->roles;
    }

    public function getRoles(): array
    {
        return $this->roles;
        $this->roles[] = "ROLE_USER";

        return array_unique($roles);
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenCreatedAt(): ?\DateTimeImmutable
    {
        return $this->tokenCreatedAt;
    }

    public function setTokenCreatedAt(?\DateTimeImmutable $tokenCreatedAt): static
    {
        $this->tokenCreatedAt = $tokenCreatedAt;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * @return Collection<int, Annonce>
     */
    public function getAnnonces(): Collection
    {
        return $this->annonces;
    }

    public function addAnnonce(Annonce $annonce): static
    {
        if (!$this->annonces->contains($annonce)) {
            $this->annonces->add($annonce);
            $annonce->setUserAnnonce($this);
        }

        return $this;
    }

    public function removeAnnonce(Annonce $annonce): static
    {
        if ($this->annonces->removeElement($annonce)) {
            // set the owning side to null (unless already changed)
            if ($annonce->getUserAnnonce() === $this) {
                $annonce->setUserAnnonce(null);
            }
        }

        return $this;
    }
}
