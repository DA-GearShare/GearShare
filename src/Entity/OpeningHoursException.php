<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OpeningHoursExceptionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: OpeningHoursExceptionRepository::class)]
#[ORM\Table(name: 'opening_hours_exception')]
class OpeningHoursException
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'pk_opening_hours_exception_id', type: UuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\Column(name: 'exception_date', type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $exceptionDate = null;

    #[ORM\Column(name: 'start_time', type: Types::TIME_IMMUTABLE)]
    private ?DateTimeImmutable $startTime = null;

    #[ORM\Column(name: 'end_time', type: Types::TIME_IMMUTABLE)]
    private ?DateTimeImmutable $endTime = null;

    #[ORM\Column(name: 'is_closed', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isClosed = false;

    #[ORM\Column(name: 'valid_until', type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $validUntil = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'LAZY', inversedBy: 'openingHoursExceptions')]
    #[ORM\JoinColumn(name: 'fk_created_by', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $createdBy = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getExceptionDate(): ?DateTimeImmutable
    {
        return $this->exceptionDate;
    }

    public function setExceptionDate(DateTimeImmutable $exceptionDate): static
    {
        $this->exceptionDate = $exceptionDate;

        return $this;
    }

    public function getStartTime(): ?DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function setIsClosed(bool $isClosed): static
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    public function getValidUntil(): ?DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(DateTimeImmutable $validUntil): static
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
