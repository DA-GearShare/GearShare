<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Equipment;
use App\Enum\EquipmentStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    /**
     * Alle Geräte: [Code, Name, Standort, Typ, Modell]
     */
    private const EQUIPMENT = [
        // ── Verfügbar ─────────────────────────────────────────────────────────────
        ['EQ-0001', 'Laptop',                'IT-Labor',   'Laptop',          'Lenovo ThinkPad T14'],
        ['EQ-0002', 'Laptop Netzteil',       'IT-Labor',   'Netzteil',        'Lenovo 65W USB-C'],
        ['EQ-0003', 'Beamer Aula',           'Aula',       'Beamer',          'Epson EB-2250U'],
        ['EQ-0004', 'Kamera',                'Medienraum', 'Kamera',          'Sony Alpha 7 III'],
        ['EQ-0005', 'Mikrofon',              'Aula',       'Mikrofon',        'Shure SM58'],

        ['EQ-0006', 'Laptop 2',              'IT-Labor',   'Laptop',          'Lenovo ThinkPad T14'],
        ['EQ-0007', 'Laptop Netzteil 2',     'IT-Labor',   'Netzteil',        'Lenovo 65W USB-C'],
        ['EQ-0008', 'Tablet',                'Medienraum', 'Tablet',          'Apple iPad 10. Generation'],
        ['EQ-0009', 'Kamerastativ',          'Medienraum', 'Stativ',          'Manfrotto MT190X3'],
        ['EQ-0010', 'Speicherkarte',         'Medienraum', 'Speicherkarte',   'SanDisk Extreme Pro 128 GB'],
        ['EQ-0011', 'Kamera-Akku',           'Medienraum', 'Akku',            'Sony NP-FZ100'],
        ['EQ-0012', 'Mikrofonständer',       'Aula',       'Ständer',         'K&M 210/9'],
        ['EQ-0013', 'XLR-Kabel 10 m',        'Aula',       'Kabel',           'Cordial CCM 10 FM'],
        ['EQ-0014', 'Funkmikrofon',          'Aula',       'Mikrofon',        'Sennheiser EW 100 G4'],
        ['EQ-0015', 'Lautsprecher',          'Aula',       'Lautsprecher',    'JBL EON 712'],
        ['EQ-0016', 'Mischpult',             'Aula',       'Mischpult',       'Yamaha MG10XU'],
        ['EQ-0017', 'HDMI-Kabel 5 m',        'Aula',       'Kabel',           'Hama Ultra High Speed HDMI'],
        ['EQ-0018', 'Mobile Leinwand',       'Aula',       'Leinwand',        'Reflecta Crystal-Line 200x200'],
        ['EQ-0019', 'Dokumentenkamera',      'Raum 2.04',  'Dokumentenkamera','Elmo MX-P3'],
        ['EQ-0020', 'VR-Brille',             'IT-Labor',   'VR-Brille',       'Meta Quest 3'],
        ['EQ-0021', '3D-Drucker',            'Werkstatt',  '3D-Drucker',      'Prusa MK4'],
        ['EQ-0022', 'Raspberry Pi Set',      'IT-Labor',   'Mikrocontroller', 'Raspberry Pi 5 8 GB'],
        ['EQ-0023', 'Arduino Starter Kit',   'IT-Labor',   'Mikrocontroller', 'Arduino Uno R4 Starter Kit'],
        ['EQ-0024', 'Drohne',                'Medienraum', 'Drohne',          'DJI Mini 4 Pro'],
        ['EQ-0025', 'Gimbal',                'Medienraum', 'Gimbal',          'DJI RS 3 Mini'],

        // ── Reserviert ────────────────────────────────────────────────────────────
        ['EQ-0026', 'Laptop 3',              'IT-Labor',   'Laptop',          'Lenovo ThinkPad T14'],
        ['EQ-0027', 'Laptop Netzteil 3',     'IT-Labor',   'Netzteil',        'Lenovo 65W USB-C'],
        ['EQ-0028', 'Kamera 2',              'Medienraum', 'Kamera',          'Sony Alpha 7 IV'],
        ['EQ-0029', 'Kamera-Akku 2',         'Medienraum', 'Akku',            'Sony NP-FZ100'],
        ['EQ-0030', 'Beamer mobil',          'Raum 2.04',  'Beamer',          'Epson EB-L210W'],
        ['EQ-0031', 'Tablet 2',              'Medienraum', 'Tablet',          'Apple iPad 10. Generation'],

        // ── Ausgegeben ────────────────────────────────────────────────────────────
        ['EQ-0032', 'Laptop 4',              'IT-Labor',   'Laptop',          'Lenovo ThinkPad T14'],
        ['EQ-0033', 'Laptop Netzteil 4',     'IT-Labor',   'Netzteil',        'Lenovo 65W USB-C'],
        ['EQ-0034', 'Funkmikrofon 2',        'Aula',       'Mikrofon',        'Sennheiser EW-D SKM-S'],
        ['EQ-0035', 'Action-Kamera',         'Medienraum', 'Kamera',          'GoPro HERO13 Black'],
        ['EQ-0036', 'microSD-Karte',         'Medienraum', 'Speicherkarte',   'SanDisk Extreme microSD 128 GB'],
        ['EQ-0037', 'VR-Brille 2',           'IT-Labor',   'VR-Brille',       'Meta Quest 3'],

        // ── Zusätzliches Zubehör (Status siehe STATUS, sonst verfügbar) ───────────
        ['EQ-0038', 'USB-C-Hub',             'IT-Labor',   'Adapter',         'Anker 7-in-1 USB-C Hub'],
        ['EQ-0039', 'Funkmaus',              'IT-Labor',   'Maus',            'Logitech M705'],
        ['EQ-0040', 'Presenter',             'Aula',       'Presenter',       'Logitech R500s'],
        ['EQ-0041', 'Kamera-Objektiv',       'Medienraum', 'Objektiv',        'Sony FE 28-70mm F3.5-5.6 OSS'],
        ['EQ-0042', 'Eingabestift',          'Medienraum', 'Eingabestift',    'Apple Pencil (USB-C)'],
        ['EQ-0043', 'Tablet-Ladegerät',      'Medienraum', 'Netzteil',        'Apple 20W USB-C'],
        ['EQ-0044', 'Akku-Set AA',           'Aula',       'Akku',            'Panasonic Eneloop Pro AA 8er-Set'],
        ['EQ-0045', 'Lautsprecherstativ',    'Aula',       'Stativ',          'K&M 21436'],
        ['EQ-0046', 'VR-Link-Kabel',         'IT-Labor',   'Kabel',           'Meta Quest Link Kabel 5 m'],
        ['EQ-0047', 'VR-Ladestation',        'IT-Labor',   'Ladestation',     'Anker Charging Dock für Meta Quest 3'],
        ['EQ-0048', 'Ersatz-Druckplatte',    'Werkstatt',  'Druckplatte',     'Prusa Satin Steel Sheet'],
        ['EQ-0049', 'Raspberry Pi Netzteil', 'IT-Labor',   'Netzteil',        'Raspberry Pi 27W USB-C'],
        ['EQ-0050', 'Micro-HDMI-Kabel',      'IT-Labor',   'Kabel',           'Raspberry Pi Micro-HDMI auf HDMI 1 m'],
        ['EQ-0051', 'Drohnen-Akku',          'Medienraum', 'Akku',            'DJI Mini 4 Pro Intelligent Flight Battery'],
        ['EQ-0052', 'GoPro-Akku',            'Medienraum', 'Akku',            'GoPro Enduro Akku'],
    ];

    /**
     * Status-Abweichungen: Code => Status
     * Alle Geräte, die hier nicht stehen, sind "Available".
     */
    private const STATUS = [
        // Reserviert
        'EQ-0026' => EquipmentStatus::Reserved,
        'EQ-0027' => EquipmentStatus::Reserved,
        'EQ-0028' => EquipmentStatus::Reserved,
        'EQ-0029' => EquipmentStatus::Reserved,
        'EQ-0030' => EquipmentStatus::Reserved,
        'EQ-0031' => EquipmentStatus::Reserved,
        'EQ-0043' => EquipmentStatus::Reserved,

        // Ausgegeben
        'EQ-0032' => EquipmentStatus::Usage,
        'EQ-0033' => EquipmentStatus::Usage,
        'EQ-0034' => EquipmentStatus::Usage,
        'EQ-0035' => EquipmentStatus::Usage,
        'EQ-0036' => EquipmentStatus::Usage,
        'EQ-0037' => EquipmentStatus::Usage,
        'EQ-0052' => EquipmentStatus::Usage,
    ];

    /**
     * Zubehör / "passt dazu": Code des Geräts => Codes der Empfehlungen
     * Jedes Gerät hat mindestens 5 Empfehlungen.
     * Ein Gerät kann bei mehreren anderen als Empfehlung auftauchen (z. B. USB-C-Hub).
     */
    private const ACCESSORIES = [
        'EQ-0001' => ['EQ-0002', 'EQ-0038', 'EQ-0039', 'EQ-0017', 'EQ-0040'],            // Laptop → Netzteil, Hub, Maus, HDMI, Presenter
        'EQ-0002' => ['EQ-0001', 'EQ-0006', 'EQ-0026', 'EQ-0032', 'EQ-0038'],            // Laptop Netzteil → Laptops 1–4, Hub
        'EQ-0003' => ['EQ-0017', 'EQ-0018', 'EQ-0040', 'EQ-0038', 'EQ-0001', 'EQ-0015'], // Beamer Aula → HDMI, Leinwand, Presenter, Hub, Laptop, Lautsprecher
        'EQ-0004' => ['EQ-0009', 'EQ-0010', 'EQ-0011', 'EQ-0041', 'EQ-0025'],            // Kamera → Stativ, Karte, Akku, Objektiv, Gimbal
        'EQ-0005' => ['EQ-0012', 'EQ-0013', 'EQ-0016', 'EQ-0015', 'EQ-0045'],            // Mikrofon → Ständer, XLR, Mischpult, Lautsprecher, LS-Stativ
        'EQ-0006' => ['EQ-0007', 'EQ-0038', 'EQ-0039', 'EQ-0017', 'EQ-0040'],            // Laptop 2 → Netzteil 2, Hub, Maus, HDMI, Presenter
        'EQ-0007' => ['EQ-0006', 'EQ-0001', 'EQ-0026', 'EQ-0032', 'EQ-0038'],            // Laptop Netzteil 2 → Laptops, Hub
        'EQ-0008' => ['EQ-0042', 'EQ-0043', 'EQ-0038', 'EQ-0017', 'EQ-0040'],            // Tablet → Stift, Ladegerät, Hub, HDMI, Presenter
        'EQ-0009' => ['EQ-0004', 'EQ-0028', 'EQ-0035', 'EQ-0041', 'EQ-0011'],            // Kamerastativ → Kamera, Kamera 2, Action-Kamera, Objektiv, Akku
        'EQ-0010' => ['EQ-0004', 'EQ-0028', 'EQ-0011', 'EQ-0041', 'EQ-0038'],            // Speicherkarte → Kameras, Akku, Objektiv, Hub (Kartenleser)
        'EQ-0011' => ['EQ-0004', 'EQ-0028', 'EQ-0029', 'EQ-0010', 'EQ-0025'],            // Kamera-Akku → Kameras, Akku 2, Karte, Gimbal
        'EQ-0012' => ['EQ-0005', 'EQ-0014', 'EQ-0034', 'EQ-0013', 'EQ-0016'],            // Mikrofonständer → Mikrofone, XLR, Mischpult
        'EQ-0013' => ['EQ-0005', 'EQ-0016', 'EQ-0015', 'EQ-0012', 'EQ-0014'],            // XLR-Kabel → Mikrofon, Mischpult, Lautsprecher, Ständer, Funkmikro
        'EQ-0014' => ['EQ-0044', 'EQ-0016', 'EQ-0015', 'EQ-0013', 'EQ-0012'],            // Funkmikrofon → Akkus, Mischpult, Lautsprecher, XLR, Ständer
        'EQ-0015' => ['EQ-0045', 'EQ-0013', 'EQ-0016', 'EQ-0005', 'EQ-0014'],            // Lautsprecher → Stativ, XLR, Mischpult, Mikrofone
        'EQ-0016' => ['EQ-0013', 'EQ-0015', 'EQ-0005', 'EQ-0014', 'EQ-0045'],            // Mischpult → XLR, Lautsprecher, Mikrofone, LS-Stativ
        'EQ-0017' => ['EQ-0003', 'EQ-0030', 'EQ-0019', 'EQ-0001', 'EQ-0006'],            // HDMI-Kabel → Beamer, Beamer mobil, Doku-Kamera, Laptops
        'EQ-0018' => ['EQ-0003', 'EQ-0030', 'EQ-0040', 'EQ-0019', 'EQ-0017'],            // Leinwand → Beamer, Beamer mobil, Presenter, Doku-Kamera, HDMI
        'EQ-0019' => ['EQ-0017', 'EQ-0030', 'EQ-0003', 'EQ-0018', 'EQ-0001'],            // Dokumentenkamera → HDMI, Beamer mobil, Beamer, Leinwand, Laptop
        'EQ-0020' => ['EQ-0046', 'EQ-0047', 'EQ-0001', 'EQ-0044', 'EQ-0038'],            // VR-Brille → Link-Kabel, Ladestation, Laptop, AA-Akkus, Hub
        'EQ-0021' => ['EQ-0048', 'EQ-0001', 'EQ-0006', 'EQ-0022', 'EQ-0023'],            // 3D-Drucker → Druckplatte, Laptops, Raspberry Pi, Arduino
        'EQ-0022' => ['EQ-0049', 'EQ-0050', 'EQ-0036', 'EQ-0023', 'EQ-0039'],            // Raspberry Pi → Netzteil, Micro-HDMI, microSD, Arduino, Maus
        'EQ-0023' => ['EQ-0022', 'EQ-0001', 'EQ-0006', 'EQ-0038', 'EQ-0049'],            // Arduino → Raspberry Pi, Laptops, Hub, RPi-Netzteil
        'EQ-0024' => ['EQ-0051', 'EQ-0036', 'EQ-0025', 'EQ-0010', 'EQ-0008'],            // Drohne → Akku, microSD, Gimbal, Karte, Tablet
        'EQ-0025' => ['EQ-0004', 'EQ-0028', 'EQ-0011', 'EQ-0009', 'EQ-0041'],            // Gimbal → Kameras, Akku, Stativ, Objektiv

        'EQ-0026' => ['EQ-0027', 'EQ-0038', 'EQ-0039', 'EQ-0017', 'EQ-0040'],            // Laptop 3 → Netzteil 3, Hub, Maus, HDMI, Presenter
        'EQ-0027' => ['EQ-0026', 'EQ-0001', 'EQ-0006', 'EQ-0032', 'EQ-0038'],            // Laptop Netzteil 3 → Laptops, Hub
        'EQ-0028' => ['EQ-0029', 'EQ-0009', 'EQ-0010', 'EQ-0011', 'EQ-0041'],            // Kamera 2 → Akku 2, Stativ, Karte, Akku, Objektiv
        'EQ-0029' => ['EQ-0028', 'EQ-0004', 'EQ-0011', 'EQ-0025', 'EQ-0010'],            // Kamera-Akku 2 → Kameras, Akku, Gimbal, Karte
        'EQ-0030' => ['EQ-0017', 'EQ-0018', 'EQ-0040', 'EQ-0019', 'EQ-0038'],            // Beamer mobil → HDMI, Leinwand, Presenter, Doku-Kamera, Hub
        'EQ-0031' => ['EQ-0042', 'EQ-0043', 'EQ-0038', 'EQ-0017', 'EQ-0040'],            // Tablet 2 → Stift, Ladegerät, Hub, HDMI, Presenter

        'EQ-0032' => ['EQ-0033', 'EQ-0038', 'EQ-0039', 'EQ-0017', 'EQ-0040'],            // Laptop 4 → Netzteil 4, Hub, Maus, HDMI, Presenter
        'EQ-0033' => ['EQ-0032', 'EQ-0001', 'EQ-0006', 'EQ-0026', 'EQ-0038'],            // Laptop Netzteil 4 → Laptops, Hub
        'EQ-0034' => ['EQ-0044', 'EQ-0016', 'EQ-0015', 'EQ-0013', 'EQ-0012'],            // Funkmikrofon 2 → Akkus, Mischpult, Lautsprecher, XLR, Ständer
        'EQ-0035' => ['EQ-0036', 'EQ-0052', 'EQ-0009', 'EQ-0010', 'EQ-0025'],            // Action-Kamera → microSD, GoPro-Akku, Stativ, Karte, Gimbal
        'EQ-0036' => ['EQ-0035', 'EQ-0022', 'EQ-0024', 'EQ-0038', 'EQ-0008'],            // microSD → Action-Kamera, Raspberry Pi, Drohne, Hub, Tablet
        'EQ-0037' => ['EQ-0046', 'EQ-0047', 'EQ-0044', 'EQ-0001', 'EQ-0038'],            // VR-Brille 2 → Link-Kabel, Ladestation, AA-Akkus, Laptop, Hub

        'EQ-0038' => ['EQ-0001', 'EQ-0006', 'EQ-0026', 'EQ-0032', 'EQ-0017'],            // USB-C-Hub → Laptops, HDMI
        'EQ-0039' => ['EQ-0001', 'EQ-0006', 'EQ-0026', 'EQ-0032', 'EQ-0022'],            // Funkmaus → Laptops, Raspberry Pi
        'EQ-0040' => ['EQ-0003', 'EQ-0030', 'EQ-0001', 'EQ-0006', 'EQ-0018'],            // Presenter → Beamer, Beamer mobil, Laptops, Leinwand
        'EQ-0041' => ['EQ-0004', 'EQ-0028', 'EQ-0009', 'EQ-0025', 'EQ-0010'],            // Kamera-Objektiv → Kameras, Stativ, Gimbal, Karte
        'EQ-0042' => ['EQ-0008', 'EQ-0031', 'EQ-0043', 'EQ-0038', 'EQ-0001'],            // Eingabestift → Tablets, Ladegerät, Hub, Laptop
        'EQ-0043' => ['EQ-0008', 'EQ-0031', 'EQ-0042', 'EQ-0038', 'EQ-0035'],            // Tablet-Ladegerät → Tablets, Stift, Hub, Action-Kamera
        'EQ-0044' => ['EQ-0014', 'EQ-0034', 'EQ-0020', 'EQ-0037', 'EQ-0039'],            // Akku-Set AA → Funkmikros, VR-Brillen, Maus
        'EQ-0045' => ['EQ-0015', 'EQ-0016', 'EQ-0013', 'EQ-0005', 'EQ-0012'],            // Lautsprecherstativ → Lautsprecher, Mischpult, XLR, Mikrofon, Ständer
        'EQ-0046' => ['EQ-0020', 'EQ-0037', 'EQ-0047', 'EQ-0001', 'EQ-0006'],            // VR-Link-Kabel → VR-Brillen, Ladestation, Laptops
        'EQ-0047' => ['EQ-0020', 'EQ-0037', 'EQ-0046', 'EQ-0044', 'EQ-0043'],            // VR-Ladestation → VR-Brillen, Link-Kabel, AA-Akkus, Ladegerät
        'EQ-0048' => ['EQ-0021', 'EQ-0001', 'EQ-0006', 'EQ-0022', 'EQ-0023'],            // Ersatz-Druckplatte → 3D-Drucker, Laptops, Raspberry Pi, Arduino
        'EQ-0049' => ['EQ-0022', 'EQ-0050', 'EQ-0036', 'EQ-0023', 'EQ-0038'],            // RPi-Netzteil → Raspberry Pi, Micro-HDMI, microSD, Arduino, Hub
        'EQ-0050' => ['EQ-0022', 'EQ-0049', 'EQ-0017', 'EQ-0036', 'EQ-0003'],            // Micro-HDMI → Raspberry Pi, RPi-Netzteil, HDMI, microSD, Beamer
        'EQ-0051' => ['EQ-0024', 'EQ-0036', 'EQ-0010', 'EQ-0008', 'EQ-0025'],            // Drohnen-Akku → Drohne, microSD, Karte, Tablet, Gimbal
        'EQ-0052' => ['EQ-0035', 'EQ-0036', 'EQ-0009', 'EQ-0010', 'EQ-0043'],            // GoPro-Akku → Action-Kamera, microSD, Stativ, Karte, Ladegerät
    ];

    public function load(ObjectManager $manager): void
    {
        $items = [];

        foreach (self::EQUIPMENT as [$code, $name, $location, $type, $model]) {
            $equipment = (new Equipment())
                ->setName($name)
                ->setLocation($location)
                ->setType($type)
                ->setModel($model)
                ->setCode($code)
                ->setStatus(self::STATUS[$code] ?? EquipmentStatus::Available);

            $manager->persist($equipment);
            $items[$code] = $equipment;
        }

        foreach (self::ACCESSORIES as $parentCode => $accessoryCodes) {
            foreach ($accessoryCodes as $accessoryCode) {
                $items[$parentCode]->addAccessory($items[$accessoryCode]);
            }
        }

        $manager->flush();
    }
}
