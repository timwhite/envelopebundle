<?php

namespace App\Command;

use App\Entity\AccessGroup;
use App\Entity\BudgetTransaction;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TeamTNT\TNTSearch\Classifier\TNTClassifier;

class TrainTNTCommand extends Command
{
    protected static $defaultName = 'train:budget';

    public function __construct(private readonly EntityManagerInterface $entityManager,
        private readonly TransactionRepository $transactionRepository,
        private readonly AccountRepository $accountRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->entityManager->getRepository(AccessGroup::class)->findAll() as $accessGroup) {

            $classifier = new TNTClassifier();
            $accounts = $this->accountRepository->findBy(['access_group' => $accessGroup]);
            foreach ($accounts as $account) {
                $transactions = $this->transactionRepository->findBy(['account' => $account]);
                foreach ($transactions as $transaction) {
                    /** @var Transaction $transaction */
                    if (1 === sizeof($transaction->getBudgetTransactions())) {
                        /** @var BudgetTransaction $budgetTransaction */
                        $budgetTransaction = $transaction->getBudgetTransactions()->first();
                        $description = \App\Service\TNTClassifier::prepareString($transaction->getFullDescription());
                        $budgetId = $budgetTransaction->getBudgetAccount()->getId();
                        $budgetName = $budgetTransaction->getBudgetAccount()->getBudgetName();
                        $classifier->learn($description, $budgetId);
                        $output->writeln("Learning '$description' as category '$budgetName'");
                    }
                }
            }

            $accessGroup->storeClassifier($classifier);
            $this->entityManager->persist($accessGroup);
            $this->entityManager->flush();

        }

        return Command::SUCCESS;
    }
}
