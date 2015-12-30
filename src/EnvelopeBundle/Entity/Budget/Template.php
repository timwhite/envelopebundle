<?php

namespace EnvelopeBundle\Entity\Budget;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use EnvelopeBundle\Entity\Budget\TemplateTransaction;

/**
 * Template
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Template
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
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="last_applied_date", type="date", nullable=True)
     */
    private $last_applied_date;

    /**
     * @ORM\OneToMany(targetEntity="TemplateTransaction", mappedBy="template", cascade={"persist","remove"})
     */
    private $template_transactions;

    public function getBalance()
    {
        $balance = 0;
        foreach($this->getTemplateTransactions() as $transaction)
        {
            $balance = bcadd($balance, $transaction->getAmount(), 2);
        }
        return $balance;
    }

    public function getPositiveSum()
    {
        $sum = 0;
        foreach($this->getTemplateTransactions() as $transaction)
        {
            if($transaction->getAmount() > 0) {
                $sum = bcadd($sum, $transaction->getAmount(), 2);
            }
        }
        return $sum;
    }

    public function getNegativeSum()
    {
        $sum = 0;
        foreach($this->getTemplateTransactions() as $transaction)
        {
            if($transaction->getAmount() < 0) {
                $sum = bcadd($sum, $transaction->getAmount(), 2);
            }
        }
        return $sum;
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
     * Set name
     *
     * @param string $name
     * @return Budget:Template
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

    /**
     * Set description
     *
     * @param string $description
     * @return Budget:Template
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
     * Constructor
     */
    public function __construct()
    {
        $this->template_transactions = new ArrayCollection();
    }


    public function __toString()
    {
        $lastDate = "";
        if($this->getLastAppliedDate())
        {
            $lastDate =  " - " . $this->getLastAppliedDate()->format('Y-m-d');
        }
        return $this->getName() .": " .  $this->getDescription() ." (" . $this->getBalance() . ")$lastDate";
    }

    /**
     * Add template_transactions
     *
     * @param \EnvelopeBundle\Entity\Budget\TemplateTransaction $templateTransactions
     * @return Template
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

    /**
     * Set last_applied_date
     *
     * @param \DateTime $lastAppliedDate
     * @return Template
     */
    public function setLastAppliedDate($lastAppliedDate)
    {
        $this->last_applied_date = $lastAppliedDate;

        return $this;
    }

    /**
     * Get last_applied_date
     *
     * @return \DateTime 
     */
    public function getLastAppliedDate()
    {
        return $this->last_applied_date;
    }

    public function __clone() {
        if ($this->id) {
            $this->id = null;
            $this->last_applied_date = null;
            $this->setDescription("Cloned - ". $this->getDescription());
            $this->setName("Cloned - ". $this->getName());

            // cloning the relation M which is a OneToMany
            $cloned_transactions = new ArrayCollection();
            foreach ($this->getTemplateTransactions() as $templateTransaction) {
                $clonedTransaction = clone $templateTransaction;
                $clonedTransaction->setTemplate($this);
                $cloned_transactions->add($clonedTransaction);
            }
            $this->template_transactions = $cloned_transactions;
        }
    }
}
