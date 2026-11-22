<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\UserFixtures as U;
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

class ReservationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /**
     * Reservierungen: [Angefragt von, Status, Start, Ende, Geräte-Codes]
     * Verantwortlich ist wie in ReservationRepository::create() immer die anfragende Person.
     * Start/Ende: [Tages-Offset relativ zu heute, Uhrzeit] – Daten bleiben so immer aktuell.
     */
    private const RESERVATIONS = [
        // ── Angefragt, in der Zukunft ─────────────────────────────────────────────
        [U::USER_1, ReservationStatus::Requested, [1, '08:00'],  [1, '16:00'],  ['EQ-0026', 'EQ-0027']],            // Laptop 3 + Netzteil 3
        [U::USER_2, ReservationStatus::Requested, [3, '09:00'],  [4, '15:00'],  ['EQ-0028', 'EQ-0029', 'EQ-0041']], // Kamera 2 + Akku 2 + Objektiv
        [U::USER_5, ReservationStatus::Requested, [2, '10:00'],  [2, '12:00'],  ['EQ-0030', 'EQ-0018']],            // Beamer mobil + Leinwand
        [U::USER_3, ReservationStatus::Requested, [5, '08:00'],  [7, '16:00'],  ['EQ-0031', 'EQ-0043']],            // Tablet 2 + Ladegerät
        [U::USER_4, ReservationStatus::Requested, [10, '08:00'], [10, '16:00'], ['EQ-0024', 'EQ-0051']],            // Drohne + Drohnen-Akku
        [U::USER_1, ReservationStatus::Requested, [6, '13:00'],  [6, '17:00'],  ['EQ-0008', 'EQ-0042']],            // Tablet + Eingabestift
        [U::USER_6, ReservationStatus::Requested, [9, '18:00'],  [9, '22:00'],  ['EQ-0005', 'EQ-0012', 'EQ-0015', 'EQ-0045', 'EQ-0016']], // Elternabend: Audio-Set

        // ── Aktiv / ausgegeben ────────────────────────────────────────────────────
        [U::USER_1, ReservationStatus::Active,    [0, '08:00'],  [2, '16:00'],  ['EQ-0032', 'EQ-0033']],            // Laptop 4 + Netzteil 4
        [U::ADMIN,  ReservationStatus::Active,    [-1, '12:00'], [1, '18:00'],  ['EQ-0034']],                       // Funkmikrofon 2
        [U::USER_2, ReservationStatus::Active,    [-2, '09:00'], [3, '16:00'],  ['EQ-0035', 'EQ-0036', 'EQ-0052']], // Action-Kamera + microSD + GoPro-Akku
        [U::USER_3, ReservationStatus::Active,    [-5, '08:00'], [-1, '16:00'], ['EQ-0037']],                       // VR-Brille 2 – ÜBERFÄLLIG
        [U::USER_4, ReservationStatus::Active,    [-1, '08:00'], [4, '16:00'],  ['EQ-0022', 'EQ-0049', 'EQ-0050']], // Raspberry Pi + Netzteil + Micro-HDMI

        // ── Abgeschlossen, in der Vergangenheit ───────────────────────────────────
        [U::USER_1, ReservationStatus::Returned,  [-14, '08:00'], [-12, '16:00'], ['EQ-0001', 'EQ-0002']],            // Laptop + Netzteil
        [U::USER_2, ReservationStatus::Returned,  [-10, '09:00'], [-9, '15:00'],  ['EQ-0004', 'EQ-0009', 'EQ-0011']], // Kamera + Stativ + Akku
        [U::USER_5, ReservationStatus::Returned,  [-7, '17:00'],  [-7, '22:00'],  ['EQ-0005', 'EQ-0012', 'EQ-0016']], // Mikrofon + Ständer + Mischpult
        [U::USER_4, ReservationStatus::Returned,  [-21, '08:00'], [-17, '16:00'], ['EQ-0021', 'EQ-0048']],            // 3D-Drucker + Druckplatte

        // ── Abgelehnt / storniert ─────────────────────────────────────────────────
        [U::USER_1, ReservationStatus::Denied,    [4, '08:00'],  [4, '16:00'],  ['EQ-0021', 'EQ-0048']],            // 3D-Drucker + Druckplatte
        [U::USER_2, ReservationStatus::Cancelled, [8, '10:00'],  [8, '14:00'],  ['EQ-0020', 'EQ-0046']],            // VR-Brille + Link-Kabel
    ];

    public function getDependencies(): array
    {
        return [EquipmentFixtures::class, U::class];
    }

    public static function getGroups(): array
    {
        return ['reservations'];
    }

    public function load(ObjectManager $manager): void
    {
        $now = new DateTimeImmutable();

        foreach (self::RESERVATIONS as [$requestedBy, $status, $start, $end, $codes]) {
            $startAt = $this->at(...$start);
            $endAt = $this->at(...$end);

            $user = $this->getReference(U::ref($requestedBy), User::class);

            $reservation = new Reservation()
                ->setRequestedBy($user)
                ->setResponsibleUser($user)
                ->setStatus($status)
                ->setStartDatetime($startAt)
                ->setEndDatetime($endAt)
                ->setCreatedAt(min($startAt->modify('-3 days'), $now));

            foreach ($codes as $code) {
                $equipment = $this->getReference(EquipmentFixtures::ref($code), Equipment::class);
                $reservation->addItem($this->createItem($equipment, $status, $startAt, $endAt));
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
        $item = new ReservationItem()
            ->setRequestedEquipment($equipment)
            ->setStatus(match ($status) {
                ReservationStatus::Active => ReservationItemStatus::Issued,
                ReservationStatus::Returned => ReservationItemStatus::Returned,
                default => ReservationItemStatus::Requested,
            });

        if (\in_array($status, [ReservationStatus::Active, ReservationStatus::Returned], true)) {
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

        return new DateTimeImmutable('today')
            ->modify(\sprintf('%+d days', $dayOffset))
            ->setTime($hour, $minute);
    }
}
