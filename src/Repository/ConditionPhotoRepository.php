<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ConditionPhoto;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConditionPhoto>
 */
class ConditionPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConditionPhoto::class);
    }

    /**
     * Fotos einer Reservierung, die $user sehen darf: Admins alle, sonst nur die eigenen.
     * Muss dieselbe Regel abbilden wie ConditionPhotoVoter::VIEW.
     *
     * @return list<ConditionPhoto>
     */
    public function findVisibleFor(Reservation $reservation, User $user): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.reservation = :reservation')
            ->setParameter('reservation', $reservation)
            ->orderBy('p.createdAt', 'ASC');

        if (!$user->isAdmin()) {
            $qb->andWhere('p.takenBy = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }
}
