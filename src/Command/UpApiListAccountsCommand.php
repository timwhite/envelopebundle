<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(name: 'up:api:listAccounts', description: 'Shows UP accounts fetched from API (debug)')]
class UpApiListAccountsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question('Enter the API secret for UP: ');
        $apiSecret = $helper->ask($input, $output, $question);

        if (empty($apiSecret)) {
            $output->writeln('<error>Missing API Secret</error>');

            return 1;
        }

        $httpClient = HttpClient::create();
        $result = $httpClient->request('GET', 'https://api.up.com.au/api/v1/accounts', [
            'auth_bearer' => $apiSecret,
        ]);

        dump(json_decode($result->getContent()));
    }
}
