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

    public function getUsersAccounts()
    {
        return $this->createQueryBuilder('a')
            ->where('a.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup())
            ->getQuery()->getResult();
    }


}