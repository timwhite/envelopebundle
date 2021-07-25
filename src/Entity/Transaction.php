<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transactions
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Transaction
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
     * @ORM\Column(name="Description", type="string", length=255)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="FullDescription", type="string", length=255)
     */
    private $fullDescription;

    /**
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="transactions")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     */
    private $account;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="Amount", type="decimal", scale=2, nullable=false)
     */
    private $amount;

    /**
     * Due to us getting the Budget Sum in our _toString, we need EAGER loading
     * @ORM\OneToMany(targetEntity="BudgetTransaction", mappedBy="transaction", cascade="persist", fetch="EAGER")
     */
    private $budget_transactions;

    /**
     * @ORM\ManyToOne(targetEntity="Import", inversedBy="transactions")
     * @ORM\JoinColumn(name="import_id", referencedColumnName="id", nullable=true)
     */
    private $import;

    /**
     * @var array|null
     *
     * @ORM\Column(name="extra", type="json", nullable=true)
     */
    private $extra;

    /**
     * An external immutable ID to match this transaction automatically via an API or similar
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, length=512)
     */
    private $externalId;

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
     * @return Transaction
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
     * Set fullDescription
     *
     * @param string $fullDescription
     * @return Transaction
     */
    public function setFullDescription($fullDescription)
    {
        $this->fullDescription = $fullDescription;

        return $this;
    }

    /**
     * Get fullDescription
     *
     * @return string 
     */
    public function getFullDescription()
    {
        return $this->fullDescription;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Transaction
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return Transaction
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
     * Set account
     *
     * @param \App\Entity\Account $account
     *
     * @return Transaction
     */
    public function setAccount(\App\Entity\Account $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return \App\Entity\Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return string
     */
    public function getBudgetSum()
    {
        $balance = 0;
        foreach($this->budget_transactions as $transaction)
        {
            $balance = bcadd($balance, $transaction->getAmount(), 2);
        }
        return $balance;
    }

    public function getPositiveBudgetSum()
    {
        $sum = 0;
        foreach($this->getBudgetTransactions() as $transaction)
        {
            if($transaction->getAmount() > 0) {
                $sum = bcadd($sum, $transaction->getAmount(), 2);
            }
        }
        return $sum;
    }

    public function getNegativeBudgetSum()
    {
        $sum = 0;
        foreach($this->getBudgetTransactions() as $transaction)
        {
            if($transaction->getAmount() < 0) {
                $sum = bcadd($sum, $transaction->getAmount(), 2);
            }
        }
        return $sum;
    }

    public function getUnassignedSum()
    {
        return bcsub($this->getAmount(), $this->getBudgetSum(), 2);
    }

    public function getUnassignedSumFormatted()
    {
        setlocale(LC_MONETARY, 'en_AU');
        return money_format('%i', $this->getUnassignedSum());
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->budget_transactions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add budget_transactions
     *
     * @param \App\Entity\BudgetTransaction $budgetTransactions
     *
     * @return Transaction
     */
    public function addBudgetTransaction(\App\Entity\BudgetTransaction $budgetTransactions)
    {
        $this->budget_transactions[] = $budgetTransactions;
        $budgetTransactions->setTransaction($this);

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

    public function __toString()
    {
        return $this->getDescription() . ': ' .$this->getBudgetSum() . '/' . $this->getAmount();
    }

    /**
     * Set import
     *
     * @param \App\Entity\Import $import
     *
     * @return Transaction
     */
    public function setImport(\App\Entity\Import $import = null)
    {
        $this->import = $import;
        $this->import->addTransaction($this);

        return $this;
    }

    /**
     * Get import
     *
     * @return \App\Entity\Import
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * @return array|null
     */
    public function getExtra(): ?array
    {
        return $this->extra;
    }

    /**
     * @param array|null $extra
     */
    public function setExtra(?array $extra)
    {
        $this->extra = $extra;
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     *
     * @return Transaction
     */
    public function setExternalId(string $externalId): Transaction
    {
        $this->externalId = $externalId;
        return $this;
    }


}
