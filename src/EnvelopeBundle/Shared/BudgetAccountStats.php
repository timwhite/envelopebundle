<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 29/12/15
 * Time: 8:45 PM
 */

namespace EnvelopeBundle\Shared;


use Doctrine\DBAL\Types\DecimalType;
use Symfony\Component\Validator\Constraints\DateTime;

class BudgetAccountStats
{
    private $budgetID;
    private $negativeSum;
    private $positiveSum;
    private $averageFortnightlySpend;
    private $averageFortnightlyIncome;
    private $averageFortnightlyPositive;
    /** @var  \DateTime $firstIncomeTransactionDate */
    private $firstIncomeTransactionDate;
    /** @var  \DateTime $lastIncomeTransactionDate */
    private $lastIncomeTransactionDate;
    /** @var  \DateTime $firstSpendTransactionDate */
    private $firstSpendTransactionDate;
    /** @var  \DateTime $lastSpendTransactionDate */
    private $lastSpendTransactionDate;

    /** @var  \DateTime $firstTransactionDate */
    private $firstTransactionDate;
    /** @var  \DateTime $lastTransactionDate */
    private $lastTransactionDate;

    // Array[Year, Week, Sum, RunningTotal]
    private $runningTotal = [];

    public function __construct($budgetID)
    {
        $this->budgetID = $budgetID;
    }

    /**
     * @return mixed
     */
    public function getBudgetID()
    {
        return $this->budgetID;
    }

    /**
     * @return \DateTime
     */
    public function getFirstTransactionDate()
    {
        return $this->firstTransactionDate;
    }

    /**
     * @param \DateTime $firstTransactionDate
     */
    public function setFirstTransactionDate($firstTransactionDate)
    {
        $this->firstTransactionDate = $firstTransactionDate;
    }

    /**
     * @return \DateTime
     */
    public function getLastTransactionDate()
    {
        return $this->lastTransactionDate;
    }

    /**
     * @param \DateTime $lastTransactionDate
     */
    public function setLastTransactionDate($lastTransactionDate)
    {
        $this->lastTransactionDate = $lastTransactionDate;
    }



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

    /**
     * @return mixed
     */
    public function getAverageFortnightlyIncome()
    {
        return $this->averageFortnightlyIncome;
    }

    /**
     * @param mixed $averageFortnightlyIncome
     */
    public function setAverageFortnightlyIncome($averageFortnightlyIncome)
    {
        $this->averageFortnightlyIncome = $averageFortnightlyIncome;
    }

    /**
     * @return \DateTime
     */
    public function getFirstIncomeTransactionDate()
    {
        return $this->firstIncomeTransactionDate;
    }

    /**
     * @param \DateTime $firstIncomeTransactionDate
     */
    public function setFirstIncomeTransactionDate($firstIncomeTransactionDate)
    {
        $this->firstIncomeTransactionDate = $firstIncomeTransactionDate;
    }

    /**
     * @return \DateTime
     */
    public function getLastIncomeTransactionDate()
    {
        return $this->lastIncomeTransactionDate;
    }

    /**
     * @param \DateTime $lastIncomeTransactionDate
     */
    public function setLastIncomeTransactionDate($lastIncomeTransactionDate)
    {
        $this->lastIncomeTransactionDate = $lastIncomeTransactionDate;
    }

    /**
     * @return mixed
     */
    public function getAverageFortnightlyPositive()
    {
        return $this->averageFortnightlyPositive;
    }

    /**
     * @param mixed $averageFortnightlyPositive
     */
    public function setAverageFortnightlyPositive($averageFortnightlyPositive)
    {
        $this->averageFortnightlyPositive = $averageFortnightlyPositive;
    }

    public function appendWeekRunningTotal($yearweek, $sum)
    {
        $year = substr($yearweek, 0, 4);
        $week = substr($yearweek, 4, 2);
        list($lastYear, $lastWeek, $lastSum, $lastTotal) = end($this->runningTotal);
        $total = bcadd($lastTotal, $sum, 2);

        /*// Give us a data point the week before if this is the start
        if($lastYear == 0) {
            $this->runningTotal = [[$year, $week - 1, 0, 0]];
        }*/
        $this->runningTotal[] = [$year, $week, $sum, $total];
    }

    public function getRunningTotalSparklineData()
    {
        $start = clone $this->firstTransactionDate;
        $end = clone $this->lastTransactionDate;
        $sparkline = [];

        if(sizeof($this->runningTotal) == 0) return implode(',', $sparkline);

        // Load first available transaction
        list($lastYear, $lastWeek, $lastSum, $lastTotal) = $this->runningTotal[0];
        $lastDate = new \DateTime($lastYear . "W" . $lastWeek);

        // Pad start of sparkline if first transaction is after our starting point
        while($start->diff($lastDate)->format("%r%a") > 7)
        {
            $start->add(new \DateInterval("P1W"));
            $sparkline[] = 0;
        }

        // Process our transactions
        foreach($this->runningTotal as $weekData)
        {
            list($year, $week, $sum, $total) = $weekData;
            $date = new \DateTime($year . "W" . $week);

            // If date is after our starting range we process it and before our end date
            if($start->diff($date)->format("%r%a") > 0 && $end->diff($date)->format("%r%a") < 0) {
                while ($lastDate->diff($date)->format("%r%a") > 7 && $lastDate->diff($end)->format("%r%a") > 7) {
                    $lastDate->add(new \DateInterval("P1W"));
                    $sparkline[] = $lastTotal;
                }
                $sparkline[] = $total;
            }
            $lastTotal = $total;
            $lastDate = $date;
        }

        // Pad end of sparkline if last transaction is before our end point
        while ($lastDate->diff($end)->format("%r%a") > 7) {
            $lastDate->add(new \DateInterval("P1W"));
            $sparkline[] = $lastTotal;
        }
        return implode(',', $sparkline);
    }

    public function getOverspend()
    {
        if (abs($this->averageFortnightlySpend) > $this->averageFortnightlyPositive)
        {
            return true;
        }
        return false;
    }

}