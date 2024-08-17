<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BudgetTransaction.
 */
#[ORM\Table]
#[ORM\Entity]
class BudgetTransaction
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * We need EAGER loading due to us displaying the balance on our _toString method, which requires all
     * the transactions.
     */
    #[ORM\JoinColumn(name: 'transaction_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Transaction::class, inversedBy: 'budget_transactions', fetch: 'EAGER')]
    private $transaction;

    #[ORM\JoinColumn(name: 'budget_account_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \BudgetAccount::class, inversedBy: 'budget_transactions')]
    private $budgetAccount;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Amount', type: 'decimal', scale: 2, nullable: false)]
    private $amount;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set transactionID.
     *
     * @return BudgetTransaction
     */
    public function setTransaction($transaction)
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get transactionID.
     *
     * @return \stdClass
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Set budgetAccount.
     *
     * @param \stdClass $budgetAccount
     *
     * @return BudgetTransaction
     */
    public function setBudgetAccount($budgetAccount)
    {
        $this->budgetAccount = $budgetAccount;

        return $this;
    }

    /**
     * Get budgetAccount.
     *
     * @return \stdClass
     */
    public function getBudgetAccount()
    {
        return $this->budgetAccount;
    }

    /**
     * Set amount.
     *
     * @param string $amount
     *
     * @return BudgetTransaction
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
