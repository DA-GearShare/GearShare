<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Equipment;
use App\Entity\Reservation;
use App\Entity\ReservationItem;
use App\Entity\User;
use App\Enum\ReservationItemStatus;
use App\Enum\ReservationStatus;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class ReservationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private const ADMIN     = 'admin2@example.com';
    private const TEACHER_1 = 'lehrer1@example.com';
    private const TEACHER_2 = 'lehrer2@example.com';
    private const STUDENT_1 = 'schueler1@example.com';
    private const STUDENT_2 = 'schueler2@example.com';
    private const STUDENT_3 = 'schueler3@example.com';
    private const BOTH      = 'beides@example.com';

    /**
     * Reservierungen: [Angefragt von, Verantwortlich, Status, Start, Ende, Geräte-Codes]
     * Start/Ende: [Tages-Offset relativ zu heute, Uhrzeit] – Daten bleiben so immer aktuell.
     */
    private const RESERVATIONS = [
        // ── Angenommen, in der Zukunft (Geräte "Reserved" in AppFixtures) ──────────
        [self::STUDENT_1, self::TEACHER_1, ReservationStatus::Accepted, [1, '08:00'],  [1, '16:00'],  ['EQ-0026', 'EQ-0027']],            // Laptop 3 + Netzteil 3
        [self::STUDENT_2, self::TEACHER_2, ReservationStatus::Accepted, [3, '09:00'],  [4, '15:00'],  ['EQ-0028', 'EQ-0029']],            // Kamera 2 + Akku 2
        [self::TEACHER_1, self::TEACHER_1, ReservationStatus::Accepted, [2, '10:00'],  [2, '12:00'],  ['EQ-0030']],                       // Beamer mobil
        [self::STUDENT_3, self::TEACHER_1, ReservationStatus::Accepted, [5, '08:00'],  [7, '16:00'],  ['EQ-0031', 'EQ-0043']],            // Tablet 2 + Ladegerät

        // ── Aktiv / ausgegeben (Geräte "Usage" in AppFixtures) ────────────────────
        [self::STUDENT_1, self::TEACHER_2, ReservationStatus::Active,   [0, '08:00'],  [2, '16:00'],  ['EQ-0032', 'EQ-0033']],            // Laptop 4 + Netzteil 4
        [self::TEACHER_2, self::ADMIN,     ReservationStatus::Active,   [-1, '12:00'], [1, '18:00'],  ['EQ-0034']],                       // Funkmikrofon 2
        [self::STUDENT_2, self::TEACHER_1, ReservationStatus::Active,   [-2, '09:00'], [3, '16:00'],  ['EQ-0035', 'EQ-0036', 'EQ-0052']], // Action-Kamera + microSD + GoPro-Akku
        [self::STUDENT_3, self::TEACHER_2, ReservationStatus::Active,   [-5, '08:00'], [-1, '16:00'], ['EQ-0037']],                       // VR-Brille 2 – ÜBERFÄLLIG

        // ── Abgeschlossen, in der Vergangenheit (Geräte wieder "Available") ───────
        [self::STUDENT_1, self::TEACHER_1, ReservationStatus::Returned, [-14, '08:00'], [-12, '16:00'], ['EQ-0001', 'EQ-0002']],          // Laptop + Netzteil
        [self::STUDENT_2, self::TEACHER_2, ReservationStatus::Returned, [-10, '09:00'], [-9, '15:00'],  ['EQ-0004', 'EQ-0009', 'EQ-0011']], // Kamera + Stativ + Akku
        [self::BOTH,      self::BOTH,      ReservationStatus::Returned, [-7, '17:00'],  [-7, '22:00'],  ['EQ-0005', 'EQ-0012', 'EQ-0016']], // Mikrofon + Ständer + Mischpult

        // ── Offen / abgelehnt / storniert (Geräte bleiben "Available") ────────────
        [self::STUDENT_3, self::TEACHER_1, ReservationStatus::Requested, [10, '08:00'], [10, '16:00'], ['EQ-0024', 'EQ-0051']],           // Drohne + Drohnen-Akku
        [self::STUDENT_1, self::TEACHER_2, ReservationStatus::Requested, [6, '13:00'],  [6, '17:00'],  ['EQ-0008', 'EQ-0042']],           // Tablet + Eingabestift
        [self::STUDENT_1, self::TEACHER_2, ReservationStatus::Denied,    [4, '08:00'],  [4, '16:00'],  ['EQ-0021', 'EQ-0048']],           // 3D-Drucker + Druckplatte
        [self::STUDENT_2, self::TEACHER_1, ReservationStatus::Cancelled, [8, '10:00'],  [8, '14:00'],  ['EQ-0020', 'EQ-0046']],           // VR-Brille + Link-Kabel
    ];

    /** @var array<string, Equipment> */
    private array $equipmentCache = [];

    /** @var array<string, User> */
    private array $userCache = [];

    public function getDependencies(): array
    {
        return [AppFixtures::class, UserFixtures::class];
    }

    public static function getGroups(): array
    {
        return ['reservations'];
    }

    public function load(ObjectManager $manager): void
    {
        $now = new DateTimeImmutable();

        foreach (self::RESERVATIONS as [$requestedBy, $responsible, $status, $start, $end, $codes]) {
            $startAt = $this->at(...$start);
            $endAt = $this->at(...$end);

            $reservation = (new Reservation())
                ->setRequestedBy($this->user($manager, $requestedBy))
                ->setResponsibleUser($this->user($manager, $responsible))
                ->setStatus($status)
                ->setStartDatetime($startAt)
                ->setEndDatetime($endAt)
                ->setCreatedAt(min($startAt->modify('-3 days'), $now));

            foreach ($codes as $code) {
                $reservation->addItem(
                    $this->createItem($this->equipment($manager, $code), $status, $startAt, $endAt)
                );
            }

            $manager->persist($reservation); // Items über cascade persist
        }

        $manager->flush();
    }

    private function createItem(
        Equipment $equipment,
        ReservationStatus $status,
        DateTimeImmutable $startAt,
        DateTimeImmutable $endAt,
    ): ReservationItem {
        $item = (new ReservationItem())
            ->setRequestedEquipment($equipment)
            ->setStatus(match ($status) {
                ReservationStatus::Active   => ReservationItemStatus::Issued,
                ReservationStatus::Returned => ReservationItemStatus::Returned,
                default                     => ReservationItemStatus::Requested,
            });

        // Ab Annahme ist das konkrete Gerät zugewiesen
        if (in_array($status, [ReservationStatus::Accepted, ReservationStatus::Active, ReservationStatus::Returned], true)) {
            $item->setAssignedEquipment($equipment);
        }

        if (in_array($status, [ReservationStatus::Active, ReservationStatus::Returned], true)) {
            $item->setIssuedAt($startAt);
        }

        if ($status === ReservationStatus::Returned) {
            $item->setReturnedAt($endAt);
        }

        return $item;
    }

    private function at(int $dayOffset, string $time): DateTimeImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return (new DateTimeImmutable('today'))
            ->modify(sprintf('%+d days', $dayOffset))
            ->setTime($hour, $minute);
    }

    private function equipment(ObjectManager $manager, string $code): Equipment
    {
        return $this->equipmentCache[$code] ??= $manager->getRepository(Equipment::class)->findOneBy(['code' => $code])
            ?? throw new RuntimeException(sprintf('Equipment "%s" nicht gefunden – AppFixtures geladen?', $code));
    }

    private function user(ObjectManager $manager, string $email): User
    {
        return $this->userCache[$email] ??= $manager->getRepository(User::class)->findOneBy(['email' => $email])
            ?? throw new RuntimeException(sprintf('User "%s" nicht gefunden – UserFixtures geladen?', $email));
    }
}
