<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * Testuser: [OAuth-ID, E-Mail, Anzeigename, isAdmin, isTeacher]
     */
    private const USERS = [
        ['test-admin',     'admin2@example.com',   'Test Admin',        true,  false],
        ['test-teacher-1', 'lehrer1@example.com',  'Test Lehrerin',     false, true],
        ['test-teacher-2', 'lehrer2@example.com',  'Test Lehrer',       false, true],
        ['test-student-1', 'schueler1@example.com', 'Test Schüler 1',   false, false],
        ['test-student-2', 'schueler2@example.com', 'Test Schüler 2',   false, false],
        ['test-student-3', 'schueler3@example.com', 'Test Schüler 3',   false, false],
        ['test-both',      'beides@example.com',   'Test Admin+Lehrer', true,  true],
    ];

    public static function getGroups(): array
    {
        return ['users'];
    }

    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(User::class);

        foreach (self::USERS as [$oauthId, $email, $name, $isAdmin, $isTeacher]) {
            // Mehrfaches Laden soll keine Fehler wegen UNIQUE (email/oauth_user_id) verursachen
            if ($repository->findOneBy(['email' => $email]) !== null) {
                continue;
            }

            $user = (new User($oauthId, $email))
                ->setDisplayName($name)
                ->setIsAdmin($isAdmin)
                ->setIsTeacher($isTeacher);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
