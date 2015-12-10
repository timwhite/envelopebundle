<?php

namespace EnvelopeBundle\Entity;

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
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="account")
     * @ORM\OrderBy({"date" = "DESC"})
     */
    private $transactions;

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
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getBalance()
    {
        $balance = 0;
        foreach($this->transactions as $transaction)
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
        $this->transactions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add transactions
     *
     * @param \EnvelopeBundle\Entity\Transaction $transactions
     * @return Account
     */
    public function addTransaction(\EnvelopeBundle\Entity\Transaction $transactions)
    {
        $this->transactions[] = $transactions;

        return $this;
    }

    /**
     * Remove transactions
     *
     * @param \EnvelopeBundle\Entity\Transaction $transactions
     */
    public function removeTransaction(\EnvelopeBundle\Entity\Transaction $transactions)
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
     * @param \EnvelopeBundle\Entity\AccessGroup $accessGroup
     * @return Account
     */
    public function setAccessGroup(\EnvelopeBundle\Entity\AccessGroup $accessGroup)
    {
        $this->access_group = $accessGroup;

        return $this;
    }

    /**
     * Get access_group
     *
     * @return \EnvelopeBundle\Entity\AccessGroup 
     */
    public function getAccessGroup()
    {
        return $this->access_group;
    }
}
