<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EquipmentRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
#[ORM\Table(name: 'equipment')]
#[ORM\HasLifecycleCallbacks]
class Equipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'pk_equipment_id', type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: EquipmentPool::class, fetch: 'LAZY', inversedBy: 'equipment')]
    #[ORM\JoinColumn(name: 'fk_pool_id', referencedColumnName: 'pk_pool_id', nullable: true, onDelete: 'SET NULL')]
    private ?EquipmentPool $pool = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(name: 'location', type: Types::STRING, length: 255)]
    private ?string $location = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255)]
    private ?string $type = null;

    #[ORM\Column(name: 'model', type: Types::STRING, length: 255)]
    private ?string $model = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 255, unique: true, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    /** @var Collection<int, EquipmentPhoto> */
    #[ORM\OneToMany(targetEntity: EquipmentPhoto::class, mappedBy: 'equipment', cascade: ['persist', 'remove'], fetch: 'LAZY', orphanRemoval: true)]
    private Collection $photos;

    /**
     * Owning side der Self-Referencing-Relation (Tabelle equipment_accessory).
     * Bewusst KEIN cascade remove: Zubehör sind eigenständige Geräte.
     *
     * @var Collection<int, Equipment>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'accessoryOf', fetch: 'LAZY')]
    #[ORM\JoinTable(name: 'equipment_accessory')]
    #[ORM\JoinColumn(name: 'pk_equipment_id', referencedColumnName: 'pk_equipment_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'pk_accessory_equipment_id', referencedColumnName: 'pk_equipment_id', onDelete: 'CASCADE')]
    private Collection $accessories;

    /**
     * Inverse side: Geräte, für die dieses Gerät Zubehör ist.
     *
     * @var Collection<int, Equipment>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'accessories', fetch: 'LAZY')]
    private Collection $accessoryOf;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->accessories = new ArrayCollection();
        $this->accessoryOf = new ArrayCollection();
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

    public function getPool(): ?EquipmentPool
    {
        return $this->pool;
    }

    public function setPool(?EquipmentPool $pool): static
    {
        $this->pool = $pool;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

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

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    /** @return Collection<int, EquipmentPhoto> */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(EquipmentPhoto $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setEquipment($this);
        }

        return $this;
    }

    public function removePhoto(EquipmentPhoto $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            if ($photo->getEquipment() === $this) {
                $photo->setEquipment(null); // orphanRemoval löscht das Foto
            }
        }

        return $this;
    }

    public function getMainPhoto(): ?EquipmentPhoto
    {
        foreach ($this->photos as $photo) {
            if ($photo->isMain()) {
                return $photo;
            }
        }

        return null;
    }

    public function setMainPhoto(?EquipmentPhoto $main): static
    {
        foreach ($this->photos as $photo) {
            $photo->setIsMain($photo === $main);
        }

        return $this;
    }

    /** @return Collection<int, Equipment> */
    public function getAccessories(): Collection
    {
        return $this->accessories;
    }

    public function addAccessory(self $accessory): static
    {
        if (!$this->accessories->contains($accessory)) {
            $this->accessories->add($accessory);
            $accessory->addAccessoryOf($this);
        }

        return $this;
    }

    public function addAccessoryOf(self $equipment): static
    {
        if (!$this->accessoryOf->contains($equipment)) {
            $this->accessoryOf->add($equipment);
            $equipment->addAccessory($this);
        }

        return $this;
    }

    public function removeAccessory(self $accessory): static
    {
        if ($this->accessories->removeElement($accessory)) {
            $accessory->removeAccessoryOf($this);
        }

        return $this;
    }

    public function removeAccessoryOf(self $equipment): static
    {
        if ($this->accessoryOf->removeElement($equipment)) {
            $equipment->removeAccessory($this);
        }

        return $this;
    }

    /** @return Collection<int, Equipment> */
    public function getAccessoryOf(): Collection
    {
        return $this->accessoryOf;
    }
}
