<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * BudgetGroup
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class BudgetGroup
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="BudgetAccount", mappedBy="budget_group")
     */
    private $budget_accounts;

    /**
     * @ORM\ManyToOne(targetEntity="AccessGroup")
     * @ORM\JoinColumn(name="accessgroup_id", referencedColumnName="id", nullable=FALSE)
     */
    private $access_group;



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
     * Set name
     *
     * @param string $name
     * @return BudgetGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->getName() ?? '';
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->budget_accounts = new ArrayCollection();
    }

    /**
     * Add budget_accounts
     *
     * @param BudgetAccount $budgetAccounts
     *
     * @return BudgetGroup
     */
    public function addBudgetAccount(BudgetAccount $budgetAccounts)
    {
        $this->budget_accounts[] = $budgetAccounts;

        return $this;
    }

    /**
     * Remove budget_accounts
     *
     * @param BudgetAccount $budgetAccounts
     */
    public function removeBudgetAccount(BudgetAccount $budgetAccounts)
    {
        $this->budget_accounts->removeElement($budgetAccounts);
    }

    /**
     * Get budget_accounts
     *
     * @return Collection
     */
    public function getBudgetAccounts()
    {
        return $this->budget_accounts;
    }

    public function getBudgetSum($startdate = null, $enddate = null)
    {
        $balance = 0;
        foreach($this->getBudgetAccounts() as $account)
        {
            $balance = bcadd($balance, $account->getBalance($startdate, $enddate), 2);
        }
        return $balance;
    }

    public function getPositiveBudgetSum($startdate = null, $enddate = null)
    {
        $balance = 0;
        foreach($this->getBudgetAccounts() as $account)
        {
            $balance = bcadd($balance, $account->getPositiveBalance($startdate, $enddate), 2);
        }
        return $balance;
    }

    public function getNegativeBudgetSum($startdate = null, $enddate = null)
    {
        $balance = 0;
        foreach($this->getBudgetAccounts() as $account)
        {
            $balance = bcadd($balance, $account->getNegativeBalance($startdate, $enddate), 2);
        }
        return $balance;
    }

    /**
     * Set access_group
     *
     * @param AccessGroup $accessGroup
     *
     * @return BudgetGroup
     */
    public function setAccessGroup(AccessGroup $accessGroup)
    {
        $this->access_group = $accessGroup;

        return $this;
    }

    /**
     * Get access_group
     *
     * @return AccessGroup
     */
    public function getAccessGroup()
    {
        return $this->access_group;
    }
}
