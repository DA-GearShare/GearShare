<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ConditionPhase;
use App\Repository\ConditionPhotoRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ConditionPhotoRepository::class)]
#[ORM\Table(name: 'condition_photo')]
#[ORM\HasLifecycleCallbacks]
class ConditionPhoto
{
    /**
     * Die ID wird NICHT von Doctrine generiert, sondern beim Upload vergeben:
     * dieselbe UUID ist der Objekt-Key im Condition-Bucket. So muss die Datei
     * nicht vor dem DB-Insert hochgeladen und danach umbenannt werden.
     */
    #[ORM\Id]
    #[ORM\Column(name: 'pk_condition_photo_id', type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Reservation::class, fetch: 'LAZY', inversedBy: 'conditionPhotos')]
    #[ORM\JoinColumn(name: 'fk_reservation_id', referencedColumnName: 'pk_reservation_id', nullable: false, onDelete: 'CASCADE')]
    private ?Reservation $reservation = null;

    /** Uploader. Nur diese Person und Admins dürfen das Foto sehen (siehe ConditionPhotoVoter). */
    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'fk_take_by_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $takenBy = null;

    #[ORM\Column(name: 'phase', type: Types::STRING, length: 255, enumType: ConditionPhase::class)]
    private ConditionPhase $phase;

    #[ORM\Column(name: 'url', type: Types::STRING, length: 255)]
    private string $url;

    /** Per finfo aus dem Dateiinhalt ermittelt. Wird beim Ausliefern als Content-Type gesetzt. */
    #[ORM\Column(name: 'mime_type', type: Types::STRING, length: 50)]
    private string $mimeType;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    public function __construct(
        Uuid           $id,
        Reservation    $reservation,
        ?User          $takenBy,
        ConditionPhase $phase,
        string         $mimeType,
    )
    {
        $this->id = $id;
        $this->takenBy = $takenBy;
        $this->phase = $phase;
        $this->mimeType = $mimeType;
        $this->url = '/api/v1/condition-photos/' . $id->toRfc4122();
        $reservation->addConditionPhoto($this);
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): Uuid
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

    public function getPhase(): ConditionPhase
    {
        return $this->phase;
    }

    public function setPhase(ConditionPhase $phase): static
    {
        $this->phase = $phase;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
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
