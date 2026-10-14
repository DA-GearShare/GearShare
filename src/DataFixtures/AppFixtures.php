<?php

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
    ];

    /**
     * Zubehör: Code des Hauptgeräts => Codes des Zubehörs
     */
    private const ACCESSORIES = [
        'EQ-0001' => ['EQ-0002'],                       // Laptop → Netzteil
        'EQ-0006' => ['EQ-0007'],                       // Laptop 2 → Netzteil 2
        'EQ-0004' => ['EQ-0009', 'EQ-0010', 'EQ-0011'], // Kamera → Stativ, Speicherkarte, Akku
        'EQ-0005' => ['EQ-0012', 'EQ-0013'],            // Mikrofon → Ständer, XLR-Kabel
        'EQ-0003' => ['EQ-0017'],                       // Beamer → HDMI-Kabel
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
                ->setStatus(EquipmentStatus::Available);

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
