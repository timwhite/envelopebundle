<?php


namespace EnvelopeBundle\Service;


use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use EnvelopeBundle\Entity\Account;
use EnvelopeBundle\Entity\ExternalConnector;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use ParagonIE\HiddenString\HiddenString;
use Symfony\Component\HttpFoundation\ParameterBag;

class ApiImportService
{
    private ParameterBag $parameterBag;
    private EntityManagerInterface $em;

    public function __construct(ParameterBag $parameterBag, EntityManagerInterface $entityManager)
    {
        $this->parameterBag = $parameterBag;
        $this->em = $entityManager;
    }

    /**
     * @param Account       $account
     * @param DateTime|null $startDate
     */
    public function importAccount(ExternalConnector $externalConnector, ?DateTime $startDate = null)
    {
        switch ($externalConnector->getSystemType()) {
            case 'UP':
                $this->importUp($externalConnector, $startDate);
        }

    }

    private function importUp(ExternalConnector $externalConnector, ?DateTime $startDate = null)
    {
        $encryptionKey = KeyFactory::loadEncryptionKey($this->parameterBag->get('api_secret_key_file'));
        $apiSecret = Symmetric::decrypt($externalConnector->getSystemCredential(), $encryptionKey,Halite::ENCODE_BASE64URLSAFE);
    }



}