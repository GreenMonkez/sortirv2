<?php

namespace App\Entity;

use App\EntityListener\SortieListener;
use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\EntityListeners([SortieListener::class])]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la sortie est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom de la sortie doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom de la sortie ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $nom = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    #[Assert\GreaterThanOrEqual(
        value: 'today',
        message: 'La date de début doit être supérieure ou égale à aujourd\'hui'
    )]
    #[Assert\GreaterThanOrEqual(
        propertyPath: 'limitSortieAt',
        message: 'La date de début doit être supérieure ou égale à la date limite d\'inscription'
    )]
    #[Assert\Type(type: \DateTimeImmutable::class, message: 'La date de début doit être au format valide')]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: 'La durée est obligatoire')]
    #[Assert\GreaterThan(
        value: 0,
        message: 'La durée doit être supérieure à 0'
    )]
    #[Assert\Type(type: 'integer', message: 'La durée doit être un entier')]
    private ?int $duration = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date limite d\'inscription est obligatoire')]
    #[Assert\GreaterThanOrEqual(
        value: 'today',
        message: 'La date limite d\'inscription doit être supérieure ou égale à aujourd\'hui'
    )]
    #[Assert\LessThan(
        propertyPath: 'startAt',
        message: 'La date limite d\'inscription doit être inférieure à la date de début'
    )]
    #[Assert\Type(type: \DateTimeImmutable::class, message: 'La date limite d\'inscription doit être au format valide')]
    private ?\DateTimeImmutable $limitSortieAt = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre de places est obligatoire')]
    #[Assert\Positive(message: 'Le nombre de places doit être positif')]
    #[Assert\Type(type: 'integer', message: 'Le nombre de places doit être un entier')]
    private ?int $limitMembers = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]

    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]

    private ?Etat $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    #[ORM\ManyToOne(inversedBy: 'sorties', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lieu $lieu = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'sorties')]
    private Collection $members;

    #[ORM\ManyToOne(inversedBy: 'sortiesPlannified')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $planner = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de début d\'inscription est obligatoire')]
    #[Assert\GreaterThan(
        value: 'today',
        message: 'La date de début d\'inscription doit être supérieure à aujourd\'hui'
    )]
    #[Assert\LessThan(
        propertyPath: 'limitSortieAt',
        message: 'La date de début d\'inscription doit être inférieure à la date limite d\'inscription'
    )]
    #[Assert\Type(type: \DateTimeImmutable::class, message: 'La date de début d\'inscription doit être au format valide')]

    private ?\DateTimeImmutable $registerStartAt = null;

    #[ORM\Column]
    private ?bool $isArchive = false;

#[ORM\OneToMany(mappedBy: 'sortie', targetEntity: NotificationLog::class, orphanRemoval: true)]
private Collection $notificationLogs;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->notificationLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getLimitSortieAt(): ?\DateTimeImmutable
    {
        return $this->limitSortieAt;
    }

    public function setLimitSortieAt(\DateTimeImmutable $limitSortieAt): static
    {
        $this->limitSortieAt = $limitSortieAt;

        return $this;
    }

    public function getLimitMembers(): ?int
    {
        return $this->limitMembers;
    }

    public function setLimitMembers(int $limitMembers): static
    {
        $this->limitMembers = $limitMembers;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?Etat
    {
        return $this->status;
    }

    public function setStatus(?Etat $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): static
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    #[ORM\PreUpdate]
    public function setModifiedAt(): static
    {
        $this->modifiedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(User $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->addSorty($this);
        }

        return $this;
    }

    public function removeMember(User $member): static
    {
        if ($this->members->removeElement($member)) {
            $member->removeSorty($this);
        }

        return $this;
    }

    public function getPlanner(): ?User
    {
        return $this->planner;
    }

    public function setPlanner(?User $planner): static
    {
        $this->planner = $planner;

        return $this;
    }

    public function getRegisterStartAt(): ?\DateTimeImmutable
    {
        return $this->registerStartAt;
    }

    public function setRegisterStartAt(\DateTimeImmutable $registerStartAt): static
    {
        $this->registerStartAt = $registerStartAt;

        return $this;
    }

    public function isArchive(): ?bool
    {
        return $this->isArchive;
    }

    public function setIsArchive(bool $archive): static
    {
        $this->isArchive = $archive;

        return $this;
    }

    public function getNotificationLogs(): Collection
    {
        return $this->notificationLogs;
    }

    public function addNotificationLog(NotificationLog $notificationLog): static
    {
        if (!$this->notificationLogs->contains($notificationLog)) {
            $this->notificationLogs->add($notificationLog);
            $notificationLog->setSortie($this);
        }

        return $this;
    }

    public function removeNotificationLog(NotificationLog $notificationLog): static
    {
        if ($this->notificationLogs->removeElement($notificationLog)) {
            if ($notificationLog->getSortie() === $this) {
                $notificationLog->setSortie(null);
            }
        }

        return $this;
    }


}
