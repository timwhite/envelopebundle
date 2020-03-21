<?php

namespace EnvelopeBundle\Shared;

use EnvelopeBundle\Entity\Import;
use EnvelopeBundle\Entity\Transaction;

class importBankTransactions
{
    private $duplicates = [];
    private $ignored = [];
    private $unknown = [];
    private $import;

    private $uncleared_searches = [
        'OUTSTANDING TRANS'
    ];

    public function importBankFile($em, $inputFile, $account, $accountType, $importDuplicates = false)
    {
        if (($handle = fopen($inputFile, "r")) !== FALSE) {
            $this->import = new Import();
            $em->persist($this->import);
            $em->flush();

            while (($row = fgetcsv($handle)) !== FALSE) {
                $processRow = $this->processRow($row, $accountType);
                if (!$processRow) {
                    // Row's we've added to ignored already
                    continue;
                }


                if($this->checkUnclearedTransaction($processRow->fullDescription))
                {
                    $this->ignored[] = [
                        'date' => $processRow->date,
                        'fullDescription' => $processRow->fullDescription,
                        'amount' =>$processRow->amount
                    ];
                    continue;
                }

                /*
                // Limit to transactions in this financial year //TODO remove this?
                if ($date < new \DateTime("2015-07-01 00:00:00")) {
                    $this->ignored[] = [
                        'date' => $date,
                        'fullDescription' => $fullDescription,
                        'amount' =>$amount
                    ];
                    continue;
                }*/

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
                            'fulldesc' => $processRow->fullDescription,
                            'amount' => $processRow->amount,
                            'tdate' => $processRow->date,
                            'import' => $this->import->getId()
                        ]
                    );
                    $results = $query->getResult();
                    if (sizeof($results) > 0) {
                        $this->duplicates[] = [
                            "date" => $processRow->date,
                            "fullDescription" => $processRow->fullDescription,
                            "amount" =>$processRow->amount
                        ];
                        continue;
                    }
                }

                $transaction = new Transaction();
                $transaction->setAccount($account);
                $transaction->setDate($processRow->date);
                $transaction->setAmount($processRow->amount);
                $transaction->setDescription($processRow->description);
                $transaction->setFullDescription($processRow->fullDescription);
                $transaction->setImport($this->import);

                $em->persist($transaction);
            }
            $em->flush();

        }

    }

    public function getDuplicates()
    {
        return array_filter($this->duplicates);
    }

    // Returns transaction we skipped due to it matching a uncleared transaction filter
    public function getIgnored()
    {
        return array_filter($this->ignored);
    }

    // Returns rows that we don't know
    public  function getUnknown()
    {
        return array_filter($this->unknown);
    }

    public function getImport()
    {
        return $this->import;
    }

    private function checkUnclearedTransaction($fullDescription) {
        foreach($this->uncleared_searches as $search) {
            if(strpos($fullDescription, $search) !== false) return true; // stop on first true result
        }
        // Check for a Debit transaction with no description, this is uncleared
        if($fullDescription == 'EFTPOS DEBIT:')
        {
            return true;
        }
        return false;
    }

    /**
     * @param $row
     * @param $fileType
     *
     * @return bool|object
     * @throws \Exception
     */
    private function processRow($row, $fileType)
    {
        // ANZ and NAB differ for importing description
        switch ($fileType) {
            case 'ANZ':
                // ANZ format is date,amount,description
                if (sizeof($row) != 3) {
                    $this->unknown[] = implode(',', $row);
                    return false;
                }
                $description = preg_replace("/ {2,}/", " ", $row[2]);
                $fullDescription = $description;


                $dateparts = explode('/', $row[0], 3);
                $date = new\DateTime($dateparts[2] . "/" . $dateparts[1] . "/" . $dateparts[0]);
                //$output->writeln($description);

                /*
                     * Get the amount. But remove any extra '+' at the start of the string, we know it's a positive number
                     * unless it has a - at the start
                     */
                $amount = ltrim($row[1], '+');
                // Remove any , characters in the string, they stuff things up too
                $amount = str_replace(',', '', $amount);
                break;
            case 'NAB':
                /**
                 * Old NAB Format was
                 * Date,Amount,_,_,Type,Description,Balance,_
                 * No string quoting, date was DD-MON-YY
                 * 30-Nov-17,-14.95,,,MISCELLANEOUS DEBIT,V1234 28/11 KFC ,123.45,
                 *
                 * New NAB format is
                 * "Date","Amount","_",_,"Type","Description","Balance"
                 * String quoting. Date is DD MON YY
                 * "28 Dec 17","-14.95","000000000000",,"MISCELLANEOUS DEBIT","V1234 25/12 KFC","+456.78"
                 */

                //NAB format date,amount,__,__,Type,Description,Balance,__
                if (sizeof($row) != 7) {
                    $this->unknown[] = implode(',', $row);
                    return false;
                }
                $description = preg_replace("/ {2,}/", " ", $row[5]);
                if ($description == "") {
                    $description = $row[4];
                }
                $fullDescription = $row[4] . ':' . preg_replace("/ {2,}/", " ", $row[5]);

                $date = new \DateTime($row[0]);

                /*
                 * Get the amount. But remove any extra '+' at the start of the string, we know it's a positive number
                 * unless it has a - at the start
                 */
                $amount = ltrim($row[1], '+');
                // Remove any , characters in the string, they stuff things up too
                $amount = str_replace(',', '', $amount);
                break;
            default:
                throw new \Exception('Invalid Account Type');

        }

        return (object)[
            'description' => $description,
            'fullDescription' => $fullDescription,
            'date' => $date,
            'amount' => $amount,
        ];
    }

}


