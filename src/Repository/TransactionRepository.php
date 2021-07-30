<?php

namespace App\Repository;

use App\Entity\AccessGroup;
use App\Entity\AutoCodeSearch;
use App\Entity\BudgetTransaction;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\NodeVisitor\TranslationNodeVisitor;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * @return Transaction[]
     *
     * @param $accessGroup
     */
    public function findAllLimitedAccessGroup($accessGroup)
    {
        return $this->createQueryBuilder('transaction')
            ->leftJoin('transaction.account', 'account')
            ->where('account.access_group = :accessGroup')
            ->setParameter('accessGroup', $accessGroup)
            ->getQuery()->getResult();
    }

    /**
     * Find unassigned budget transactions so we can code them
     *
     * @param AutoCodeSearch $search
     * @param AccessGroup|int    $accessGroup
     *
     * @return Transaction[]
     */
    public function findUnassignedTransactions(AutoCodeSearch $search, $accessGroup)
    {
        $searchString = $search->getSearch();
        $qb = $this->createQueryBuilder('t');
        $qb
            ->leftJoin('t.account', 'a')
            ->where(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $this->_em->createQueryBuilder()
                            ->select('b')
                            ->from(BudgetTransaction::class, 'b')
                            ->where('b.transaction = t')
                        ->getDQL()
                    )
                )
            )
            ->andWhere('t.description LIKE :searchString')
            ->andWhere('a.access_group = :accessGroup')
            ->setParameter('searchString', "%$searchString%")
            ->setParameter('accessGroup', $accessGroup)
            ;
        if ($search->getAmount()) {
            $qb->andWhere('t.amount = :amount')
                ->setParameter('amount', $search->getAmount());
        }

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return Transaction[] Returns an array of Transaction objects
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
    public function findOneBySomeField($value): ?Transaction
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
