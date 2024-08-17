<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Account.
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string', length: 255, nullable: false)]
    private $name;

    #[ORM\OneToMany(targetEntity: \Transaction::class, mappedBy: 'account', fetch: 'EAGER')]
    #[ORM\OrderBy(['date' => 'DESC'])]
    private $transactions;

    #[ORM\JoinColumn(name: 'accessgroup_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \AccessGroup::class)]
    private $access_group;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'budget_transfer', type: 'boolean')]
    private $budgetTransfer = false;

    #[ORM\OneToMany(targetEntity: \ExternalConnector::class, mappedBy: 'account')]
    private $externalConnectors;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set accountName.
     *
     * @param string $name
     *
     * @return Account
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get accountName.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * @return string
     */
    public function getBalance()
    {
        $balance = 0;
        foreach ($this->transactions as $transaction) {
            $balance = bcadd($balance, $transaction->getAmount(), 2);
        }

        return $balance;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->externalConnectors = new ArrayCollection();
    }

    /**
     * Add transactions.
     *
     * @return Account
     */
    public function addTransaction(Transaction $transactions)
    {
        $this->transactions[] = $transactions;

        return $this;
    }

    /**
     * Remove transactions.
     */
    public function removeTransaction(Transaction $transactions)
    {
        $this->transactions->removeElement($transactions);
    }

    /**
     * Get transactions.
     *
     * @return Collection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Set access_group.
     *
     * @return Account
     */
    public function setAccessGroup(AccessGroup $accessGroup)
    {
        $this->access_group = $accessGroup;

        return $this;
    }

    /**
     * Get access_group.
     *
     * @return AccessGroup
     */
    public function getAccessGroup()
    {
        return $this->access_group;
    }

    /**
     * Set budgetTransfer.
     *
     * @param bool $budgetTransfer
     *
     * @return Account
     */
    public function setBudgetTransfer($budgetTransfer)
    {
        $this->budgetTransfer = $budgetTransfer;

        return $this;
    }

    /**
     * Get budgetTransfer.
     *
     * @return bool
     */
    public function getBudgetTransfer()
    {
        return $this->budgetTransfer;
    }

    public function isBudgetTransfer(): ?bool
    {
        return $this->budgetTransfer;
    }

    /**
     * @return Collection<int, ExternalConnector>
     */
    public function getExternalConnectors(): Collection
    {
        return $this->externalConnectors;
    }

    public function addExternalConnector(ExternalConnector $externalConnector): static
    {
        if (!$this->externalConnectors->contains($externalConnector)) {
            $this->externalConnectors->add($externalConnector);
            $externalConnector->setAccount($this);
        }

        return $this;
    }

    public function removeExternalConnector(ExternalConnector $externalConnector): static
    {
        if ($this->externalConnectors->removeElement($externalConnector)) {
            // set the owning side to null (unless already changed)
            if ($externalConnector->getAccount() === $this) {
                $externalConnector->setAccount(null);
            }
        }

        return $this;
    }
}
