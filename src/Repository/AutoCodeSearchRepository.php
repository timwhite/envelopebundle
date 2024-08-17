<?php

namespace App\Repository;

use App\Entity\AutoCodeSearch;
use App\Entity\BudgetAccount;
use App\Entity\BudgetGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<AutoCodeSearch>
 */
class AutoCodeSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, AutoCodeSearch::class);
    }

    /**
     * @return AutoCodeSearch[]
     */
    public function findUsersSearches(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin(BudgetAccount::class, 'b', 'WITH', 's.budgetAccount = b')
            ->leftJoin(BudgetGroup::class, 'g', 'WITH', 'b.budget_group = g')
            ->where('g.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup())
            ->getQuery()->getResult();
    }

    //    /**
    //     * @return AutoCodeSearch[] Returns an array of AutoCodeSearch objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AutoCodeSearch
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
