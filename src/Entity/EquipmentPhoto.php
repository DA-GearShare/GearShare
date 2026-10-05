<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EquipmentPhotoRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EquipmentPhotoRepository::class)]
#[ORM\Table(name: 'equipment_photo')]
#[ORM\HasLifecycleCallbacks]
class EquipmentPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'pk_equipment_photo_id', type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Equipment::class, fetch: 'LAZY', inversedBy: 'photos')]
    #[ORM\JoinColumn(name: 'fk_equipment_id', referencedColumnName: 'pk_equipment_id', nullable: false, onDelete: 'CASCADE')]
    private ?Equipment $equipment = null;

    #[ORM\Column(name: 'url', type: Types::STRING, length: 255)]
    private ?string $url = null;

    #[ORM\Column(name: 'is_main', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isMain = false;

    #[ORM\Column(name: 'uploaded_at', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $uploadedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->uploadedAt ??= new DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEquipment(): ?Equipment
    {
        return $this->equipment;
    }

    public function setEquipment(?Equipment $equipment): static
    {
        $this->equipment = $equipment;

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

    public function isMain(): bool
    {
        return $this->isMain;
    }

    public function setIsMain(bool $isMain): static
    {
        $this->isMain = $isMain;

        return $this;
    }

    public function getUploadedAt(): ?DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }
}
