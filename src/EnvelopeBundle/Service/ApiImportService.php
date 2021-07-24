<?php


namespace EnvelopeBundle\Service;


use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use EnvelopeBundle\Entity\ExternalConnector;
use EnvelopeBundle\Entity\Import;
use EnvelopeBundle\Entity\Transaction;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;

class ApiImportService
{
    private ParameterBagInterface $parameterBag;
    private EntityManagerInterface $em;

    // @TODO enable ParameterBagInterface after 4.0 upgrade
    public function __construct(/*ParameterBagInterface $parameterBag,*/ EntityManagerInterface $entityManager)
    {
        //$this->parameterBag = $parameterBag;
        $this->em = $entityManager;
    }

    /**
     * @param ExternalConnector $externalConnector
     * @param DateTime|null     $startDate
     */
    public function importAccount(ExternalConnector $externalConnector, ?DateTime $startDate = null)
    {
        switch ($externalConnector->getSystemType()) {
            case 'UP':
                $this->importUp($externalConnector, $startDate);
                break;
        }

    }

    private function importUp(ExternalConnector $externalConnector, ?DateTime $startDate = null)
    {
        $encryptionKey = KeyFactory::loadEncryptionKey($this->parameterBag->get('api_secret_key_file'));
        $apiSecret = Symmetric::decrypt(
            $externalConnector->getSystemCredential(),
            $encryptionKey,
            Halite::ENCODE_BASE64URLSAFE
        )->getString();

        $requestParams = [
            'page[size]' => 100,

            'filter[status]' => 'SETTLED'
        ];
        if ($startDate) {
            $requestParams['filter[since]'] = $startDate->format(\DateTimeInterface::RFC3339);
        }

        $requestUrl = 'https://api.up.com.au/api/v1/accounts/' . $externalConnector->getSystemId() . '/transactions?';
        $requestUrl = $requestUrl . http_build_query($requestParams);

        $import = null;

        $httpClient = HttpClient::create();
        while ($requestUrl) {
            $result = $httpClient->request(
                'GET',
                $requestUrl,
                [
                    'auth_bearer' => $apiSecret
                ]
            );
            $results = json_decode($result->getContent());
            foreach ($results->data as $externalTransaction) {
                // Check if transaction already exists based on ID
                $transaction = $this->findTransactionByExternalId($externalConnector, $externalTransaction->id);
                if ($transaction) {
                    print("Found transaction based on id {$externalTransaction->id}\n");
                    continue;
                }

                $description = $externalTransaction->attributes->description;

                // Check if transaction already exists based on metadata
                $transaction = $this->findTransactionByMetadata(
                    $externalConnector,
                    $externalTransaction->attributes->amount->value,
                    $description,
                    (new DateTime($externalTransaction->attributes->createdAt))->format('Y-m-d')
                );
                if ($transaction) {
                    print("Found transaction based on metadata {$externalTransaction->id}. Updating with externalID\n");
                    if (empty($transaction->getExtra())) {
                        $transaction->setExtra((array)$externalTransaction);
                    }
                    $transaction->setExternalId($externalTransaction->id);
                    $this->em->persist($transaction);
                    continue;
                }

                // Create new transaction
                print("{$externalTransaction->id} not found, creating new transaction\n");

                if (!$import) {
                    $import = new Import();
                    $this->em->persist($import);
                }

                $fullDescription = "{$externalTransaction->attributes->description} - {$externalTransaction->attributes->rawText}";

                // For transactions that are not in AUD, include the foreign amount
                if ($externalTransaction->attributes->foreignAmount && $externalTransaction->attributes->foreignAmount->currencyCode !== 'AUD') {
                    $fullDescription .= " ({$externalTransaction->attributes->foreignAmount->currencyCode} {$externalTransaction->attributes->foreignAmount->value})";
                }

                $description = $externalTransaction->attributes->description;
                $date = new DateTime($externalTransaction->attributes->createdAt);
                $amount = $externalTransaction->attributes->amount->value;
                $extra = (array)$externalTransaction;

                $transaction = new Transaction();
                $transaction->setAccount($externalConnector->getAccount());
                $transaction->setDate($date);
                $transaction->setAmount($amount);
                $transaction->setDescription($description);
                $transaction->setFullDescription($fullDescription);
                $transaction->setImport($import);
                $transaction->setExtra($extra);
                $transaction->setExternalId($externalTransaction->id);

                $this->em->persist($transaction);
            }

            $this->em->flush();

            if (!empty($results->links->next)) {
                $requestUrl = $results->links->next;
                print("Next page -> $requestUrl");
            } else {
                $requestUrl = null;
            }
        }
    }

    private function findTransactionByExternalId(ExternalConnector $externalConnector, $transactionId)
    {
        return $this->em->getRepository(Transaction::class)->findBy(
            [
                'externalId' => $transactionId,
                'account' => $externalConnector->getAccount()
            ]
        );
    }

    /**
     * @param ExternalConnector $externalConnector
     * @param                   $amount
     * @param                   $description
     * @param                   $date
     *
     * @return Transaction|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function findTransactionByMetadata(ExternalConnector $externalConnector, $amount, $description, $date)
    {
        $transaction = $this->em->getRepository(Transaction::class)->createQueryBuilder('t')
            //->select(Transaction::class, 't')
            ->andWhere('t.account = :account')
            ->andWhere('t.amount = :amount')
            ->andWhere('t.description LIKE :description')
            ->andWhere('t.date = :date')
            ->setParameters(
                [
                    'account' => $externalConnector->getAccount(),
                    'amount' => $amount,
                    'description' => "%$description%",
                    'date' => $date

                ]
            )
            ->getQuery()->getOneOrNullResult();

        if ($transaction) {
            return $transaction;
        }

        // If not found, retry with this overrides

        // Override description for NAB Transfers
        if ($description == 'NAB Transactional') {
            $description = 'NAB Transfer';
        }

        // Override for referral bonus
        if ($description == 'Bonus Payment') {
            $description = 'Referral Bonus';
        }

        return $this->em->getRepository(Transaction::class)->createQueryBuilder('t')
            //->select(Transaction::class, 't')
            ->andWhere('t.account = :account')
            ->andWhere('t.amount = :amount')
            ->andWhere('t.description LIKE :description')
            ->andWhere('t.date = :date')
            ->setParameters(
                [
                    'account' => $externalConnector->getAccount(),
                    'amount' => $amount,
                    'description' => "%$description%",
                    'date' => $date

                ]
            )
            ->getQuery()->getOneOrNullResult();


    }


}