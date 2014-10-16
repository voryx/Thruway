<?php

namespace Voryx\ThruwayBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Thruway\ClientWampCraAuthenticator;
use Thruway\ConsoleLogger;
use Thruway\Peer\Client;
use Thruway\Transport\InternalClientTransportProvider;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thruway\Transport\PawlTransportProvider;

class ThruwayWorkerCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:worker:start')
            ->setDescription('Start Thruway WAMP worker')
            ->setHelp("The <info>%command.name%</info> starts the Thruway WAMP client.")
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the worker you\'re starting')
            ->addArgument('instance', InputArgument::REQUIRED, 'Worker instance number');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {
            echo "Making a go at starting a Thruway worker.\n";

            $name      = $input->getArgument('name');
            $config    = $this->getContainer()->getParameter('voryx_thruway');
            $loop      = $this->getContainer()->get('voryx.thruway.loop');
            $client    = new Client($config['realm'], $loop);
            $transport = new PawlTransportProvider("ws://127.0.0.1:9991");

            $client->addTransportProvider($transport);
            $client->setAuthId('trusted_worker');
            $client->setLogger(new ConsoleLogger());

            $worker = $this->getContainer()->get('voryx.thruway.connection');
            $worker->setWorkerName($name);
            $worker->setClient($client);
            $worker->setWorkerInstance($input->getArgument('instance'));

            $client->start();

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("WAMP EXCEPTION:" . $e->getMessage());
            $output->writeln("Error... see log for more info");
        }
    }
}