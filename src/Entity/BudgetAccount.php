<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\BudgetTransaction;
use App\Shared\BudgetAccountStats;

/**
 * Account
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
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
     * @TODO unique isn't enforced, and it should be unique with the budgetGroup
     * @ORM\Column(name="BudgetName", type="string", length=255, nullable=false)
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
     * @ORM\ManyToOne(targetEntity="BudgetGroup", inversedBy="budget_accounts")
     * @ORM\JoinColumn(name="budget_group", referencedColumnName="id", nullable=false)
     */
    private $budget_group;

    /** @var  BudgetAccountStats $budgetStats */
    private $budgetStats;

    /**
     * @return BudgetAccountStats
     */
    public function getBudgetStats()
    {
        return $this->budgetStats;
    }

    /**
     * @param BudgetAccountStats $budgetStats
     */
    public function setBudgetStats($budgetStats)
    {
        $this->budgetStats = $budgetStats;
    }

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
        $this->budgetStats = new BudgetAccountStats($this->id);
    }

    /** @ORM\PostLoad */
    public function postLoad()
    {
        if(!$this->budgetStats)
        {
            $this->budgetStats = new BudgetAccountStats($this->id);
        }
    }

    /**
     * Add budget_transactions
     *
     * @param \App\Entity\BudgetTransaction $budgetTransactions
     *
     * @return BudgetAccount
     */
    public function addBudgetTransaction(\App\Entity\BudgetTransaction $budgetTransactions)
    {
        $this->budget_transactions[] = $budgetTransactions;

        return $this;
    }

    /**
     * Remove budget_transactions
     *
     * @param \App\Entity\BudgetTransaction $budgetTransactions
     */
    public function removeBudgetTransaction(\App\Entity\BudgetTransaction $budgetTransactions)
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

    public function getBalance($startdate = null, $enddate = null)
    {
        $balance = 0;
        /** @var BudgetTransaction $transaction */
        foreach($this->budget_transactions as $transaction)
        {
            if(
                ($transaction->getTransaction()->getDate() >= $startdate && $transaction->getTransaction()->getDate() <= $enddate) ||
                $startdate == null || $enddate == null
            ) {
                $balance = bcadd($balance, $transaction->getAmount(), 2);
            }

        }
        return $balance;
    }

    public function getPositiveBalance($startdate = null, $enddate = null)
    {
        $balance = 0;
        /** @var BudgetTransaction $transaction */
        foreach($this->budget_transactions as $transaction)
        {
            if(
                ($transaction->getTransaction()->getDate() >= $startdate && $transaction->getTransaction()->getDate() <= $enddate) ||
                $startdate == null || $enddate == null
            ) {
                if ($transaction->getAmount() > 0) {
                    $balance = bcadd($balance, $transaction->getAmount(), 2);
                }
            }

        }
        return $balance;
    }

    public function getNegativeBalance($startdate = null, $enddate = null)
    {
        $balance = 0;
        /** @var BudgetTransaction $transaction */
        foreach($this->budget_transactions as $transaction)
        {
            if(
                ($transaction->getTransaction()->getDate() >= $startdate && $transaction->getTransaction()->getDate() <= $enddate) ||
                $startdate == null || $enddate == null
            ) {
                if ($transaction->getAmount() < 0) {
                    $balance = bcadd($balance, $transaction->getAmount(), 2);
                }
            }

        }
        return $balance;
    }


    public function __toString()
    {
        // NB: This should probably be handled by the view, instead of hard coding a locale here
        $fmt = numfmt_create( 'en_AU', \NumberFormatter::CURRENCY );
            ;

        return $this->getBudgetName() . ": ". numfmt_format_currency($fmt, $this->getBalance(), 'AUD')."";
    }


    /**
     * Set budget_group
     *
     * @param \App\Entity\BudgetGroup $budgetGroup
     *
     * @return BudgetAccount
     */
    public function setBudgetGroup(\App\Entity\BudgetGroup $budgetGroup)
    {
        $this->budget_group = $budgetGroup;

        return $this;
    }

    /**
     * Get budget_group
     *
     * @return \App\Entity\BudgetGroup
     */
    public function getBudgetGroup()
    {
        return $this->budget_group;
    }



    /**
     * Add template_transactions
     *
     * @param \App\Entity\Budget\TemplateTransaction $templateTransactions
     *
     * @return BudgetAccount
     */
    public function addTemplateTransaction(Budget\TemplateTransaction $templateTransactions)
    {
        $this->template_transactions[] = $templateTransactions;

        return $this;
    }

    /**
     * Remove template_transactions
     *
     * @param \App\Entity\Budget\TemplateTransaction $templateTransactions
     */
    public function removeTemplateTransaction(Budget\TemplateTransaction $templateTransactions)
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

    public function getTemplateTransactionsDescriptionsTooltip()
    {
        // NB: This should probably be handled by the view, instead of hard coding a locale here
        $fmt = numfmt_create( 'en_AU', \NumberFormatter::CURRENCY );
        $desc = [];
        /** @var \App\Entity\Budget\TemplateTransaction $trans */
        foreach($this->getTemplateTransactions() as $trans)
        {
            if(!$trans->getTemplate()->getArchived()) {
                $desc[] = $trans->getDescription() . " (" . numfmt_format_currency(
                        $fmt,
                        $trans->getAmount(),
                        'AUD'
                    ) . ")";
            }
        }
        return implode("<br/>", $desc);
    }
}
