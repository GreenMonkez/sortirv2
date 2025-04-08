<?php

namespace App\Entity;

use App\Repository\LieuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LieuRepository::class)]
class Lieu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du lieu est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom du lieu doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom du lieu ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La rue est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'La rue doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La rue ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $street = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(type: 'float', message: 'La latitude doit être un nombre valide')]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(type: 'float', message: 'La longitude doit être un nombre valide')]
    private ?float $longitude = null;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'lieu')]
    private Collection $sorties;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La ville est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'La ville doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le code postal est obligatoire')]
    #[Assert\Regex(
        pattern: '/^\d{5}$/',
        message: 'Le code postal doit contenir 5 chiffres'
    )]
    #[Assert\Length(
        min: 5,
        max: 5,
        minMessage: 'Le code postal doit contenir exactement {{ limit }} chiffres',
        maxMessage: 'Le code postal doit contenir exactement {{ limit }} chiffres'
    )]

    private ?string $postaleCode = null;

    public function __construct()
    {
        $this->sorties = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSorties(): Collection
    {
        return $this->sorties;
    }

    public function addSorty(Sortie $sorty): static
    {
        if (!$this->sorties->contains($sorty)) {
            $this->sorties->add($sorty);
            $sorty->setLieu($this);
        }

        return $this;
    }

    public function removeSorty(Sortie $sorty): static
    {
        if ($this->sorties->removeElement($sorty)) {
            // set the owning side to null (unless already changed)
            if ($sorty->getLieu() === $this) {
                $sorty->setLieu(null);
            }
        }

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPostaleCode(): ?string
    {
        return $this->postaleCode;
    }

    public function setPostaleCode(string $postaleCode): static
    {
        $this->postaleCode = $postaleCode;

        return $this;
    }

}
