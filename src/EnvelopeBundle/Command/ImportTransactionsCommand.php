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
            ->addArgument('inputFile', InputArgument::REQUIRED, 'CSV file of transactions');

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

            while(($row = fgetcsv($handle)) !== FALSE) {
                if(sizeof($row) < 5) {
                    continue;
                }
                // date,amount,__,__,Type,Description,Balance,__
                $date = new \DateTime($row[0]);
                $amount = $row[1];
                $description = $row[5];
                if($description == "")
                {
                    $description = $row[4];
                }
                $transaction = new Transaction();
                $transaction->setAccount($account);
                $transaction->setDate($date);
                $transaction->setAmount($amount);
                $transaction->setDescription($description);
                $transaction->setFullDescription($row[4] . ':' . $row[5]);
                $transaction->setImport($import);

                $em->persist($transaction);
            }
            $em->flush();
        }
    }
}