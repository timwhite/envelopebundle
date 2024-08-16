<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Transactions
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string', length: 255)]
    private string $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'FullDescription', type: 'string', length: 255)]
    private string $fullDescription;

    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'Date', type: 'date', nullable: false)]
    private \DateTime $date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Amount', type: 'decimal', scale: 2, nullable: false)]
    private string $amount;

    /**
     * Due to us getting the Budget Sum in our _toString, we need EAGER loading
     */
    #[ORM\OneToMany(mappedBy: 'transaction', targetEntity: BudgetTransaction::class, cascade: ['persist'], fetch: 'EAGER')]
    private Collection $budget_transactions;

    #[ORM\JoinColumn(name: 'import_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: Import::class)]
    private ?Import $import;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'extra', type: 'json', nullable: true)]
    private ?array $extra;

    /**
     * An external immutable ID to match this transaction automatically via an API or similar
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private string $externalId;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Transaction
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set fullDescription
     *
     * @param string $fullDescription
     * @return Transaction
     */
    public function setFullDescription(string $fullDescription): static
    {
        $this->fullDescription = $fullDescription;

        return $this;
    }

    /**
     * Get fullDescription
     *
     * @return string 
     */
    public function getFullDescription(): string
    {
        return $this->fullDescription;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Transaction
     */
    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return Transaction
     */
    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Set account
     *
     * @param Account $account
     * @return Transaction
     */
    public function setAccount(Account $account = null): static
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @return string
     */
    public function getBudgetSum(): int|string
    {
        $balance = 0;
        foreach($this->budget_transactions as $transaction)
        {
            $balance = bcadd($balance, $transaction->getAmount(), 2);
        }
        return $balance;
    }

    public function getPositiveBudgetSum(): int|string
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

    public function getNegativeBudgetSum(): int|string
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

    public function getUnassignedSum(): string
    {
        return bcsub($this->getAmount(), $this->getBudgetSum(), 2);
    }

    public function getUnassignedSumFormatted(): string
    {
        setlocale(LC_MONETARY, 'en_AU');
        return number_format(round($this->getUnassignedSum(), 2, PHP_ROUND_HALF_ODD), 2, '.', '');
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->budget_transactions = new ArrayCollection();
    }

    /**
     * Add budget_transactions
     *
     * @param BudgetTransaction $budgetTransactions
     * @return Transaction
     */
    public function addBudgetTransaction(BudgetTransaction $budgetTransactions): static
    {
        $this->budget_transactions[] = $budgetTransactions;
        $budgetTransactions->setTransaction($this);

        return $this;
    }

    /**
     * Remove budget_transactions
     *
     * @param BudgetTransaction $budgetTransactions
     */
    public function removeBudgetTransaction(BudgetTransaction $budgetTransactions): void
    {
        $this->budget_transactions->removeElement($budgetTransactions);
    }

    /**
     * Get budget_transactions
     *
     * @return ArrayCollection|Collection
     */
    public function getBudgetTransactions(): ArrayCollection|Collection
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
     * @param Import $import
     * @return Transaction
     */
    public function setImport(Import $import = null): static
    {
        $this->import = $import;
        $this->import->addTransaction($this);

        return $this;
    }

    /**
     * Get import
     *
     * @return Import
     */
    public function getImport(): ?Import
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
    public function setExtra(?array $extra): void
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
