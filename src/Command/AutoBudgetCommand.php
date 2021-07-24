<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
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
        $searches = $this->em->createQuery('SELECT s from EnvelopeBundle:AutoCodeSearch s')->getResult();

        // Find all unassigned transactions (no budget transactions assigned to them at all)
        $query = $this->em->createQuery(
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
    }



}