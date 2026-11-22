<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Testuser: 1 Admin + 6 normale User (Lehrkraft-Funktionen gibt es noch nicht).
 *
 * Persönliche Daten (Microsoft-ID, E-Mail, Name) kommen aus der Env-Variable FIXTURE_USERS und
 * gehören in die nicht versionierte .env.local, damit sie nicht im Git landen:
 *
 *   FIXTURE_USERS='{"admin":{"microsoftId":"<sub>","email":"...","name":"..."},"user-1":{...}}'
 *
 * Jedes Feld ist optional, fehlende Werte werden durch die Platzhalter unten ersetzt.
 *
 * Der Login sucht den User über `oauth_user_id` = "microsoft:<id>" (siehe OAuthAuthenticator).
 * <id> ist der `sub`-Claim des ID-Tokens (Fallback `oid`), NICHT zwingend die Object-ID aus Entra.
 * Am einfachsten: einmal einloggen und den Wert aus `SELECT oauth_user_id FROM "user"` übernehmen
 * (ohne das Präfix "microsoft:").
 *
 * User ohne Microsoft-ID bekommen die Platzhalter-ID "fixture:<key>" und können sich nicht einloggen.
 * E-Mail und Name werden beim Login ohnehin mit den Daten von Microsoft überschrieben.
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

    private const FIELDS = ['microsoftId', 'email', 'name'];

    /**
     * Key => [Platzhalter-E-Mail, Platzhalter-Name, isAdmin].
     */
    private const USERS = [
        self::ADMIN => ['admin@example.com', 'Admin', true],
        self::USER_1 => ['user1@example.com', 'Testuser 1', false],
        self::USER_2 => ['user2@example.com', 'Testuser 2', false],
        self::USER_3 => ['user3@example.com', 'Testuser 3', false],
        self::USER_4 => ['user4@example.com', 'Testuser 4', false],
        self::USER_5 => ['user5@example.com', 'Testuser 5', false],
        self::USER_6 => ['user6@example.com', 'Testuser 6', false],
    ];

    /**
     * @param array<string, array{microsoftId?: string, email?: string, name?: string}> $userData
     *                                                                                            Key => persönliche Daten, aus FIXTURE_USERS
     */
    public function __construct(
        #[Autowire('%env(json:FIXTURE_USERS)%')]
        private readonly array $userData,
    ) {
        $unknown = array_diff(array_keys($this->userData), array_keys(self::USERS));
        if ($unknown !== []) {
            throw new InvalidArgumentException(\sprintf('FIXTURE_USERS enthält unbekannte User: %s (erlaubt: %s)', implode(', ', $unknown), implode(', ', array_keys(self::USERS))));
        }

        foreach ($this->userData as $key => $data) {
            if (!\is_array($data)) {
                throw new InvalidArgumentException(\sprintf('FIXTURE_USERS["%s"] muss ein Objekt sein.', $key));
            }

            $unknownFields = array_diff(array_keys($data), self::FIELDS);
            if ($unknownFields !== []) {
                throw new InvalidArgumentException(\sprintf('FIXTURE_USERS["%s"] enthält unbekannte Felder: %s (erlaubt: %s)', $key, implode(', ', $unknownFields), implode(', ', self::FIELDS)));
            }
        }
    }

    public static function getGroups(): array
    {
        return ['users'];
    }

    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(User::class);

        foreach (self::USERS as $key => [$defaultEmail, $defaultName, $isAdmin]) {
            $microsoftId = $this->value($key, 'microsoftId') ?? '';
            $email = $this->value($key, 'email') ?? $defaultEmail;
            $name = $this->value($key, 'name') ?? $defaultName;

            $oauthId = $microsoftId !== ''
                ? self::OAUTH_PROVIDER.':'.$microsoftId
                : 'fixture:'.$key;

            $user = $repository->findOneBy(['oAuthUserId' => $oauthId])
                ?? $repository->findOneBy(['email' => $email]);

            if ($user === null) {
                $user = new User($oauthId, $email)
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
        return 'user.'.$key;
    }

    private function value(string $key, string $field): ?string
    {
        $value = trim((string) ($this->userData[$key][$field] ?? ''));

        return $value !== '' ? $value : null;
    }
}
