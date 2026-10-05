<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'user_id', type: UuidType::NAME)]
    private ?Uuid $userId = null;
    #[ORM\Column(name: 'display_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(name: 'is_admin', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isAdmin = false;

    #[ORM\Column(name: 'is_teacher', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isTeacher = false;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'requestedBy', fetch: 'EXTRA_LAZY')]
    private Collection $requestedReservations;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'responsibleUser', fetch: 'EXTRA_LAZY')]
    private Collection $responsibleReservations;

    /** @var Collection<int, OpeningHoursRecurring> */
    #[ORM\OneToMany(targetEntity: OpeningHoursRecurring::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'], fetch: 'LAZY', orphanRemoval: true)]
    private Collection $openingHoursRecurring;

    /** @var Collection<int, OpeningHoursException> */
    #[ORM\OneToMany(targetEntity: OpeningHoursException::class, mappedBy: 'createdBy', cascade: ['persist', 'remove'], fetch: 'LAZY', orphanRemoval: true)]
    private Collection $openingHoursExceptions;

    public function __construct(
        #[ORM\Column(name: 'oauth_user_id', type: Types::STRING, length: 64, unique: true)]
        private readonly string $oAuthUserId,

        #[ORM\Column(name: 'email', type: Types::STRING, length: 255, unique: true)]
        private string          $email,
    )
    {
        $this->requestedReservations = new ArrayCollection();
        $this->responsibleReservations = new ArrayCollection();
        $this->openingHoursRecurring = new ArrayCollection();
        $this->openingHoursExceptions = new ArrayCollection();
    }

    public function getOAuthUserId(): string
    {
        return $this->oAuthUserId;
    }

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): static
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function isTeacher(): bool
    {
        return $this->isTeacher;
    }

    public function setIsTeacher(bool $isTeacher): static
    {
        $this->isTeacher = $isTeacher;

        return $this;
    }

    // ---- UserInterface -------------------------------------------------

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if ($this->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }

        if ($this->isTeacher) {
            $roles[] = 'ROLE_TEACHER';
        }

        return $roles;
    }

    public function eraseCredentials(): void
    {
        // Keine Passwörter / temporären Credentials gespeichert (OAuth-Login).
    }

    // ---- Collections ---------------------------------------------------

    /** @return Collection<int, Reservation> */
    public function getRequestedReservations(): Collection
    {
        return $this->requestedReservations;
    }

    public function addRequestedReservation(Reservation $reservation): static
    {
        if (!$this->requestedReservations->contains($reservation)) {
            $this->requestedReservations->add($reservation);
            $reservation->setRequestedBy($this);
        }

        return $this;
    }

    public function removeRequestedReservation(Reservation $reservation): static
    {
        if ($this->requestedReservations->removeElement($reservation)) {
            if ($reservation->getRequestedBy() === $this) {
                $reservation->setRequestedBy(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, Reservation> */
    public function getResponsibleReservations(): Collection
    {
        return $this->responsibleReservations;
    }

    public function addResponsibleReservation(Reservation $reservation): static
    {
        if (!$this->responsibleReservations->contains($reservation)) {
            $this->responsibleReservations->add($reservation);
            $reservation->setResponsibleUser($this);
        }

        return $this;
    }

    public function removeResponsibleReservation(Reservation $reservation): static
    {
        if ($this->responsibleReservations->removeElement($reservation)) {
            if ($reservation->getResponsibleUser() === $this) {
                $reservation->setResponsibleUser(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, OpeningHoursRecurring> */
    public function getOpeningHoursRecurring(): Collection
    {
        return $this->openingHoursRecurring;
    }

    public function addOpeningHoursRecurring(OpeningHoursRecurring $openingHours): static
    {
        if (!$this->openingHoursRecurring->contains($openingHours)) {
            $this->openingHoursRecurring->add($openingHours);
            $openingHours->setCreatedBy($this);
        }

        return $this;
    }

    public function removeOpeningHoursRecurring(OpeningHoursRecurring $openingHours): static
    {
        if ($this->openingHoursRecurring->removeElement($openingHours)) {
            if ($openingHours->getCreatedBy() === $this) {
                $openingHours->setCreatedBy(null); // orphanRemoval löscht den Eintrag
            }
        }

        return $this;
    }

    /** @return Collection<int, OpeningHoursException> */
    public function getOpeningHoursExceptions(): Collection
    {
        return $this->openingHoursExceptions;
    }

    public function addOpeningHoursException(OpeningHoursException $exception): static
    {
        if (!$this->openingHoursExceptions->contains($exception)) {
            $this->openingHoursExceptions->add($exception);
            $exception->setCreatedBy($this);
        }

        return $this;
    }

    public function removeOpeningHoursException(OpeningHoursException $exception): static
    {
        if ($this->openingHoursExceptions->removeElement($exception)) {
            if ($exception->getCreatedBy() === $this) {
                $exception->setCreatedBy(null);
            }
        }

        return $this;
    }
}
