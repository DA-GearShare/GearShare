<?php

namespace App\DataFixtures;

use App\Entity\Equipment;
use App\Enum\EquipmentStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $laptop = new Equipment();
        $laptop
            ->setName('Laptop')
            ->setLocation('IT-Labor')
            ->setType('Laptop')
            ->setModel('Lenovo ThinkPad T14')
            ->setCode('EQ-0001')
            ->setStatus(EquipmentStatus::Available);

        $charger = new Equipment();
        $charger
            ->setName('Laptop Netzteil')
            ->setLocation('IT-Labor')
            ->setType('Netzteil')
            ->setModel('Lenovo 65W USB-C')
            ->setCode('EQ-0002')
            ->setStatus(EquipmentStatus::Available);

        $beamer = new Equipment();
        $beamer
            ->setName('Beamer Aula')
            ->setLocation('Aula')
            ->setType('Beamer')
            ->setModel('Epson EB-2250U')
            ->setCode('EQ-0003')
            ->setStatus(EquipmentStatus::Available);

        $camera = new Equipment();
        $camera
            ->setName('Kamera')
            ->setLocation('Medienraum')
            ->setType('Kamera')
            ->setModel('Sony Alpha 7 III')
            ->setCode('EQ-0004')
            ->setStatus(EquipmentStatus::Available);

        $microphone = new Equipment();
        $microphone
            ->setName('Mikrofon')
            ->setLocation('Aula')
            ->setType('Mikrofon')
            ->setModel('Shure SM58')
            ->setCode('EQ-0005')
            ->setStatus(EquipmentStatus::Available);

        $laptop->addAccessory($charger);

        $manager->persist($laptop);
        $manager->persist($charger);
        $manager->persist($beamer);
        $manager->persist($camera);
        $manager->persist($microphone);

        $manager->flush();
    }
}
