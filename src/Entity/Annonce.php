<?php

namespace App\Entity;

use App\Repository\AnnonceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AnnonceRepository::class)]
class Annonce
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['annonce:info'])]
    private ?int $id = null;
    #[ORM\Column(length: 255)]
    #[Groups(['annonce:info'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['annonce:info'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['annonce:info'])]
    private ?int $remuneration = null;

    #[ORM\Column]
    #[Groups(['annonce:info'])]
    private ?\DateTime $date_active = null;

    #[ORM\Column]
    #[Groups(['annonce:info'])]
    private ?\DateTime $creation_date = null;

    #[ORM\Column(length: 255)]
    #[Groups(['annonce:info'])]
    private ?string $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'annonces')]
    #[Groups(['user:info'])]
    private ?User $user_annonce = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getRemuneration(): ?int
    {
        return $this->remuneration;
    }

    public function setRemuneration(int $remuneration): static
    {
        $this->remuneration = $remuneration;

        return $this;
    }

    public function getDateActive(): ?\DateTime
    {
        return $this->date_active;
    }

    public function setDateActive(\DateTime $dateActive): static
    {
        $this->date_active = $dateActive;

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    public function setCreationDate(\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getUserAnnonce(): ?User
    {
        return $this->user_annonce;
    }

    public function setUserAnnonce(?User $user_annonce): static
    {
        $this->user_annonce = $user_annonce;

        return $this;
    }

}
