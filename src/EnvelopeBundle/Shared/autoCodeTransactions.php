<?php

namespace EnvelopeBundle\Shared;

use EnvelopeBundle\Entity\BudgetTransaction;

class autoCodeTransactions
{
    private $codedResults = [];

    public function codeTransactions($em)
    {
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
            AND t.description LIKE :search
            '
        );

        foreach ($searches as $search) {
            $query->setParameters(
                ['search' => "%" . $search->getSearch() . "%"]
            );


            $transactions = $query->getResult();
            foreach ($transactions as $transaction) {
                $budgetTransaction = new BudgetTransaction();
                $budgetTransaction->setAmount($transaction->getAmount());
                $budgetTransaction->setBudgetAccount($search->getBudgetAccount());
                $budgetTransaction->setTransaction($transaction);
                $this->codedResults[] = [
                    "transaction" => $transaction,
                    "search" => $search
                ];

                if ($search->getRename() != "") {
                    $transaction->setDescription($search->getRename());
                    $em->persist($transaction);
                }

                $em->persist($budgetTransaction);
            }
            $em->flush();
        }
    }

    public function getResults()
    {
        return $this->codedResults;
    }
}