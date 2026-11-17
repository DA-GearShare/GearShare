<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\ConditionPhoto;
use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Uid\Uuid;

/**
 * Einzige Stelle, die entscheidet, wer Zustandsfotos sehen und hochladen darf.
 *
 *   VIEW   (Subject: ConditionPhoto)  Admin oder die Person, die das Foto hochgeladen hat.
 *   UPLOAD (Subject: Reservation)     Admin, AnfragerIn oder verantwortliche Person der Reservierung.
 *
 * @extends Voter<string, ConditionPhoto|Reservation>
 */
final class ConditionPhotoVoter extends Voter
{
    public const string VIEW = 'CONDITION_PHOTO_VIEW';
    public const string UPLOAD = 'CONDITION_PHOTO_UPLOAD';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return ($attribute === self::VIEW && $subject instanceof ConditionPhoto)
            || ($attribute === self::UPLOAD && $subject instanceof Reservation);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, Vote|null $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => self::isSameUser($subject->getTakenBy(), $user),
            self::UPLOAD => self::isSameUser($subject->getRequestedBy(), $user)
                || self::isSameUser($subject->getResponsibleUser(), $user),
            default => false,
        };
    }

    /** Vergleich über die ID, nicht über die Objekt-Identität (Doctrine-Proxies). */
    private static function isSameUser(?User $a, User $b): bool
    {
        $idA = $a?->getUserId();
        $idB = $b->getUserId();

        return $idA instanceof Uuid && $idB instanceof Uuid && $idA->equals($idB);
    }
}
