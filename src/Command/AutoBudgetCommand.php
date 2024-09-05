<?php

namespace App\Command;

use App\Entity\Account;
use App\Entity\BudgetTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'transactions:autobudget', description: 'Automatically assign unassigned transactions')]
class AutoBudgetCommand extends Command
{
    protected $searches =
        [
            'Fast Food' => [
                ' HJ ',
                'DOMINOS',
            ],
        ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        // @TODO make this use a service that is shared with the web interface for running this
        $em = $this->entityManager;
        // $account = $em->getRepository('EnvelopeBundle:Account')
        //    ->find($input->getArgument('accountID'));
        $searches = $em->createQuery('SELECT s from EnvelopeBundle:AutoCodeSearch s')->getResult();

        // Find all unassigned transactions (no budget transactions assigned to them at all)
        $query = $em->createQuery(
            'SELECT t
            FROM EnvelopeBundle:Transaction t
            WHERE NOT EXISTS (
              SELECT b
              FROM EnvelopeBundle:BudgetTransaction b
              WHERE b.transaction = t
            )
            AND t.description LIKE :search
            '
        );

        foreach ($searches as $search) {
            $query->setParameters(
                ['search' => '%'.$search->getSearch().'%']
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

                if ('' != $search->getRename()) {
                    $transaction->setDescription($search->getRename());
                    $em->persist($transaction);
                }

                $em->persist($budgetTransaction);
            }
            $em->flush();
        }
    }
}
