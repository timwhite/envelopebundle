<?php

namespace EnvelopeBundle\Entity;

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
        return $this->getName();
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->budget_accounts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add budget_accounts
     *
     * @param \EnvelopeBundle\Entity\BudgetAccount $budgetAccounts
     * @return BudgetGroup
     */
    public function addBudgetAccount(\EnvelopeBundle\Entity\BudgetAccount $budgetAccounts)
    {
        $this->budget_accounts[] = $budgetAccounts;

        return $this;
    }

    /**
     * Remove budget_accounts
     *
     * @param \EnvelopeBundle\Entity\BudgetAccount $budgetAccounts
     */
    public function removeBudgetAccount(\EnvelopeBundle\Entity\BudgetAccount $budgetAccounts)
    {
        $this->budget_accounts->removeElement($budgetAccounts);
    }

    /**
     * Get budget_accounts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBudgetAccounts()
    {
        return $this->budget_accounts;
    }
}
