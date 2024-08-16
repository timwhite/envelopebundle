<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Import
 *
 * @ORM\Table()
 * @ORM\Entity
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
     * @var \DateTime
     *
     * @ORM\Column(name="ImportTime", type="datetime", nullable=false, unique=true)
     */
    private $importTime;


    /**
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="import")
     */
    private $transactions;

    public function __construct()
    {
        $this->importTime = new \DateTime();
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
     * @param \DateTime $importTime
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
     * @return \DateTime 
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
     * @param \App\Entity\Transaction $transactions
     * @return Import
     */
    public function addTransaction(\App\Entity\Transaction $transactions)
    {
        $this->transactions[] = $transactions;

        return $this;
    }

    /**
     * Remove transactions
     *
     * @param \App\Entity\Transaction $transactions
     */
    public function removeTransaction(\App\Entity\Transaction $transactions)
    {
        $this->transactions->removeElement($transactions);
    }

    /**
     * Get transactions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTransactions()
    {
        return $this->transactions;
    }
}
