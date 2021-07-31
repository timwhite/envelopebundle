<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Import
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="App\Repository\ImportRepository")
 */
class Import
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
     * @var DateTime
     *
     * @ORM\Column(name="ImportTime", type="datetime", nullable=false, unique=true)
     */
    private $importTime;


    /**
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="import")
     */
    private $transactions;
    private $transactionCount;

    public function __construct()
    {
        $this->importTime = new DateTime();
        $this->transactions = new ArrayCollection();
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
     * Set importTime
     *
     * @param DateTime $importTime
     * @return Import
     */
    public function setImportTime($importTime)
    {
        $this->importTime = $importTime;

        return $this;
    }

    /**
     * Get importTime
     *
     * @return DateTime
     */
    public function getImportTime()
    {
        return $this->importTime;
    }

    public function __toString()
    {
        return $this->id . " " . $this->importTime->format('Y-m-d H:i:s');
    }

    /**
     * Add transactions
     *
     * @param Transaction $transactions
     *
     * @return Import
     */
    public function addTransaction(Transaction $transactions)
    {
        $this->transactions[] = $transactions;

        return $this;
    }

    /**
     * Remove transactions
     *
     * @param Transaction $transactions
     */
    public function removeTransaction(Transaction $transactions)
    {
        $this->transactions->removeElement($transactions);
    }

    /**
     * Get transactions
     *
     * @return Collection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    public function setTransactionCountHydrate(int $count)
    {
        $this->transactionCount = $count;
    }

    public function getTransactionsCountSpecial()
    {
        return $this->transactionCount;
    }

    // @TODO store Account refrence in Import instead of trying to get it from transactions
}
