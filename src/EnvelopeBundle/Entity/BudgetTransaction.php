<?php

namespace EnvelopeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BudgetTransaction
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class BudgetTransaction
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Transaction", inversedBy="budget_transactions")
     * @ORM\JoinColumn(name="transaction_id", referencedColumnName="id", nullable=false)
     */
    private $transaction;

    /**
     * @ORM\ManyToOne(targetEntity="BudgetAccount", inversedBy="budget_transactions")
     * @ORM\JoinColumn(name="budget_account_id", referencedColumnName="id", nullable=false)
     */
    private $budgetAccount;

    /**
     * @var string
     *
     * @ORM\Column(name="Amount", type="decimal", scale=2, nullable=false)
     */
    private $amount;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set transactionID
     *
     * @param \stdClass $transactionID
     * @return BudgetTransaction
     */
    public function setTransaction($transaction)
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get transactionID
     *
     * @return \stdClass 
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Set budgetAccount
     *
     * @param \stdClass $budgetAccount
     * @return BudgetTransaction
     */
    public function setBudgetAccount($budgetAccount)
    {
        $this->budgetAccount = $budgetAccount;

        return $this;
    }

    /**
     * Get budgetAccount
     *
     * @return \stdClass 
     */
    public function getBudgetAccount()
    {
        return $this->budgetAccount;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return BudgetTransaction
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
