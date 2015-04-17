<?php

namespace Voryx\ThruwayBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Thruway\Peer\Client;
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
            ->addArgument('instance', InputArgument::OPTIONAL, 'Worker instance number', 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {
            echo "Making a go at starting a Thruway worker.\n";

            $name             = $input->getArgument('name');
            $config           = $this->getContainer()->getParameter('voryx_thruway');
            $loop             = $this->getContainer()->get('voryx.thruway.loop');
            $kernel           = $this->getContainer()->get('wamp_kernel');
            $workerAnnotation = $kernel->getResourceMapper()->getWorkerAnnotation($name);

            if ($workerAnnotation) {
                $realm = $workerAnnotation->getRealm() ?: $config['realm'];
                $url   = $workerAnnotation->getUrl() ?: $config['url'];
            } else {
                $realm = $config['realm'];
                $url   = $config['url'];
            }

            $transport = new PawlTransportProvider($uri);
            $client    = new Client($realm, $loop);

            $client->addTransportProvider($transport);

            $kernel->setProcessName($name);
            $kernel->setClient($client);
            $kernel->setProcessInstance($input->getArgument('instance'));

            $client->start();

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("EXCEPTION:" . $e->getMessage());
        }
    }
}
