<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Testuser: 1 Admin + 6 normale User (Lehrkraft-Funktionen gibt es noch nicht).
 *
 * Microsoft-ID eintragen:
 *   Der Login sucht den User über `oauth_user_id` = "microsoft:<id>" (siehe OAuthAuthenticator).
 *   <id> ist der `sub`-Claim des ID-Tokens (Fallback `oid`), NICHT zwingend die Object-ID aus Entra.
 *   Am einfachsten: einmal einloggen und den Wert aus `SELECT oauth_user_id FROM "user"` übernehmen
 *   (ohne das Präfix "microsoft:").
 *
 * Solange MICROSOFT_ID leer ist, bekommt der User die Platzhalter-ID "fixture:<key>" und kann sich
 * nicht einloggen. E-Mail und Anzeigename werden beim ersten Login mit den Daten von Microsoft
 * überschrieben, die Werte hier sind nur Platzhalter.
 */
class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public const ADMIN = 'admin';
    public const USER_1 = 'user-1';
    public const USER_2 = 'user-2';
    public const USER_3 = 'user-3';
    public const USER_4 = 'user-4';
    public const USER_5 = 'user-5';
    public const USER_6 = 'user-6';

    private const OAUTH_PROVIDER = 'microsoft';

    /**
     * Key => [Microsoft-ID, E-Mail, Anzeigename, isAdmin]
     */
    private const USERS = [
        self::ADMIN => ['p0Uk5Zb5CRo6koLygXoVD0TY71EAQ2F3zSWjLE5WVEI', 'alexander.bachinger@htl.rennweg.at', 'Bachinger Alexander', true],
        self::USER_1 => ['', 'lena.gruber@example.com', 'Lena Gruber', false],
        self::USER_2 => ['', 'jonas.berger@example.com', 'Jonas Berger', false],
        self::USER_3 => ['', 'mia.huber@example.com', 'Mia Huber', false],
        self::USER_4 => ['', 'elias.steiner@example.com', 'Elias Steiner', false],
        self::USER_5 => ['', 'sophie.bauer@example.com', 'Sophie Bauer', false],
        self::USER_6 => ['', 'noah.fischer@example.com', 'Noah Fischer', false],
    ];

    public static function getGroups(): array
    {
        return ['users'];
    }

    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(User::class);

        foreach (self::USERS as $key => [$microsoftId, $email, $name, $isAdmin]) {
            $oauthId = $microsoftId !== ''
                ? self::OAUTH_PROVIDER . ':' . $microsoftId
                : 'fixture:' . $key;

            // Mit --append soll erneutes Laden nicht an UNIQUE (email / oauth_user_id) scheitern
            $user = $repository->findOneBy(['oAuthUserId' => $oauthId])
                ?? $repository->findOneBy(['email' => $email]);

            if ($user === null) {
                $user = (new User($oauthId, $email))
                    ->setDisplayName($name)
                    ->setIsAdmin($isAdmin);

                $manager->persist($user);
            }

            $this->addReference(self::ref($key), $user);
        }

        $manager->flush();
    }

    /** Referenz-Name für getReference() in anderen Fixtures. */
    public static function ref(string $key): string
    {
        return 'user.' . $key;
    }
}
