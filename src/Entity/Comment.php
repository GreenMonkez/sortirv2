<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(targetEntity: Sortie::class,inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sortie $sortie = null;

    #[ORM\Column(type: 'json', nullable: false)]
    private array $reactions;



    public function __construct()
    {
        $this->reactions = [];
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

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

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getSortie(): ?Sortie
    {
        return $this->sortie;
    }

    public function setSortie(?Sortie $sortie): static
    {
        $this->sortie = $sortie;

        return $this;
    }

    /**
     * @return array
     */
    public function getReactions(): array
    {
        return $this->reactions ?? [];
    }


    public function addReaction(string $emoji, User $user): static
    {
        $this->reactions[] = ['emoji' => $emoji, 'user' => $user->getId()];
        return $this;
    }

    public function removeReaction(string $emoji, User $user): static
    {
        $this->reactions = array_filter($this->reactions, function ($reaction) use ($emoji, $user) {
            return !($reaction['emoji'] === $emoji && $reaction['user'] === $user->getId());
        });
        return $this;
    }
}
