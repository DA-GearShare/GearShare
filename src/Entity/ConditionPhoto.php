<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ConditionPhase;
use App\Repository\ConditionPhotoRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ConditionPhotoRepository::class)]
#[ORM\Table(name: 'condition_photo')]
#[ORM\HasLifecycleCallbacks]
class ConditionPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'pk_condition_photo_id', type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class, fetch: 'LAZY', inversedBy: 'conditionPhotos')]
    #[ORM\JoinColumn(name: 'fk_reservation_id', referencedColumnName: 'pk_reservation_id', nullable: false, onDelete: 'CASCADE')]
    private ?Reservation $reservation = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'fk_take_by_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $takenBy = null;

    #[ORM\Column(name: 'phase', type: Types::STRING, length: 255, enumType: ConditionPhase::class)]
    private ?ConditionPhase $phase = null;

    #[ORM\Column(name: 'url', type: Types::STRING, length: 255)]
    private ?string $url = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt ??= new DateTimeImmutable();
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

    public function getTakenBy(): ?User
    {
        return $this->takenBy;
    }

    public function setTakenBy(?User $takenBy): static
    {
        $this->takenBy = $takenBy;

        return $this;
    }

    public function getPhase(): ?ConditionPhase
    {
        return $this->phase;
    }

    public function setPhase(ConditionPhase $phase): static
    {
        $this->phase = $phase;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

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
}
