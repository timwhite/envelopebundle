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
     * @ORM\Column(name="BudgetName", type="string", length=255, nullable=false, unique=true)
     */
    private $budget_name;

    /**
     * @ORM\OneToMany(targetEntity="BudgetTransaction", mappedBy="budgetAccount")
     */
    private $budget_transactions;

    /**
     * @ORM\OneToMany(targetEntity="EnvelopeBundle\Entity\Budget\TemplateTransaction", mappedBy="budgetAccount")
     */
    private $template_transactions;

    /**
     * @ORM\ManyToOne(targetEntity="BudgetGroup", inversedBy="group_accounts")
     * @ORM\JoinColumn(name="budget_group", referencedColumnName="id", nullable=false)
     */
    private $budget_group;



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

    public function __toString()
    {
        return $this->getBudgetName() . ': ' . $this->getBalance();
    }


    /**
     * Set budget_group
     *
     * @param \EnvelopeBundle\Entity\BudgetGroup $budgetGroup
     * @return BudgetAccount
     */
    public function setBudgetGroup(\EnvelopeBundle\Entity\BudgetGroup $budgetGroup)
    {
        $this->budget_group = $budgetGroup;

        return $this;
    }

    /**
     * Get budget_group
     *
     * @return \EnvelopeBundle\Entity\BudgetGroup 
     */
    public function getBudgetGroup()
    {
        return $this->budget_group;
    }



    /**
     * Add template_transactions
     *
     * @param \EnvelopeBundle\Entity\Budget\TemplateTransaction $templateTransactions
     * @return BudgetAccount
     */
    public function addTemplateTransaction(\EnvelopeBundle\Entity\Budget\TemplateTransaction $templateTransactions)
    {
        $this->template_transactions[] = $templateTransactions;

        return $this;
    }

    /**
     * Remove template_transactions
     *
     * @param \EnvelopeBundle\Entity\Budget\TemplateTransaction $templateTransactions
     */
    public function removeTemplateTransaction(\EnvelopeBundle\Entity\Budget\TemplateTransaction $templateTransactions)
    {
        $this->template_transactions->removeElement($templateTransactions);
    }

    /**
     * Get template_transactions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTemplateTransactions()
    {
        return $this->template_transactions;
    }
}
