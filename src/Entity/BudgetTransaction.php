<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BudgetTransaction.
 */
#[ORM\Table]
#[ORM\Entity]
class BudgetTransaction
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id;

    /**
     * We need EAGER loading due to us displaying the balance on our _toString method, which requires all
     * the transactions.
     */
    #[ORM\JoinColumn(name: 'transaction_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Transaction::class, fetch: 'EAGER', inversedBy: 'budget_transactions')]
    private Transaction $transaction;

    #[ORM\JoinColumn(name: 'budget_account_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: BudgetAccount::class, inversedBy: 'budget_transactions')]
    private BudgetAccount $budgetAccount;

    #[ORM\Column(name: 'Amount', type: 'decimal', scale: 2, nullable: false)]
    private string $amount;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set transactionID.
     */
    public function setTransaction(Transaction $transaction): static
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get transactionID.
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * Set budgetAccount.
     */
    public function setBudgetAccount(BudgetAccount $budgetAccount): static
    {
        $this->budgetAccount = $budgetAccount;

        return $this;
    }

    /**
     * Get budgetAccount.
     */
    public function getBudgetAccount(): BudgetAccount
    {
        return $this->budgetAccount;
    }

    /**
     * Set amount.
     *
     * @return BudgetTransaction
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
}
