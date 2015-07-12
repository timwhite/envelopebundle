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


class ImportTransactionsCommand  extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("account:import")
            ->setDescription("Imports Bank Transactions into Account")
            ->addArgument('accountID', InputArgument::REQUIRED, 'The transaction account ID')
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
            ->find($input->getArgument('accountID'));

        if (($handle = fopen($inputFile, "r")) !== FALSE) {
            while(($row = fgetcsv($handle)) !== FALSE) {
                if(sizeof($row) < 5) {
                    continue;
                }
                var_dump($row);
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

                $em->persist($transaction);
            }
            $em->flush();
        }
    }
}