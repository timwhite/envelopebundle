<?php

namespace App\Repository;

use App\Entity\AutoCodeSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AutoCodeSearch|null find($id, $lockMode = null, $lockVersion = null)
 * @method AutoCodeSearch|null findOneBy(array $criteria, array $orderBy = null)
 * @method AutoCodeSearch[]    findAll()
 * @method AutoCodeSearch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AutoCodeSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutoCodeSearch::class);
    }

    /**
     * @param $accessGroup
     *
     * @return int|mixed|string
     */
    public function findByAccessGroup($accessGroup)
    {
        return $this->createQueryBuilder('search')
            ->leftJoin('search.budgetAccount', 'budgetAccount')
            ->leftJoin('budgetAccount.budget_group', 'budgetGroup')
            ->where('budgetGroup.access_group = :accessGroup')
            ->setParameter('accessGroup', $accessGroup)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return AutoCodeSearch[] Returns an array of AutoCodeSearch objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AutoCodeSearch
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
