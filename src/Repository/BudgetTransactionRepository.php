<?php

namespace App\Repository;

use App\Entity\BudgetTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<BudgetTransaction>
 */
class BudgetTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, BudgetTransaction::class);
    }

    public function getPositiveBudgetSums(\DateTime $dateFrom, \DateTime $dateTo): array
    {
        return $this->getBudgetSums($dateFrom, $dateTo, true);
    }

    public function getNegativeBudgetSums(\DateTime $dateFrom, \DateTime $dateTo): array
    {
        return $this->getBudgetSums($dateFrom, $dateTo, false);
    }

    public function getBudgetSums(\DateTime $dateFrom, \DateTime $dateTo, bool $positive = true): array
    {
        $query = $this->createQueryBuilder('budgetTransaction')
            ->join('budgetTransaction.transaction', 'transaction')
            ->join('budgetTransaction.budgetAccount', 'budgetAccount')
            ->andWhere('transaction.date BETWEEN :dateFrom AND :dateTo')
            ->setParameter('dateFrom', $dateFrom)
            ->setParameter('dateTo', $dateTo)
            ->groupBy('budgetAccount');
        if ($positive) {
            return $query->select('budgetAccount.budget_name as budgetName, budgetAccount.id as budget_account_id, MIN(transaction.date) as mindate, MAX(transaction.date) as maxdate, SUM(budgetTransaction.amount) as positivesum')
                ->andWhere('transaction.amount = 0')
                ->andWhere('budgetTransaction.amount > 0')
                ->getQuery()->getResult();
        }

        return $query->select('budgetAccount.budget_name as budgetName, budgetAccount.id as budget_account_id, MIN(transaction.date) as mindate, MAX(transaction.date) as maxdate, SUM(budgetTransaction.amount) as negativesum')
            ->andWhere('transaction.amount != 0')
            ->andWhere('budgetTransaction.amount < 0')
            ->getQuery()->getResult();
    }

    public function getIncomeSums(\DateTime $dateFrom, \DateTime $dateTo): array
    {
        return $this->createQueryBuilder('budgetTransaction')
            ->select('budgetAccount.budget_name as budgetName,
                    budgetAccount.id as budget_account_id,
                    MIN(transaction.date) as mindate,
                    MAX(transaction.date) as maxdate,
                    SUM(budgetTransaction.amount) as positivesum')
            ->join('budgetTransaction.transaction', 'transaction')
            ->join('budgetTransaction.budgetAccount', 'budgetAccount')
            ->andWhere('transaction.amount != 0')
            ->andWhere('budgetTransaction.amount > 0')
            ->andWhere('transaction.date BETWEEN :dateFrom AND :dateTo')
            ->setParameters(['dateFrom' => $dateFrom, 'dateTo' => $dateTo])
            ->groupBy('budgetAccount.id')
            ->getQuery()->getResult();
    }

    public function getWeeklySums(\DateTime $dateFrom, \DateTime $dateTo): array
    {
        return $this->createQueryBuilder('budgetTransaction')
            ->select('YEARWEEK(transaction.date, 3) AS yearweeknum,
                    budgetAccount.budget_name as budgetName,
                    budgetAccount.id as budget_account_id,
                    SUM(budgetTransaction.amount) as weeksum')
            ->join('budgetTransaction.transaction', 'transaction')
            ->join('budgetTransaction.budgetAccount', 'budgetAccount')
            ->andWhere('transaction.date BETWEEN :dateFrom AND :dateTo')
            ->setParameters(['dateFrom' => $dateFrom, 'dateTo' => $dateTo])
            ->groupBy('budgetAccount.id, yearweeknum')
            ->getQuery()->getResult();
    }

    public function getWeeklySpends(\DateTime $dateFrom, \DateTime $dateTo): array
    {
        return $this->createQueryBuilder('budgetTransaction')
            ->select('YEARWEEK(transaction.date, 3) AS yearweeknum,
                    budgetAccount.budget_name as budgetName,
                    budgetAccount.id as budget_account_id,
                    SUM(budgetTransaction.amount) as weekspend')
            ->join('budgetTransaction.transaction', 'transaction')
            ->join('budgetTransaction.budgetAccount', 'budgetAccount')
            ->andWhere('budgetTransaction.amount < 0')
            ->andWhere('transaction.date BETWEEN :dateFrom AND :dateTo')
            ->setParameters(['dateFrom' => $dateFrom, 'dateTo' => $dateTo])
            ->groupBy('budgetAccount.id, yearweeknum')
            ->getQuery()->getResult();
    }
}
