<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Il y a déjà un compte avec cet email')]
#[UniqueEntity(fields: ['pseudo'], message: 'Il y a déjà un compte avec ce pseudo')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Un email est requis')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas un email valide')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9._%+-]+@campus-eni\.fr$/',
        message: 'L\'email doit appartenir au domaine @campus-eni.fr',
    )]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\Length(
        min: 6,
        max: 255,
        minMessage: 'Votre mot de passe doit faire au moins {{ limit }} caractères',
        maxMessage: 'Votre mot de passe ne peut pas faire plus de {{ limit }} caractères',
    )]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Votre nom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Votre nom ne peut pas faire plus de {{ limit }} caractères',
    )]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est requis')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Votre prénom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Votre prénom ne peut pas faire plus de {{ limit }} caractères',
    )]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\ManyToMany(targetEntity: Sortie::class, inversedBy: 'members')]
    private Collection $sorties;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'planner')]
    private Collection $sortiesPlannified;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Un pseudo est requis')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Votre pseudo doit faire au moins {{ limit }} caractères',
        maxMessage: 'Votre pseudo ne peut pas faire plus de {{ limit }} caractères',
    )]
    private ?string $pseudo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

   #[ORM\OneToMany(mappedBy: 'user', targetEntity: NotificationLog::class, orphanRemoval: true)]
    private Collection $notificationLogs;

   /**    * @var Collection<int, Conversation>
    */
   #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: 'participants')]
   private Collection $conversations;

   /**
    * @var Collection<int, Message>
    */
   #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender')]
   private Collection $sentMessages;

   /**
    * @var Collection<int, Conversation>
    */
   #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'creator')]
   private Collection $createdConversations;

    /**
    * @var Collection<int, Group>
    */
   #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'owner')]
   private Collection $privateGroups;

   /**
    * @var Collection<int, Group>
    */
   #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'teammate')]
   private Collection $memberGroupPrivate;

   /**
    * @var Collection<int, Comment>
    */
   #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'author')]
   private Collection $comments;



    public function __construct()
    {
        $this->sorties = new ArrayCollection();
        $this->sortiesPlannified = new ArrayCollection();
        $this->notificationLogs = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->sentMessages = new ArrayCollection();
        $this->createdConversations = new ArrayCollection();
        $this->privateGroups = new ArrayCollection();
        $this->memberGroupPrivate = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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
        }

        return $this;
    }

    public function removeSorty(Sortie $sorty): static
    {
        $this->sorties->removeElement($sorty);

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSortiesPlannified(): Collection
    {
        return $this->sortiesPlannified;
    }

    public function addSortiesPlannified(Sortie $sortiesPlannified): static
    {
        if (!$this->sortiesPlannified->contains($sortiesPlannified)) {
            $this->sortiesPlannified->add($sortiesPlannified);
            $sortiesPlannified->setPlanner($this);
        }

        return $this;
    }

    public function removeSortiesPlannified(Sortie $sortiesPlannified): static
    {
        if ($this->sortiesPlannified->removeElement($sortiesPlannified)) {
            // set the owning side to null (unless already changed)
            if ($sortiesPlannified->getPlanner() === $this) {
                $sortiesPlannified->setPlanner(null);
            }
        }

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

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
           $notificationLog->setUser($this);
       }

       return $this;
   }

   public function removeNotificationLog(NotificationLog $notificationLog): static
   {
       if ($this->notificationLogs->removeElement($notificationLog)) {
           if ($notificationLog->getUser() === $this) {
               $notificationLog->setUser(null);
           }
       }

       return $this;
   }

   /**
    * @return Collection<int, Conversation>
    */
   public function getConversations(): Collection
   {
       return $this->conversations;
   }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->addParticipant($this);
        }

        return $this;
    }

    /**
    * @return Collection<int, Group>
    */
   public function getPrivateGroups(): Collection
   {
       return $this->privateGroups;
   }

   public function addPrivateGroup(Group $privateGroup): static
   {
       if (!$this->privateGroups->contains($privateGroup)) {
           $this->privateGroups->add($privateGroup);
           $privateGroup->setOwner($this);
       }

       return $this;
   }

   public function removeConversation(Conversation $conversation): static
   {
       if ($this->conversations->removeElement($conversation)) {
           $conversation->removeParticipant($this);
       }

       return $this;
   }

   /**
    * @return Collection<int, Message>
    */
   public function getSentMessages(): Collection
   {
       return $this->sentMessages;
   }

   public function addSentMessage(Message $sentMessage): static
   {
       if (!$this->sentMessages->contains($sentMessage)) {
           $this->sentMessages->add($sentMessage);
           $sentMessage->setSender($this);
       }

       return $this;
   }

    public function removeSentMessage(Message $sentMessage): static
    {
        if ($this->sentMessages->removeElement($sentMessage)) {
            // set the owning side to null (unless already changed)
            if ($sentMessage->getSender() === $this) {
                $sentMessage->setSender(null);
            }
        }
        return $this;
    }
   public function removePrivateGroup(Group $privateGroup): static
   {
       if ($this->privateGroups->removeElement($privateGroup)) {
           // set the owning side to null (unless already changed)
           if ($privateGroup->getOwner() === $this) {
               $privateGroup->setOwner(null);
           }
       }

       return $this;
   }

    /**
    * @return Collection<int, Group>
    */
   public function getMemberGroupPrivate(): Collection
   {
       return $this->memberGroupPrivate;
   }

   public function addMemberGroupPrivate(Group $memberGroupPrivate): static
   {
       if (!$this->memberGroupPrivate->contains($memberGroupPrivate)) {
           $this->memberGroupPrivate->add($memberGroupPrivate);
           $memberGroupPrivate->addTeammate($this);
       }

       return $this;
   }

   public function removeMemberGroupPrivate(Group $memberGroupPrivate): static
   {
       if ($this->memberGroupPrivate->removeElement($memberGroupPrivate)) {
           $memberGroupPrivate->removeTeammate($this);
       }

       return $this;
   }

   /**
    * @return Collection<int, Comment>
    */
   public function getComments(): Collection
   {
       return $this->comments;
   }

   public function addComment(Comment $comment): static
   {
       if (!$this->comments->contains($comment)) {
           $this->comments->add($comment);
           $comment->setAuthor($this);
       }

       return $this;
   }

   public function removeComment(Comment $comment): static
   {
       if ($this->comments->removeElement($comment)) {
           // set the owning side to null (unless already changed)
           if ($comment->getAuthor() === $this) {
               $comment->setAuthor(null);
           }
       }

       return $this;
   }
}
