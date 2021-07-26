<?php

namespace App\Repository;

use App\Entity\Budget\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Template|null find($id, $lockMode = null, $lockVersion = null)
 * @method Template|null findOneBy(array $criteria, array $orderBy = null)
 * @method Template[]    findAll()
 * @method Template[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BudgetTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    /**
     * @param $accessGroup
     *
     * @return Template[]
     */
    public function findAllLimitedAccessGroup($accessGroup)
    {
        return $this->createQueryBuilder('template')
            ->where('template.access_group = :accessGroup')
            ->setParameter('accessGroup', $accessGroup)
            ->getQuery()->getResult();
    }

    public function findGroupSums($accessGroup)
    {
        $templateSums =  $this->createQueryBuilder('template')
            ->select('template.id', 'budgetGroup.name', 'SUM(transactions.amount) as total')
            ->leftJoin('template.template_transactions', 'transactions')
            ->leftJoin('transactions.budgetAccount', 'budgetAccount')
            ->leftJoin('budgetAccount.budget_group', 'budgetGroup')
            ->where('template.access_group = :accessGroup')
            ->setParameter('accessGroup', $accessGroup)
            ->groupBy('template.id')
            ->addGroupBy('budgetAccount.budget_group')
            ->orderBy('budgetAccount.budget_group')
            ->getQuery()->getResult();

        $groupSums = [];
        foreach ($templateSums as $templateSumPart) {
            $groupSums[$templateSumPart['id']][] = $templateSumPart;
        }

        return $groupSums;
    }

    // /**
    //  * @return Template[] Returns an array of Template objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Template
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
