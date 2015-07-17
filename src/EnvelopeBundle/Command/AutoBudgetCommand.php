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
            '
        );

        $transactions = $query->getResult();
        foreach($transactions as $transaction) {
            $output->writeln($transaction->getDescription());
            foreach($searches as $search) {
                if(strpos($transaction->getDescription(), $search->getSearch()) !== false)
                {
                    $budgetTransaction = new BudgetTransaction();
                    $budgetTransaction->setAmount($transaction->getAmount());
                    $budgetTransaction->setBudgetAccount($search->getBudgetAccount());
                    $budgetTransaction->setTransaction($transaction);
                    $output->writeln($transaction->getDescription());
                    $output->writeln($search->getSearch());
                    $output->writeln($search->getBudgetAccount()->getBudgetName());

                    if($search->getRename() != "")
                    {
                        $transaction->setDescription($search->getRename());
                        $em->persist($transaction);
                    }

                    $em->persist($budgetTransaction);
                    break;
                }

            }
            $em->flush();
        }
        // Run the descriptions against a list of keywords per budget

    }

}