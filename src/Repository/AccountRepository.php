<?php

namespace App\Repository;

use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * @return Account[]
     */
    public function getUsersAccounts(): array
    {
        return $this->findBy(['access_group' => $this->security->getUser()->getAccessGroup()]);
    }

    public function getBudgetTransferAccount(): Account
    {
        return $this->findOneBy([
            'access_group' => $this->security->getUser()->getAccessGroup(),
            'budgetTransfer' => true,
        ]);
    }
}
