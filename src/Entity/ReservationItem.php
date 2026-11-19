<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ReservationItemStatus;
use App\Repository\ReservationItemRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReservationItemRepository::class)]
#[ORM\Table(name: 'reservation_item')]
class ReservationItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'pk_reservation_item_id', type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class, fetch: 'LAZY', inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'fk_reservation_id', referencedColumnName: 'pk_reservation_id', nullable: false, onDelete: 'CASCADE')]
    private ?Reservation $reservation = null;

    #[ORM\ManyToOne(targetEntity: Equipment::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'fk_requested_equipment_id', referencedColumnName: 'pk_equipment_id', nullable: true, onDelete: 'SET NULL')]
    private ?Equipment $requestedEquipment = null;

    #[ORM\ManyToOne(targetEntity: EquipmentPool::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'fk_requested_pool_id', referencedColumnName: 'pk_pool_id', nullable: true, onDelete: 'SET NULL')]
    private ?EquipmentPool $requestedPool = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 255, enumType: ReservationItemStatus::class)]
    private ?ReservationItemStatus $status = null;

    #[ORM\Column(name: 'issued_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $issuedAt = null;

    #[ORM\Column(name: 'returned_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $returnedAt = null;

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

    public function getRequestedEquipment(): ?Equipment
    {
        return $this->requestedEquipment;
    }

    public function setRequestedEquipment(?Equipment $requestedEquipment): static
    {
        $this->requestedEquipment = $requestedEquipment;

        return $this;
    }

    public function getRequestedPool(): ?EquipmentPool
    {
        return $this->requestedPool;
    }

    public function setRequestedPool(?EquipmentPool $requestedPool): static
    {
        $this->requestedPool = $requestedPool;

        return $this;
    }

    public function getStatus(): ?ReservationItemStatus
    {
        return $this->status;
    }

    public function setStatus(ReservationItemStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getIssuedAt(): ?DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(?DateTimeImmutable $issuedAt): static
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getReturnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function setReturnedAt(?DateTimeImmutable $returnedAt): static
    {
        $this->returnedAt = $returnedAt;

        return $this;
    }
}
