<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

/**
 * Transactions.
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']],
)]
// #[Get(security: "is_granted('transaction_edit', object)")]
class Transaction
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'Description', type: 'string', length: 255)]
    #[Groups(['read'])]
    private string $description;

    #[ORM\Column(name: 'FullDescription', type: 'string', length: 255)]
    #[Groups(['read'])]
    private string $fullDescription = '';

    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    // #[Groups(['read'])]
    // #[MaxDepth(1)]
    private ?Account $account = null;

    #[ORM\Column(name: 'Date', type: 'date', nullable: false)]
    #[Groups(['read'])]
    private \DateTime $date;

    #[ORM\Column(name: 'Amount', type: 'decimal', scale: 2, nullable: false)]
    #[Groups(['read'])]
    private string $amount = '0';

    /**
     * Due to us getting the Budget Sum in our _toString, we need EAGER loading.
     */
    #[ORM\OneToMany(mappedBy: 'transaction', targetEntity: BudgetTransaction::class, cascade: ['persist'], fetch: 'EAGER')]
    // #[Groups(['read'])]
    private Collection $budget_transactions;

    #[ORM\JoinColumn(name: 'import_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: Import::class)]
    private ?Import $import;

    #[ORM\Column(name: 'extra', type: 'json', nullable: true)]
    #[Groups(['read'])]
    private ?array $extra;

    /**
     * An external immutable ID to match this transaction automatically via an API or similar.
     */
    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    #[Groups(['read'])]
    private string $externalId;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set description.
     *
     * @return Transaction
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set fullDescription.
     *
     * @return Transaction
     */
    public function setFullDescription(string $fullDescription): static
    {
        $this->fullDescription = $fullDescription;

        return $this;
    }

    /**
     * Get fullDescription.
     */
    public function getFullDescription(): string
    {
        return $this->fullDescription;
    }

    /**
     * Set date.
     *
     * @return Transaction
     */
    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Set amount.
     *
     * @return Transaction
     */
    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Set account.
     *
     * @return Transaction
     */
    public function setAccount(?Account $account = null): static
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account.
     */
    public function getAccount(): ?Account
    {
        return $this->account;
    }

    #[Groups(['read'])]
    public function getBudgetSum(): string
    {
        $balance = '0';
        foreach ($this->budget_transactions as $transaction) {
            $balance = bcadd($balance, $transaction->getAmount(), 2);
        }

        return $balance;
    }

    public function getPositiveBudgetSum(): string
    {
        $sum = '0';
        foreach ($this->getBudgetTransactions() as $transaction) {
            if ($transaction->getAmount() > 0) {
                $sum = bcadd($sum, $transaction->getAmount(), 2);
            }
        }

        return $sum;
    }

    public function getNegativeBudgetSum(): int|string
    {
        $sum = 0;
        foreach ($this->getBudgetTransactions() as $transaction) {
            if ($transaction->getAmount() < 0) {
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
     * Constructor.
     */
    public function __construct()
    {
        $this->budget_transactions = new ArrayCollection();
    }

    /**
     * Add budget_transactions.
     *
     * @return Transaction
     */
    public function addBudgetTransaction(BudgetTransaction $budgetTransactions): static
    {
        $this->budget_transactions[] = $budgetTransactions;
        $budgetTransactions->setTransaction($this);

        return $this;
    }

    /**
     * Remove budget_transactions.
     */
    public function removeBudgetTransaction(BudgetTransaction $budgetTransactions): void
    {
        $this->budget_transactions->removeElement($budgetTransactions);
    }

    /**
     * Get budget_transactions.
     */
    public function getBudgetTransactions(): ArrayCollection|Collection
    {
        return $this->budget_transactions;
    }

    public function __toString()
    {
        return $this->getDescription().': '.$this->getBudgetSum().'/'.$this->getAmount();
    }

    /**
     * Set import.
     *
     * @return Transaction
     */
    public function setImport(?Import $import = null): static
    {
        $this->import = $import;
        $this->import->addTransaction($this);

        return $this;
    }

    /**
     * Get import.
     */
    public function getImport(): ?Import
    {
        return $this->import;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): void
    {
        $this->extra = $extra;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): Transaction
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getCategory(): string
    {
        /** @var BudgetTransaction[] $budgetTransactions */
        $budgetTransactions = $this->getBudgetTransactions();
        if (1 !== sizeof($budgetTransactions)) {
            return '';
        }

        return $budgetTransactions[0]->getBudgetAccount()->getBudgetName();
    }
}
