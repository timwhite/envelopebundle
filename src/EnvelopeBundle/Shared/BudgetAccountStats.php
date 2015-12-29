<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 29/12/15
 * Time: 8:45 PM
 */

namespace EnvelopeBundle\Shared;


use Doctrine\DBAL\Types\DecimalType;

class BudgetAccountStats
{
    private $negativeSum;
    private $positiveSum;

    /**
     * @return mixed
     */
    public function getNegativeSum()
    {
        return $this->negativeSum;
    }

    /**
     * @param mixed $negativeSum
     */
    public function setNegativeSum($negativeSum)
    {
        $this->negativeSum = $negativeSum;
    }

    /**
     * @return mixed
     */
    public function getPositiveSum()
    {
        return $this->positiveSum;
    }

    /**
     * @param mixed $positiveSum
     */
    public function setPositiveSum($positiveSum)
    {
        $this->positiveSum = $positiveSum;
    }
    /** @var  DecimalType $averageFortnightlySpend */
    private $averageFortnightlySpend;

    /** @var  \DateTime $firstSpendTransactionDate */
    private $firstSpendTransactionDate;

    /**
     * @return DecimalType
     */
    public function getAverageFortnightlySpend()
    {
        return $this->averageFortnightlySpend;
    }

    /**
     * @param DecimalType $averageFortnightlySpend
     */
    public function setAverageFortnightlySpend($averageFortnightlySpend)
    {
        $this->averageFortnightlySpend = $averageFortnightlySpend;
    }

    /**
     * @return \DateTime
     */
    public function getFirstSpendTransactionDate()
    {
        return $this->firstSpendTransactionDate;
    }

    /**
     * @param \DateTime $firstSpendTransactionDate
     */
    public function setFirstSpendTransactionDate($firstSpendTransactionDate)
    {
        $this->firstSpendTransactionDate = $firstSpendTransactionDate;
    }

    /**
     * @return \DateTime
     */
    public function getLastSpendTransactionDate()
    {
        return $this->lastSpendTransactionDate;
    }

    /**
     * @param \DateTime $lastSpendTransactionDate
     */
    public function setLastSpendTransactionDate($lastSpendTransactionDate)
    {
        $this->lastSpendTransactionDate = $lastSpendTransactionDate;
    }

    /** @var  \DateTime $lastSpendTransactionDate */
    private $lastSpendTransactionDate;

}