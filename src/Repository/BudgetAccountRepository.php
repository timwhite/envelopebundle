<?php

namespace App\Repository;

use App\Entity\BudgetAccount;
use App\Entity\BudgetGroup;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @method BudgetAccount|null find($id, $lockMode = null, $lockVersion = null)
 */
class BudgetAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, BudgetAccount::class);
    }

    /**
     * @return BudgetAccount[]|null
     */
    public function getUserBudgetAccounts(?int $accountId = null): ?array
    {
        $query = $this->createQueryBuilder('a')
            ->join(BudgetGroup::class, 'g', 'WITH', 'a.budget_group = g')
            ->andWhere('g.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup());

        if ($accountId) {
            $query->andWhere('a.id = :accountId')
                ->setParameter('accountId', $accountId);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @param string $name
     * @return BudgetAccount|null
     * @throws NonUniqueResultException
     */
    public function getUserBudgetAccountByName(string $name): ?BudgetAccount
    {
        $query = $this->createQueryBuilder('a')
            ->join(BudgetGroup::class, 'g', 'WITH', 'a.budget_group = g')
            ->andWhere('g.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup())
            ->andWhere('a.budget_name = :name')
            ->setParameter('name', $name);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Get query builder filtered by current security user access groups.
     */
    public function getSecurityUserBudgetAccountsQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('budgetAccount')
            ->leftJoin(BudgetGroup::class, 'budgetGroup', 'WITH', 'budgetAccount.budget_group = budgetGroup')
            ->where('budgetGroup.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup());
    }
}
