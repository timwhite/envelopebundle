<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use TeamTNT\TNTSearch\Classifier\TNTClassifier;

/**
 * AccessGroup.
 */
#[ORM\Table]
#[ORM\Entity]
class AccessGroup
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
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $classifierSerialized = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastClassified = null;

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
     * Set name.
     *
     * @param string $name
     *
     * @return AccessGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Store the TNT Classifier in the DB.
     *
     * @return $this
     */
    public function storeClassifier(TNTClassifier $classifier): self
    {
        $this->classifierSerialized = serialize($classifier);
        $this->lastClassified = new \DateTimeImmutable();

        return $this;
    }

    public function getClassifier(): ?TNTClassifier
    {
        if (!$this->classifierSerialized) {
            return null;
        }

        return unserialize($this->classifierSerialized);
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getLastClassified(): ?\DateTimeImmutable
    {
        return $this->lastClassified;
    }
}
