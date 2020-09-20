<?php

namespace EnvelopeBundle\Shared;

use Doctrine\ORM\EntityManagerInterface;
use EnvelopeBundle\Entity\AutoCodeSearch;
use EnvelopeBundle\Entity\BudgetTransaction;

class autoCodeTransactions
{
    private $codedResults = [];

    /**
     * @param $em EntityManagerInterface
     * @param $accessgroup
     */
    public function codeTransactions($em, $accessgroup)
    {
        /** @var AutoCodeSearch[] $searches */
        $searches = $em->createQuery('
          SELECT s
          FROM EnvelopeBundle:AutoCodeSearch s
          LEFT JOIN EnvelopeBundle:BudgetAccount b WITH s.budgetAccount = b
          LEFT JOIN EnvelopeBundle:BudgetGroup g WITH b.budget_group = g
          WHERE g.access_group = :accessgroup
          ')
            ->setParameters(
                [
                    'accessgroup' => $accessgroup
                ])
            ->getResult();

        foreach ($searches as $search) {
            $amountQuery = '';
            if ($search->getAmount()) {
                $amountQuery = 'AND t.amount = :amount';
            }
            // Find all unassigned transactions (no budget transactions assigned to them at all)
            $query = $em->createQuery(
                'SELECT t
            FROM EnvelopeBundle:Transaction t
            JOIN EnvelopeBundle:Account a
            WHERE NOT EXISTS (
              SELECT b
              FROM EnvelopeBundle:BudgetTransaction b
              WHERE b.transaction = t
            )
            AND t.description LIKE :search
            AND a.access_group = :accessgroup ' . $amountQuery
            );

            $query->setParameters(
                ['search' => "%" . $search->getSearch() . "%",
                    'accessgroup' => $accessgroup
                ]
            );

            if ($search->getAmount()) {
                $query->setParameter('amount', $search->getAmount());
            }

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