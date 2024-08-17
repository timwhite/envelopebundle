<?php

namespace App\Repository;

use App\Entity\Budget\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Template>
 */
class BudgetTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, Template::class);
    }

    /**
     * @return Template[]
     */
    public function getUsersBudgetTemplates(): array
    {
        return $this->findBy(['access_group' => $this->security->getUser()->getAccessGroup()]);
    }

    /**
     * Get the Budget Template groups with the template transactions summed by group.
     */
    public function getBudgetTemplateGroupSums(): array
    {
        return $this->createQueryBuilder('budgetTemplate')
            ->select('budgetTemplate.id, budgetGroup.name, SUM(transactions.amount) as total')
            ->join('budgetTemplate.template_transactions', 'transactions')
            ->join('transactions.budgetAccount', 'budgetAccount')
            ->join('budgetAccount.budget_group', 'budgetGroup')
            ->where('budgetTemplate.access_group = :accessGroup')
            ->setParameter('accessGroup', $this->security->getUser()->getAccessGroup())
            ->groupBy('budgetTemplate.id, budgetAccount.budget_group')
            ->orderBy('budgetAccount.budget_group')
            ->getQuery()->getResult();
    }
}
