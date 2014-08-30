<?php


namespace Voryx\ThruwayBundle\Command;

use Thruway\Transport\InternalClientTransportProvider;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setHelp("The <info>%command.name%</info> starts the Thruway WAMP client.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {

            $config = $this->getContainer()->getParameter('voryx_thruway');
            echo "Making a go at starting the Thruway client.\n";
            $server = $this->getContainer()->get('voryx.thruway.server');

            $this->getContainer()->get('voryx.thruway.connection');

            //Add internal clients that are defined in the config
            //@todo move this to the config
            foreach ($config['clients'] as $clientService) {
                $c = $this->getContainer()->get($clientService);
                $ctp = new InternalClientTransportProvider($c);
                $server->addTransportProvider($ctp);
            }

            $server->start();

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("WAMP EXCEPTION:" . $e->getMessage());
            $output->writeln("Error... see log for more info");
        }
    }
}