<?php

namespace EnvelopeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Account
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class BudgetAccount
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
     * @ORM\Column(name="BudgetName", type="string", length=255, nullable=false)
     */
    private $budget_name;

    /**
     * @ORM\OneToMany(targetEntity="BudgetTransaction", mappedBy="budgetAccount")
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
     * Set budgetName
     *
     * @param string $budgetName
     * @return BudgetAccount
     */
    public function setBudgetName($budgetName)
    {
        $this->budget_name = $budgetName;

        return $this;
    }

    /**
     * Get budgetName
     *
     * @return string 
     */
    public function getBudgetName()
    {
        return $this->budget_name;
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
     * @return BudgetAccount
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

    public function getBalance()
    {
        $balance = 0;
        foreach($this->budget_transactions as $transaction)
        {
            $balance += $transaction->getAmount();
        }
        return $balance;
    }
}
