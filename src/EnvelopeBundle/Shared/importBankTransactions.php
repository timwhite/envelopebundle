<?php

namespace EnvelopeBundle\Shared;

use EnvelopeBundle\Entity\Import;
use EnvelopeBundle\Entity\Transaction;

class importBankTransactions
{
    const ACCOUNT_TYPE_NAB = 'NAB';
    const ACCOUNT_TYPE_ANZ = 'ANC';
    const ACCOUNT_TYPE_UP = 'UP';
    const ACCOUNT_TYPE_ATHENA = 'ATHENA';
    const ACCOUNT_TYPE_FMC = 'FMC';
    const ACCOUNT_TYPES = [
        'NAB' => self::ACCOUNT_TYPE_NAB,
        'ANZ' => self::ACCOUNT_TYPE_ANZ,
        'UP' => self::ACCOUNT_TYPE_UP,
        'Athena' => self::ACCOUNT_TYPE_ATHENA,
        'FirstMac' => self::ACCOUNT_TYPE_FMC,
    ];
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

            // @TODO check header rows?
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
                $transaction->setExtra($processRow->extra);

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
        $extra = [];
        // ANZ and NAB differ for importing description
        switch ($fileType) {
            case self::ACCOUNT_TYPE_FMC:
                /**
                 * FirstMac format is:
                 *
                 * Firstline contains the account number, then
                 * Posted Date,Effective Date,Description,Debit,Credit,Balance
                 * Empty line
                 */
                if (sizeof($row) != 6) {
                    $this->unknown[] = implode(',', $row);
                    return false;
                }
                $description = $fullDescription = $row[2];
                $date = \DateTime::createFromFormat('d/m/Y', $row[0]);
                $amount = $row[3] ?: $row[4]; // Either the debit or the credit column will be filled in

                break;
            case self::ACCOUNT_TYPE_ANZ:
                // ANZ format is date,amount,description
                if (sizeof($row) != 3) {
                    $this->unknown[] = implode(',', $row);
                    return false;
                }
                $description = preg_replace("/ {2,}/", " ", $row[2]);
                $fullDescription = $description;


                $dateparts = explode('/', $row[0], 3);
                $date = new \DateTime($dateparts[2] . "/" . $dateparts[1] . "/" . $dateparts[0]);
                //$output->writeln($description);

                /*
                     * Get the amount. But remove any extra '+' at the start of the string, we know it's a positive number
                     * unless it has a - at the start
                     */
                $amount = ltrim($row[1], '+');
                // Remove any , characters in the string, they stuff things up too
                $amount = str_replace(',', '', $amount);
                break;
            case self::ACCOUNT_TYPE_ATHENA:
                /**
                 * Athena format is:
                 * Date, Description, Detail, Debit, Credit, Balance
                 * Date is "DD MON YY"
                 *
                 * Description is the transaction type
                 * New lines are allowed in the detail
                 * 01 Feb 2021,Loan repayment (EFT),"Savings
                 * Payment from T S White",$0.00,$650.00,-$176828.49
                 * 01 Feb 2021,Loan repayment (direct debit),"",$0.00,$162.04,-$177478.49

                 */
                if (sizeof($row) != 6) {
                    $this->unknown[] = implode(',', $row);
                    return false;
                }

                if ($row[0] === 'Date') {
                    // Header row
                    return false;
                }

                // Try the detail field as the description
                $description = $row[2];
                if ($description == "") {
                    // If detail is empty, just use the type instead
                    $description = $row[1];
                }
                $fullDescription = $row[1] . ': ' . $row[2];

                // Turn newlines into spaces
                $description = str_replace("\n", ' ', $description);
                $fullDescription = str_replace("\n", ' ', $fullDescription);

                $date = new \DateTime($row[0]);

                /*
                 * Get the amount. But remove any extra '$' at the start of the string, also remove the , characters
                 */
                $debit = - str_replace(',', '', ltrim($row[3], '-$'));
                $credit = str_replace(',', '', ltrim($row[4], '$'));

                $amount = $debit + $credit;
                

                break;
            case self::ACCOUNT_TYPE_NAB:
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
                 *
                 * New NAB format (2022-06) is
                 * Date,Amount,Account Number,,Transaction Type,Transaction Details,Balance,Category,Merchant Name
                 * No string quoting, Date is DD MON YY
                 * If no merchant name, then there will be less fields in the row
                 * 02 Feb 22,-20,1XXXXXXXX, ,MISCELLANEOUS DEBIT,VXXXX 01/02 GOOGLE CLOUD,123.45,Shopping,Google Cloud
                 * 27 Jan 22,8.00,1XXXXXXXX, ,INTER-BANK CREDIT,Internet Wordpress,131.45,Other income
                 */

                //NAB format date,amount,__,__,Type,Description,Balance,__
                if (sizeof($row) != 8 && sizeof($row) != 9) {
                    $this->unknown[] = implode(',', $row);
                    return false;
                }
                if ($row[0] === 'Date') {
                    // Header row
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

            case self::ACCOUNT_TYPE_UP:
                /**
                 * UP Bank CSV Format
                 * Time, BSB/Account number, Transaction Type, Payee, Description, Category, Tags,
                 * Subtotal (AUD), Currency, Subtotal (Transaction Currency), Fee (AUD),
                 * Round Up (AUD), Total (AUD), Payment Method, Settled Date
                 */
                if (sizeof($row) != 15) {
                    $this->unknown[] = implode(',', $row);
                    return false;
                }

                if ($row[0] === 'Time') {
                    // Header row
                    return false;
                }

                $fullDescription = "${row[2]}: ${row[3]} - ${row[4]}";
                if ($row[8] != 'AUD') {
                    $fullDescription .= " (${row[8]} ${row[9]})";
                }

                $description = "${row[3]} - ${row[4]}";

                $date = new \DateTime($row[0]);

                $amount = $row[12];

                $extra = [
                    'timestamp' => $row[0],
                    'bsb_account' => $row[1],
                    'transaction_type' => $row[2],
                    'payee' => $row[3],
                    //'description' => $row[4],
                    'category' => $row[5],
                    'tags' => $row[6],
                    //'subtotal_aud' => $row[7],
                    'currency' => $row[8],
                    'subtotal_currency' => $row[9],
                    //'fee' => $row[10],
                    //'roundup' => $row[11],
                    //'total_aud' => $row[12],
                    'payment_method' => $row[13],
                    'settlement_date' => $row[14],
                ];
                break;


            default:
                throw new \Exception('Invalid Account Type');

        }

        return (object)[
            'description' => $description,
            'fullDescription' => $fullDescription,
            'date' => $date,
            'amount' => $amount,
            'extra' => $extra,
        ];
    }

}


