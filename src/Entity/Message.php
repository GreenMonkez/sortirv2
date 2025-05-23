<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne(inversedBy: 'sentMessages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'json')]
    private array $reactions = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
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

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    #[ORM\PrePersist]
    public function setSentAt(): static
    {
        $this->sentAt = new \DateTimeImmutable();

        return $this;
    }


    /**
     * @return array
     */
    public function getReactions(): array
    {
        return $this->reactions;
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
