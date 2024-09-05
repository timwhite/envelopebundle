<?php

namespace App\Command;

use _PHPStan_b7fe9900d\Symfony\Component\Console\Attribute\AsCommand;
use App\Entity\ExternalConnector;
use Doctrine\ORM\EntityManagerInterface;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use ParagonIE\HiddenString\HiddenString;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpFoundation\ParameterBag;

#[AsCommand(name: 'account:store-api-secret', description: 'Stores API secret for external connector')]
class StoreApiSecretCommand extends Command
{
    private $apiSecretKeyFile;

    public function __construct(protected readonly EntityManagerInterface $em, protected readonly ParameterBag $parameterBag)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('externalConnectorId', InputArgument::REQUIRED, 'ID of External Connector to store secret for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->apiSecretKeyFile = $this->parameterBag->get('api_secret_key_file');

        $this->checkEncryptionKey($output);
        $externalConnectorId = $input->getArgument('externalConnectorId');
        /** @var ExternalConnector $externalConnector */
        $externalConnector = $this->em->getRepository(ExternalConnector::class)->find($externalConnectorId);
        if (empty($externalConnector)) {
            $output->writeln('<error>External connector not found</error>');

            return 1;
        }

        $helper = $this->getHelper('question');
        $question = new Question('Enter the API secret for '.$externalConnector->getSystemType().':'.$externalConnector->getSystemId().' ('.$externalConnector->getAccount()->getName().'): ');
        $apiSecret = $helper->ask($input, $output, $question);

        if (empty($apiSecret)) {
            $output->writeln('<error>Missing API Secret</error>');

            return 1;
        }

        $encryptionKey = KeyFactory::loadEncryptionKey($this->apiSecretKeyFile);

        $encryptedApiSecret = Symmetric::encrypt(new HiddenString($apiSecret), $encryptionKey, Halite::ENCODE_BASE64URLSAFE);
        $externalConnector->setSystemCredential($encryptedApiSecret);
        $this->em->persist($externalConnector);
        $this->em->flush();
        $output->writeln('<info>API Secret stored successfully</info>');
    }

    protected function checkEncryptionKey(OutputInterface $output)
    {
        $secretFile = $this->apiSecretKeyFile;
        if (!file_exists($secretFile) || empty(file_get_contents($secretFile))) {
            $output->writeln("<warning>API Secret Key File is empty or missing, creating new key file. Please protect $secretFile</warning>");
            $encKey = KeyFactory::generateEncryptionKey();
            KeyFactory::save($encKey, $secretFile);
        }
    }
}
