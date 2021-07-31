<?php

namespace App\Command;

use App\Entity\BudgetTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AutoBudgetCommand  extends Command
{
    protected $searches =
        [
            'Fast Food' => [
                ' HJ ',
                'DOMINOS'
            ]
        ];
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->em = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName("transactions:autobudget")
            ->setDescription("Automatically assign unassigned transactions");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $searches = $this->em->createQuery('SELECT s from AutoCodeSearch s')->getResult();

        // Find all unassigned transactions (no budget transactions assigned to them at all)
        $query = $this->em->createQuery(
            'SELECT t
            FROM Transaction t
            WHERE NOT EXISTS (
              SELECT b
              FROM BudgetTransaction b
              WHERE b.transaction = t
            )
            AND t.description LIKE :search
            '
        );

        foreach($searches as $search) {
            $query->setParameters(
                ['search' => "%" . $search->getSearch() . "%"]
            );


            $transactions = $query->getResult();
            foreach ($transactions as $transaction) {
                $budgetTransaction = new BudgetTransaction();
                $budgetTransaction->setAmount($transaction->getAmount());
                $budgetTransaction->setBudgetAccount($search->getBudgetAccount());
                $budgetTransaction->setTransaction($transaction);
                $output->writeln($transaction->getDescription());
                $output->writeln($search->getSearch());
                $output->writeln($search->getBudgetAccount()->getBudgetName());

                if ($search->getRename() != "") {
                    $transaction->setDescription($search->getRename());
                    $this->em->persist($transaction);
                }

                $this->em->persist($budgetTransaction);
            }
            $this->em->flush();
        }

        return 0;
    }



}