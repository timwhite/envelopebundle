<?php

namespace EnvelopeBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use EnvelopeBundle\Entity\Account;
use EnvelopeBundle\Entity\Transaction;
use EnvelopeBundle\Entity\BudgetTransaction;


class AutoBudgetCommand  extends ContainerAwareCommand
{
    protected $searches =
        [
            'Fast Food' => [
                ' HJ ',
                'DOMINOS'
            ]
        ];

    protected function configure()
    {
        $this
            ->setName("transactions:autobudget")
            ->setDescription("Automatically assign unassigned transactions");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get("doctrine")->getManager();
        //$account = $em->getRepository('EnvelopeBundle:Account')
        //    ->find($input->getArgument('accountID'));

        // Find all unassigned transactions (no budget transactions assigned to them at all)
        $query = $em->createQuery(
            'SELECT t
            FROM EnvelopeBundle:Transaction t
            WHERE NOT EXISTS (
              SELECT b
              FROM EnvelopeBundle:BudgetTransaction b
              WHERE b.transaction = t
            )
            '
        );

        $transactions = $query->getResult();
        foreach($transactions as $transaction) {
            $output->writeln($transaction->getDescription());
            foreach($this->searches as $searchBudget => $searchDescriptions) {
                // Load Budget Account
                $budgetAccount = $this->loadBudgetAccount($searchBudget);
                foreach($searchDescriptions as $searchDescription)
                {
                    if(strpos($transaction->getDescription(), $searchDescription) !== false)
                    {
                        $budgetTransaction = new BudgetTransaction();
                        $budgetTransaction->setAmount($transaction->getAmount());
                        $budgetTransaction->setBudgetAccount($budgetAccount);
                        $budgetTransaction->setTransaction($transaction);
                        $output->writeln($transaction->getDescription());
                        $output->writeln($searchDescription);
                        $output->writeln($searchBudget);
                        $em->persist($budgetTransaction);
                    }
                }
                $em->flush();
            }
        }
        // Run the descriptions against a list of keywords per budget

    }

    protected function loadBudgetAccount($budgetName) {
        $em = $this->getContainer()->get("doctrine")->getManager();
        return $em->getRepository('EnvelopeBundle:BudgetAccount')
            ->findOneBy(['budget_name' => $budgetName]);
    }
}