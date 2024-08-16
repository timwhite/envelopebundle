<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Account
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Account
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
     * @ORM\Column(name="Name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="account", fetch="EAGER")
     * @ORM\OrderBy({"date" = "DESC"})
     */
    private $transactions;

    /**
     * @ORM\ManyToOne(targetEntity="AccessGroup")
     * @ORM\JoinColumn(name="accessgroup_id", referencedColumnName="id", nullable=FALSE)
     */
    private $access_group;

    /**
     * @ORM\Column(name="budget_transfer", type="boolean", )
     * @var boolean
     */
    private $budgetTransfer = false;


    /**
     * @ORM\OneToMany(targetEntity="ExternalConnector", mappedBy="account")
     */
    private $externalConnectors;

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
     * Set accountName
     *
     * @param string $name
     * @return Account
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get accountName
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return (string)$this->getName();
    }

    /**
     * @return string
     */
    public function getBalance()
    {
        $balance = 0;
        foreach($this->transactions as $transaction)
        {
            $balance = bcadd($balance, $transaction->getAmount(), 2);
        }
        return $balance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->transactions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add transactions
     *
     * @param \App\Entity\Transaction $transactions
     * @return Account
     */
    public function addTransaction(\App\Entity\Transaction $transactions)
    {
        $this->transactions[] = $transactions;

        return $this;
    }

    /**
     * Remove transactions
     *
     * @param \App\Entity\Transaction $transactions
     */
    public function removeTransaction(\App\Entity\Transaction $transactions)
    {
        $this->transactions->removeElement($transactions);
    }

    /**
     * Get transactions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Set access_group
     *
     * @param \App\Entity\AccessGroup $accessGroup
     * @return Account
     */
    public function setAccessGroup(\App\Entity\AccessGroup $accessGroup)
    {
        $this->access_group = $accessGroup;

        return $this;
    }

    /**
     * Get access_group
     *
     * @return \App\Entity\AccessGroup
     */
    public function getAccessGroup()
    {
        return $this->access_group;
    }

    /**
     * Set budgetTransfer
     *
     * @param boolean $budgetTransfer
     * @return Account
     */
    public function setBudgetTransfer($budgetTransfer)
    {
        $this->budgetTransfer = $budgetTransfer;

        return $this;
    }

    /**
     * Get budgetTransfer
     *
     * @return boolean 
     */
    public function getBudgetTransfer()
    {
        return $this->budgetTransfer;
    }
}
