<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
#[ORM\HasLifecycleCallbacks]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'pk_reservation_id', type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER', inversedBy: 'requestedReservations')]
    #[ORM\JoinColumn(name: 'fk_requested_by_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $requestedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER', inversedBy: 'responsibleReservations')]
    #[ORM\JoinColumn(name: 'fk_responsible_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $responsibleUser = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 255, enumType: ReservationStatus::class)]
    private ?ReservationStatus $status = null;

    #[ORM\Column(name: 'start_datetime', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $startDatetime = null;

    #[ORM\Column(name: 'end_datetime', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $endDatetime = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    /** @var Collection<int, ReservationItem> */
    #[ORM\OneToMany(targetEntity: ReservationItem::class, mappedBy: 'reservation', cascade: ['persist', 'remove'], fetch: 'LAZY', orphanRemoval: true)]
    private Collection $items;

    /** @var Collection<int, ConditionPhoto> */
    #[ORM\OneToMany(targetEntity: ConditionPhoto::class, mappedBy: 'reservation', cascade: ['persist', 'remove'], fetch: 'LAZY', orphanRemoval: true)]
    private Collection $conditionPhotos;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->conditionPhotos = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): static
    {
        $this->requestedBy = $requestedBy;

        return $this;
    }

    public function getResponsibleUser(): ?User
    {
        return $this->responsibleUser;
    }

    public function setResponsibleUser(?User $responsibleUser): static
    {
        $this->responsibleUser = $responsibleUser;

        return $this;
    }

    public function getStatus(): ?ReservationStatus
    {
        return $this->status;
    }

    public function setStatus(ReservationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartDatetime(): ?DateTimeImmutable
    {
        return $this->startDatetime;
    }

    public function setStartDatetime(DateTimeImmutable $startDatetime): static
    {
        $this->startDatetime = $startDatetime;

        return $this;
    }

    public function getEndDatetime(): ?DateTimeImmutable
    {
        return $this->endDatetime;
    }

    public function setEndDatetime(DateTimeImmutable $endDatetime): static
    {
        $this->endDatetime = $endDatetime;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return Collection<int, ReservationItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ReservationItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setReservation($this);
        }

        return $this;
    }

    public function removeItem(ReservationItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getReservation() === $this) {
                $item->setReservation(null); // orphanRemoval löscht das Item
            }
        }

        return $this;
    }

    /** @return Collection<int, ConditionPhoto> */
    public function getConditionPhotos(): Collection
    {
        return $this->conditionPhotos;
    }

    public function addConditionPhoto(ConditionPhoto $photo): static
    {
        if (!$this->conditionPhotos->contains($photo)) {
            $this->conditionPhotos->add($photo);
            $photo->setReservation($this);
        }

        return $this;
    }

    public function removeConditionPhoto(ConditionPhoto $photo): static
    {
        if ($this->conditionPhotos->removeElement($photo)) {
            if ($photo->getReservation() === $this) {
                $photo->setReservation(null);
            }
        }

        return $this;
    }
}
