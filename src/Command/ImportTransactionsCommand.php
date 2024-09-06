<?php

namespace App\Command;

use App\Entity\Import;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// @TODO Update this to use importBankTransactions
#[AsCommand(name: 'account:import', description: 'Imports Bank Transactions into Account')]
class ImportTransactionsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly AccountRepository $accountRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('accountName', InputArgument::REQUIRED, 'The transaction account name')
            ->addArgument('inputFile', InputArgument::REQUIRED, 'CSV file of transactions')
            ->addOption('import_duplicates', null, InputOption::VALUE_NONE, 'Import suspected duplicates')
            ->addOption('importANZ', null, InputOption::VALUE_NONE, 'Import an ANZ CSV file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;
        $inputFile = $input->getArgument('inputFile');

        if (!file_exists($inputFile)) {
            $output->writeln('Unable to open input file');
            exit(1);
        }

        $account = $this->accountRepository->findOneByName($input->getArgument('accountName'));
        if (!$account) {
            $output->writeln('Unable to find that account');
            exit(1);
        }

        if (($handle = fopen($inputFile, 'r')) !== false) {
            $import = new Import();
            $em->persist($import);
            $em->flush();

            while (($row = fgetcsv($handle)) !== false) {
                // ANZ and NAB differ for importing description
                if ($input->getOption('importANZ')) {
                    // ANZ format is date,amount,description
                    if (sizeof($row) < 3) {
                        continue;
                    }
                    $description = preg_replace('/ {2,}/', ' ', $row[2]);
                    $fullDescription = $description;

                    $dateparts = explode('/', $row[0], 3);
                    $date = new \DateTime($dateparts[2].'/'.$dateparts[1].'/'.$dateparts[0]);
                // $output->writeln($description);
                } else {
                    // NAB format date,amount,__,__,Type,Description,Balance,__
                    if (sizeof($row) < 5) {
                        continue;
                    }
                    $description = preg_replace('/ {2,}/', ' ', $row[5]);
                    if ('' == $description) {
                        $description = $row[4];
                    }
                    $fullDescription = $row[4].':'.preg_replace('/ {2,}/', ' ', $row[5]);

                    $date = new \DateTime($row[0]);
                }

                // Limit to transactions new
                if ($date < new \DateTime('2015-07-01 00:00:00')) {
                    $output->write('.');
                    continue;
                }
                $output->write('#');

                $amount = $row[1];

                if (!$input->getOption('import_duplicates')) {
                    // Attempt to detect duplicate transaction
                    $query = $em->createQuery('
                      SELECT t FROM App\Entity\Transaction t
                      WHERE t.account = :account
                      AND t.fullDescription = :fulldesc
                      AND t.amount = :amount
                      AND t.date = :tdate
                      AND t.import != :import');
                    $query->setParameters(
                        [
                            'account' => $account,
                            'fulldesc' => $fullDescription,
                            'amount' => $amount,
                            'tdate' => $date,
                            'import' => $import->getId(),
                        ]
                    );
                    $results = $query->getResult();
                    if (sizeof($results) > 0) {
                        $output->writeln("Not importing duplicate($import) transaction: ".$date->format('Y-m-d H:i:s').": $amount, $fullDescription");
                        continue;
                    }
                }

                $transaction = new Transaction();
                $transaction->setAccount($account);
                $transaction->setDate($date);
                $transaction->setAmount($amount);
                $transaction->setDescription($description);
                $transaction->setFullDescription($fullDescription);
                $transaction->setImport($import);

                $em->persist($transaction);
            }
            $em->flush();
            $output->writeln('*');
        }

        return Command::SUCCESS;
    }
}
