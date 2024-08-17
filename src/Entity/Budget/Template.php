<?php

namespace App\Entity\Budget;

use App\Entity\AccessGroup;
use App\Repository\BudgetTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Template.
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: BudgetTemplateRepository::class)]
class Template
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    private string $description;

    #[ORM\Column(name: 'last_applied_date', type: 'date', nullable: true)]
    private ?\DateTime $last_applied_date;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: TemplateTransaction::class, cascade: ['persist', 'remove'])]
    private Collection $template_transactions;

    #[ORM\Column(name: 'Archived', type: 'boolean', nullable: false)]
    private bool $archived = false;

    #[ORM\JoinColumn(name: 'accessgroup_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: AccessGroup::class)]
    private AccessGroup $access_group;

    public function getBalance(): string
    {
        $balance = '0';
        foreach ($this->getTemplateTransactions() as $transaction) {
            $balance = bcadd($balance, $transaction->getAmount(), 2);
        }

        return $balance;
    }

    public function getPositiveSum(): string
    {
        $sum = '0';
        foreach ($this->getTemplateTransactions() as $transaction) {
            if ($transaction->getAmount() > 0) {
                $sum = bcadd($sum, $transaction->getAmount(), 2);
            }
        }

        return $sum;
    }

    public function getNegativeSum(): string
    {
        $sum = '0';
        foreach ($this->getTemplateTransactions() as $transaction) {
            if ($transaction->getAmount() < 0) {
                $sum = bcadd($sum, $transaction->getAmount(), 2);
            }
        }

        return $sum;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set name.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set description.
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
     * Constructor.
     */
    public function __construct()
    {
        $this->template_transactions = new ArrayCollection();
    }

    public function __toString()
    {
        $lastDate = '';
        if ($this->getLastAppliedDate()) {
            $lastDate = ' - '.$this->getLastAppliedDate()->format('Y-m-d');
        }

        return $this->getName().': '.$this->getDescription().' ('.$this->getBalance().")$lastDate";
    }

    /**
     * Add template_transactions.
     *
     * @return Template
     */
    public function addTemplateTransaction(TemplateTransaction $templateTransactions): static
    {
        $this->template_transactions[] = $templateTransactions;

        return $this;
    }

    /**
     * Remove template_transactions.
     */
    public function removeTemplateTransaction(TemplateTransaction $templateTransactions): void
    {
        $this->template_transactions->removeElement($templateTransactions);
    }

    /**
     * Get template_transactions.
     *
     * @return Collection
     */
    public function getTemplateTransactions(): ArrayCollection|Collection
    {
        return $this->template_transactions;
    }

    /**
     * Set last_applied_date.
     *
     * @param \DateTime $lastAppliedDate
     *
     * @return Template
     */
    public function setLastAppliedDate($lastAppliedDate): static
    {
        $this->last_applied_date = $lastAppliedDate;

        return $this;
    }

    /**
     * Get last_applied_date.
     */
    public function getLastAppliedDate(): ?\DateTime
    {
        return $this->last_applied_date;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->last_applied_date = null;
            $this->setDescription('Cloned - '.$this->getDescription());
            $this->setName('Cloned - '.$this->getName());

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

    /**
     * Set archived.
     */
    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     */
    public function getArchived(): bool
    {
        return $this->archived;
    }

    /**
     * Set access_group.
     *
     * @return Template
     */
    public function setAccessGroup(AccessGroup $accessGroup): static
    {
        $this->access_group = $accessGroup;

        return $this;
    }

    /**
     * Get access_group.
     */
    public function getAccessGroup(): AccessGroup
    {
        return $this->access_group;
    }

    public function isArchived(): ?bool
    {
        return $this->archived;
    }
}
