<?php

namespace EnvelopeBundle\Entity;

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
}
