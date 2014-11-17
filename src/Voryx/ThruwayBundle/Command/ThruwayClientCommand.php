<?php


namespace Voryx\ThruwayBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thruway\ConsoleLogger;
use Thruway\Peer\Client;
use Thruway\Transport\InternalClientTransport;
use Thruway\Transport\InternalClientTransportProvider;

/**
 * Class ThruwayClientCommand
 * @package Voryx\ThruwayBundle\Command
 */
class ThruwayClientCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:client:start')
            ->setDescription('Start Thruway WAMP client')
            ->setHelp("The <info>%command.name%</info> starts the Thruway WAMP client and router within one process.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {

            $output->writeln("Making a go at starting the Thruway Client and Server running within a single process.");

            $config = $this->getContainer()->getParameter('voryx_thruway');
            $server = $this->getContainer()->get('voryx.thruway.server');
            $loop   = $this->getContainer()->get('voryx.thruway.loop');
            $kernel = $this->getContainer()->get('wamp_kernel');
            $client = new Client($config['realm'], $loop);

            $kernel->setClient($client);

            $internalTransportProvider = new InternalClientTransportProvider($client);
            $server->addTransportProvider($internalTransportProvider);

            $client->setLogger(new ConsoleLogger());

            $output->writeln("You can connect to this server on 'ws://{$config['router']['ip']}:{$config['router']['port']}' with the realm '{$config['realm']}'");

            $server->start();

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("EXCEPTION:" . $e->getMessage());
            $output->writeln("Error... see log for more info");
        }
    }
}
