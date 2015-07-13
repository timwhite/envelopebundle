<?php

namespace EnvelopeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transactions
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Transaction
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
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="FullDescription", type="string", length=255)
     */
    private $fullDescription;

    /**
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     */
    private $account;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="Amount", type="decimal", scale=2, nullable=false)
     */
    private $amount;

    /**
     * @ORM\OneToMany(targetEntity="BudgetTransaction", mappedBy="transaction")
     */
    private $budget_transactions;


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
     * Set description
     *
     * @param string $description
     * @return Transaction
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set fullDescription
     *
     * @param string $fullDescription
     * @return Transaction
     */
    public function setFullDescription($fullDescription)
    {
        $this->fullDescription = $fullDescription;

        return $this;
    }

    /**
     * Get fullDescription
     *
     * @return string 
     */
    public function getFullDescription()
    {
        return $this->fullDescription;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Transaction
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return Transaction
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

    /**
     * Set account
     *
     * @param \EnvelopeBundle\Entity\Account $account
     * @return Transaction
     */
    public function setAccount(\EnvelopeBundle\Entity\Account $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return \EnvelopeBundle\Entity\Account 
     */
    public function getAccount()
    {
        return $this->account;
    }

    public function getBudgetSum()
    {
        $balance = 0;
        foreach($this->budget_transactions as $transaction)
        {
            $balance += $transaction->getAmount();
        }
        return $balance;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->budget_transactions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add budget_transactions
     *
     * @param \EnvelopeBundle\Entity\BudgetTransaction $budgetTransactions
     * @return Transaction
     */
    public function addBudgetTransaction(\EnvelopeBundle\Entity\BudgetTransaction $budgetTransactions)
    {
        $this->budget_transactions[] = $budgetTransactions;

        return $this;
    }

    /**
     * Remove budget_transactions
     *
     * @param \EnvelopeBundle\Entity\BudgetTransaction $budgetTransactions
     */
    public function removeBudgetTransaction(\EnvelopeBundle\Entity\BudgetTransaction $budgetTransactions)
    {
        $this->budget_transactions->removeElement($budgetTransactions);
    }

    /**
     * Get budget_transactions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBudgetTransactions()
    {
        return $this->budget_transactions;
    }
}
