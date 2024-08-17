<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\AutoCodeSearch;
use App\Entity\BudgetTransaction;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, Transaction::class);
    }

    //    public function find($id, LockMode|int|null $lockMode = null, int|null $lockVersion = null): ?object
    //    {
    //        return $this->findOneBy(['id' => $id, 'access_group' => $this->security->getUser()->getAccessGroup()]);
    //    }

    public function getUnbalancedTransactions()
    {
        return $this->createQueryBuilder('t')
            ->leftJoin(BudgetTransaction::class, 'bt', 'WITH', 'bt.transaction = t.id')
            ->leftJoin(Account::class, 'a', 'WITH', 't.account = a.id')
            ->where('a.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup())
            ->groupBy('t.id')
            ->having('(COUNT(bt.amount) = 0 AND t.amount != 0) OR SUM(bt.amount) != t.amount')
            ->orderBy('t.date')
            ->getQuery()->getResult();
    }

    /**
     * @return Transaction[]
     */
    public function searchTransactions(AutoCodeSearch $search): array
    {
        $query = $this->createQueryBuilder('t')
            ->leftJoin(Account::class, 'a', 'WITH', 't.account = a')
            ->where('NOT EXISTS (SELECT b FROM '.BudgetTransaction::class.' b WHERE b.transaction = t.id)')
            ->andWhere('t.description LIKE :search')
            ->setParameter('search', '%'.$search->getSearch().'%')
            ->andWhere('a.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup());

        if ($search->getAmount()) {
            $query->andWhere('t.amount = :amount')
                ->setParameter('amount', $search->getAmount());
        }

        return $query->getQuery()->getResult();
    }

    public function persistTransaction(Transaction $transaction): Transaction
    {
        $this->getEntityManager()->persist($transaction);
        $this->getEntityManager()->flush();

        return $transaction;
    }

    public function findUserFirstTransactionDate()
    {
        return $this->createQueryBuilder('transaction')
            ->select('MIN(transaction.date)')
            ->leftJoin(Account::class, 'a', 'WITH', 'transaction.account = a')
            ->andWhere('a.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findUserLastTransactionDate()
    {
        return $this->createQueryBuilder('transaction')
            ->select('MAX(transaction.date)')
            ->leftJoin(Account::class, 'a', 'WITH', 'transaction.account = a')
            ->andWhere('a.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
