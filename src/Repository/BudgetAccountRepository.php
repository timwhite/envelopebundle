<?php

namespace App\Repository;

use App\Entity\BudgetAccount;
use App\Entity\BudgetGroup;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BudgetAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BudgetAccount::class);
    }

    public function getUserBudgetAccounts(User $user, int $accountId = null)
    {
        $query = $this->createQueryBuilder('a')
            ->join(BudgetGroup::class, 'g', 'WITH', 'a.budget_group = g')
            ->andWhere('g.access_group = :accessGroup')
            ->setParameter('accessGroup', $user->getAccessGroup());

        if ($accountId) {
            $query->andWhere('a.id = :accountId')
                ->setParameter('accountId', $accountId);
        }

        return $query->getQuery()->getResult();
    }

}