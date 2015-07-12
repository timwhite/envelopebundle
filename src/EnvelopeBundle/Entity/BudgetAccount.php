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
}
