<?php

namespace App\Entity;

use App\Repository\ImportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Import.
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: ImportRepository::class)]
class Import
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ImportTime', type: 'datetime', nullable: false, unique: true)]
    private $importTime;

    #[ORM\OneToMany(targetEntity: \Transaction::class, mappedBy: 'import')]
    private $transactions;

    public function __construct()
    {
        $this->importTime = new \DateTime();
        $this->transactions = new ArrayCollection();
    }

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
     * Set importTime.
     *
     * @param \DateTime $importTime
     *
     * @return Import
     */
    public function setImportTime($importTime)
    {
        $this->importTime = $importTime;

        return $this;
    }

    /**
     * Get importTime.
     *
     * @return \DateTime
     */
    public function getImportTime()
    {
        return $this->importTime;
    }

    public function __toString()
    {
        return $this->id.' '.$this->importTime->format('Y-m-d H:i:s');
    }

    /**
     * Add transactions.
     *
     * @return Import
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
}
