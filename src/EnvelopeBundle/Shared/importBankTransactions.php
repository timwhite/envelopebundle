<?php

namespace EnvelopeBundle\Shared;

use EnvelopeBundle\Entity\Import;
use EnvelopeBundle\Entity\Transaction;

class importBankTransactions
{
    private $duplicates = [];
    private $import;

    public function importBankFile($em, $inputFile, $account, $accountType, $importDuplicates = false)
    {
        dump($accountType);
        if (($handle = fopen($inputFile, "r")) !== FALSE) {
            $this->import = new Import();
            $em->persist($this->import);
            $em->flush();

            while (($row = fgetcsv($handle)) !== FALSE) {


                // ANZ and NAB differ for importing description
                if ($accountType == 'ANZ') {
                    // ANZ format is date,amount,description
                    if (sizeof($row) < 3) {
                        continue;
                    }
                    $description = preg_replace("/ {2,}/", " ", $row[2]);
                    $fullDescription = $description;

                    $dateparts = explode('/', $row[0], 3);
                    $date = new\DateTime($dateparts[2] . "/" . $dateparts[1] . "/" . $dateparts[0]);
                    //$output->writeln($description);
                } elseif ($accountType == 'NAB') {
                    //NAB format date,amount,__,__,Type,Description,Balance,__
                    if (sizeof($row) < 5) {
                        continue;
                    }
                    $description = preg_replace("/ {2,}/", " ", $row[5]);
                    if ($description == "") {
                        $description = $row[4];
                    }
                    $fullDescription = $row[4] . ':' . preg_replace("/ {2,}/", " ", $row[5]);

                    $date = new \DateTime($row[0]);
                } else {
                    throw new \Exception('Invalid Account Type');
                }


                // Limit to transactions new
                if ($date < new \DateTime("2015-07-01 00:00:00")) {
                    continue;
                }

                $amount = $row[1];

                if (!$importDuplicates) {
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
                            'import' => $this->import->getId()
                        ]
                    );
                    $results = $query->getResult();
                    if (sizeof($results) > 0) {
                        $this->duplicates[] = [
                            "date" => $date,
                            "fullDescription" => $fullDescription,
                            "amount" =>$amount
                        ];
                        continue;
                    }
                }

                $transaction = new Transaction();
                $transaction->setAccount($account);
                $transaction->setDate($date);
                $transaction->setAmount($amount);
                $transaction->setDescription($description);
                $transaction->setFullDescription($fullDescription);
                $transaction->setImport($this->import);

                $em->persist($transaction);
            }
            $em->flush();

        }

    }

    public function getDuplicates()
    {
        return $this->duplicates;
    }

    public function getImport()
    {
        return $this->import;
    }
}