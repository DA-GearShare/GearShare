<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
use Doctrine\ORM\Tools\Pagination\WindowPage;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param $id
     * @return User|null
     */
    public function findOneById($id): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.userId = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string|null $search
     * @param int $page
     * @param int $pageSize
     * @return WindowPage
     */
    public function findPaginated(?string $search, int $page, int $pageSize): WindowPage
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.userId', 'ASC');

        if ($search !== null && $search !== '') {
            $qb->andWhere('u.displayName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return new OffsetPaginator()
            ->paginate($qb->getQuery(), new Window(($page - 1) * $pageSize, $pageSize));
    }
}
