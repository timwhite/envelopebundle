<?php

namespace App\Entity\Budget;

use App\Entity\Budget\Template;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\BudgetAccount;

/**
 * TemplateTransaction
 */
#[ORM\Table]
#[ORM\Entity]
class TemplateTransaction
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'amount', type: 'decimal', scale: 2, nullable: false)]
    private $amount;

    #[ORM\JoinColumn(name: 'budget_account_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \App\Entity\BudgetAccount::class, inversedBy: 'template_transactions')]
    private $budgetAccount;

    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Budget\Template::class, inversedBy: 'template_transactions')]
    private $template;


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
     * Set description
     *
     * @param string $description
     * @return TemplateTransaction
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return TemplateTransaction
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }


    /**
     * Set template
     *
     * @param Template $template
     * @return TemplateTransaction
     */
    public function setTemplate(Template $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set budgetAccount
     *
     * @param \App\Entity\BudgetAccount $budgetAccount
     * @return TemplateTransaction
     */
    public function setBudgetAccount(\App\Entity\BudgetAccount $budgetAccount = null)
    {
        $this->budgetAccount = $budgetAccount;

        return $this;
    }

    /**
     * Get budgetAccount
     *
     * @return \App\Entity\BudgetAccount
     */
    public function getBudgetAccount()
    {
        return $this->budgetAccount;
    }

    public function __toString()
    {
        $string = "Empty Account";
        if($this->budgetAccount) $string = $this->budgetAccount->getBudgetName();
        return $string . ": " . $this->description . ": "  . $this->amount;
    }
}
