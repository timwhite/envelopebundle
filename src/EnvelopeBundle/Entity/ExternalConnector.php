<?php


namespace EnvelopeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table
 */
class ExternalConnector
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="externalConnectors")
     */
    private $account;

    /**
     * External system ID. e.g. account ID
     *
     * @var string
     *
     * @ORM\Column (type="string", length=512, nullable=false)
     */
    private $systemId;

    /**
     * Externals system type, e.g. 'UP' for an UP bank account API connection
     *
     * @var string
     *
     * @ORM\Column (type="string", length=64, nullable=false)
     */
    private $systemType;

    /**
     * External system API credential. This is encoded using a secret key unique to each installation
     *
     * @var string|null
     *
     * @ORM\Column (type="string", length=2048, nullable=true)
     */
    private $systemCredential;

    /**
     * @return Account
     */
    public function getAccount(): ?Account
    {
        return $this->account;
    }

    /**
     * @param Account $account
     *
     * @return ExternalConnector
     */
    public function setAccount(Account $account): ExternalConnector
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @return string
     */
    public function getSystemId()
    {
        return $this->systemId;
    }

    /**
     * @param string $systemId
     *
     * @return ExternalConnector
     */
    public function setSystemId(string $systemId): ExternalConnector
    {
        $this->systemId = $systemId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSystemType()
    {
        return $this->systemType;
    }

    /**
     * @param string $systemType
     *
     * @return ExternalConnector
     */
    public function setSystemType(string $systemType): ExternalConnector
    {
        $this->systemType = $systemType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSystemCredential(): ?string
    {
        return $this->systemCredential;
    }

    /**
     * @param string|null $systemCredential
     *
     * @return ExternalConnector
     */
    public function setSystemCredential(?string $systemCredential): ExternalConnector
    {
        $this->systemCredential = $systemCredential;
        return $this;
    }


}