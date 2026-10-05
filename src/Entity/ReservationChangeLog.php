<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReservationChangeLogRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReservationChangeLogRepository::class)]
#[ORM\Table(name: 'reservation_change_log')]
#[ORM\HasLifecycleCallbacks]
class ReservationChangeLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'pk_reservation_change_id', type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'fk_reservation_id', referencedColumnName: 'pk_reservation_id', nullable: true, onDelete: 'SET NULL')]
    private ?Reservation $reservation = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'fk_changed_by_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $changedBy = null;

    #[ORM\Column(name: 'old_start_datetime', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $oldStartDatetime = null;

    #[ORM\Column(name: 'old_end_datetime', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $oldEndDatetime = null;

    #[ORM\Column(name: 'new_start_datetime', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $newStartDatetime = null;

    #[ORM\Column(name: 'new_end_datetime', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $newEndDatetime = null;

    #[ORM\Column(name: 'changed_at', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $changedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->changedAt ??= new DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): static
    {
        $this->changedBy = $changedBy;

        return $this;
    }

    public function getOldStartDatetime(): ?DateTimeImmutable
    {
        return $this->oldStartDatetime;
    }

    public function setOldStartDatetime(DateTimeImmutable $oldStartDatetime): static
    {
        $this->oldStartDatetime = $oldStartDatetime;

        return $this;
    }

    public function getOldEndDatetime(): ?DateTimeImmutable
    {
        return $this->oldEndDatetime;
    }

    public function setOldEndDatetime(DateTimeImmutable $oldEndDatetime): static
    {
        $this->oldEndDatetime = $oldEndDatetime;

        return $this;
    }

    public function getNewStartDatetime(): ?DateTimeImmutable
    {
        return $this->newStartDatetime;
    }

    public function setNewStartDatetime(DateTimeImmutable $newStartDatetime): static
    {
        $this->newStartDatetime = $newStartDatetime;

        return $this;
    }

    public function getNewEndDatetime(): ?DateTimeImmutable
    {
        return $this->newEndDatetime;
    }

    public function setNewEndDatetime(DateTimeImmutable $newEndDatetime): static
    {
        $this->newEndDatetime = $newEndDatetime;

        return $this;
    }

    public function getChangedAt(): ?DateTimeImmutable
    {
        return $this->changedAt;
    }

    public function setChangedAt(DateTimeImmutable $changedAt): static
    {
        $this->changedAt = $changedAt;

        return $this;
    }
}
