<?php

namespace EnvelopeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AutoCodeSearch
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class AutoCodeSearch
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
     * @ORM\ManyToOne(targetEntity="BudgetAccount")
     * @ORM\JoinColumn(name="budget_account_id", referencedColumnName="id", nullable=false)
     */
    private $budgetAccount;

    /**
     * @ORM\Column(name="search", type="string", length=255, nullable=false)
     */
    private $search;

    /**
     * @ORM\Column(name="description_rename", type="string", length=255, nullable=true)
     *
     * The description we rename autocoded transactions
     */
    private $rename = null;

    /**
     * @ORM\Column(name="amount", type="decimal", scale=2, nullable=true)
     *
     * An amount for searching exact amounts
     */
    private $amount = null;

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
     * Set search
     *
     * @param string $search
     * @return AutoCodeSearch
     */
    public function setSearch($search)
    {
        $this->search = $search;

        return $this;
    }

    /**
     * Get search
     *
     * @return string 
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * Set budgetAccount
     *
     * @param \EnvelopeBundle\Entity\BudgetAccount $budgetAccount
     * @return AutoCodeSearch
     */
    public function setBudgetAccount(\EnvelopeBundle\Entity\BudgetAccount $budgetAccount)
    {
        $this->budgetAccount = $budgetAccount;

        return $this;
    }

    /**
     * Get budgetAccount
     *
     * @return \EnvelopeBundle\Entity\BudgetAccount 
     */
    public function getBudgetAccount()
    {
        return $this->budgetAccount;
    }

    /**
     * Set rename
     *
     * @param string $rename
     * @return AutoCodeSearch
     */
    public function setRename($rename)
    {
        $this->rename = $rename;

        return $this;
    }

    /**
     * Get rename
     *
     * @return string 
     */
    public function getRename()
    {
        return $this->rename;
    }

    /**
     * @return null|float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param $amount null|float
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }
}
