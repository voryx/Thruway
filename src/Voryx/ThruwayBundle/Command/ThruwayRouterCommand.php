<?php


namespace Voryx\ThruwayBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thruway\Transport\RatchetTransportProvider;

class ThruwayRouterCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:router:start')
            ->setDescription('Start Thruway WAMP client')
            ->setHelp("The <info>%command.name%</info> starts the Thruway WAMP client.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {

            $output->writeln("Making a go at starting the Thruway Router");

            //Configure stuff
            $config = $this->getContainer()->getParameter('voryx_thruway');

            //Get the Router Service
            $server = $this->getContainer()->get('voryx.thruway.server');

            //Trusted provider (bound to loopback and requires no authentication)
            $trustedProvider = new RatchetTransportProvider($config['router']['ip'], $config['router']['trusted_port']);
            $trustedProvider->setTrusted(true);
            $server->addTransportProvider($trustedProvider);

            $server->start();

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("EXCEPTION:" . $e->getMessage());
            $output->writeln("Error... see log for more info");
        }
    }
}
