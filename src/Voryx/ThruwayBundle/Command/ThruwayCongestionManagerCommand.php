<?php

namespace Voryx\ThruwayBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thruway\Transport\PawlTransportProvider;
use Voryx\ThruwayBundle\Process\CongestionManager;

/**
 * Class ThruwayWorkerCommand
 * @package Voryx\ThruwayBundle\Command
 */
class ThruwayCongestionManagerCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:manager:start')
            ->setDescription('Start Thruway congestion manager')
            ->setHelp("The <info>%command.name%</info> starts the process manager");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {
            echo "Starting the process manager.\n";

            $config    = $this->getContainer()->getParameter('voryx_thruway');
            $loop      = $this->getContainer()->get('voryx.thruway.loop');
            $client    = new CongestionManager($config['realm'], $loop, $this->getContainer());
            $transport = new PawlTransportProvider($config['trusted_url']);

            $client->addTransportProvider($transport);
            $client->setAuthId('trusted_worker');
            $client->start();

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("WAMP EXCEPTION:" . $e->getMessage());
            $output->writeln("Error... see log for more info");
        }
    }

}
