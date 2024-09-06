<?php

namespace App\Command;

use App\Entity\ExternalConnector;
use Doctrine\ORM\EntityManagerInterface;
use EnvelopeBundle\Service\ApiImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'up:api:importTransactions', description: 'Import transactions from UP account API')]
class UpApiImportTransactionsCommand extends Command
{
    public function __construct(protected readonly EntityManagerInterface $entityManager,
        private readonly ApiImportService $apiImportService)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('externalConnectorId', InputArgument::REQUIRED, 'ID of External Connector to fetch for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $externalConnectorId = $input->getArgument('externalConnectorId');
        $externalConnector = $this->entityManager->getRepository(ExternalConnector::class)->find($externalConnectorId);
        if (empty($externalConnector)) {
            $output->writeln('<error>External connector not found</error>');

            return Command::FAILURE;
        }

        if ('UP' !== $externalConnector->getSystemType()) {
            $output->writeln('<error>External connector not of type UP</error>');

            return Command::FAILURE;
        }

        $this->apiImportService->importAccount($externalConnector);

        return Command::SUCCESS;
    }
}
