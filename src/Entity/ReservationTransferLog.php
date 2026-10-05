<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReservationTransferLogRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReservationTransferLogRepository::class)]
#[ORM\Table(name: 'reservation_transfer_log')]
#[ORM\HasLifecycleCallbacks]
class ReservationTransferLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'pk_reservation_transfer_id', type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'fk_reservation_id', referencedColumnName: 'pk_reservation_id', nullable: true, onDelete: 'SET NULL')]
    private ?Reservation $reservation = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'fk_by_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $byUser = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'fk_to_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $toUser = null;

    #[ORM\Column(name: 'transferred_at', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $transferredAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->transferredAt ??= new DateTimeImmutable();
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

    public function getByUser(): ?User
    {
        return $this->byUser;
    }

    public function setByUser(?User $byUser): static
    {
        $this->byUser = $byUser;

        return $this;
    }

    public function getToUser(): ?User
    {
        return $this->toUser;
    }

    public function setToUser(?User $toUser): static
    {
        $this->toUser = $toUser;

        return $this;
    }

    public function getTransferredAt(): ?DateTimeImmutable
    {
        return $this->transferredAt;
    }

    public function setTransferredAt(DateTimeImmutable $transferredAt): static
    {
        $this->transferredAt = $transferredAt;

        return $this;
    }
}
