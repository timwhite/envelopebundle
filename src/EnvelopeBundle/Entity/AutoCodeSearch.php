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
}
