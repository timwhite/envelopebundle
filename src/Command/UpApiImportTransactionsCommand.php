<?php


namespace EnvelopeBundle\Command;

use App\Entity\ExternalConnector;
use EnvelopeBundle\Service\ApiImportService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpApiImportTransactionsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("up:api:importTransactions")
            ->setDescription("Import transactions from UP account API")
            ->addArgument('externalConnectorId', InputArgument::REQUIRED, 'ID of External Connector to fetch for')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $externalConnectorId = $input->getArgument('externalConnectorId');
        /** @var ExternalConnector $externalConnector */
        $externalConnector = $this->getContainer()->get("doctrine")->getManager()->getRepository(ExternalConnector::class)->find($externalConnectorId);
        if (empty($externalConnector)) {
            $output->writeln('<error>External connector not found</error>');
            return 1;
        }

        if ($externalConnector->getSystemType() !== 'UP') {
            $output->writeln('<error>External connector not of type UP</error>');
            return 1;
        }

        /** @var ApiImportService $apiImport */
        $apiImport = $this->getContainer()->get('api_import');

        $apiImport->importAccount($externalConnector);
        return 0;
    }


}