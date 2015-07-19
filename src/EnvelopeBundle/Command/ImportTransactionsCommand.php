<?php

namespace EnvelopeBundle\Command;

use EnvelopeBundle\Entity\Import;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use EnvelopeBundle\Entity\Account;
use EnvelopeBundle\Entity\Transaction;


class ImportTransactionsCommand  extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("account:import")
            ->setDescription("Imports Bank Transactions into Account")
            ->addArgument('accountName', InputArgument::REQUIRED, 'The transaction account name')
            ->addArgument('inputFile', InputArgument::REQUIRED, 'CSV file of transactions')
            ->addOption('import_duplicates', null, InputOption::VALUE_NONE, 'Import suspected duplicates')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get("doctrine")->getManager();
        $inputFile = $input->getArgument('inputFile');

        if(!file_exists($inputFile)) {
            $output->writeln("Unable to open input file");
            exit(1);
        }

        $account = $em->getRepository('EnvelopeBundle:Account')
            ->findOneByName($input->getArgument('accountName'));
        if(!$account) {
            $output->writeln("Unable to find that account");
            exit(1);
        }



        if (($handle = fopen($inputFile, "r")) !== FALSE) {
            $import = new Import();
            $em->persist($import);
            $em->flush();

            while(($row = fgetcsv($handle)) !== FALSE) {
                if(sizeof($row) < 5) {
                    continue;
                }
                // date,amount,__,__,Type,Description,Balance,__
                $date = new \DateTime($row[0]);
                $amount = $row[1];
                $description = preg_replace("/ {2,}/", " ", $row[5]);
                if($description == "")
                {
                    $description = $row[4];
                }
                $fullDescription = $row[4] . ':' . preg_replace("/ {2,}/", " ", $row[5]);

                if(!$input->getOption('import_duplicates'))
                {
                    // Attempt to detect duplicate transaction
                    $query = $em->createQuery('
                      SELECT t FROM EnvelopeBundle\Entity\Transaction t
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
                            'import' => $import->getId()
                        ]
                    );
                    $results = $query->getResult();
                    if(sizeof($results) > 0)
                    {
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
        }
    }
}