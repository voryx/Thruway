<?php

namespace Voryx\ThruwayBundle\Command;


use Thruway\ClientSession;
use Thruway\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ThruwaySendMessageCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:send:message')
            ->setDescription('Send a WAMP message')
            ->addArgument('topic', InputArgument::REQUIRED, "Topic to send to WAMP server")
            ->addArgument('message', InputArgument::REQUIRED, "Message to send to ratchet server")
            ->setHelp("The <info>%command.name%</info> sends a WAMP message.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            $config = $this->getContainer()->getParameter('voryx_thruway');


            $connection = new Connection(
                [
                    "realm" => $config['realm'],
                    "url" => "ws://{$config['server']}:{$config['port']}"
                ]
            );

            $connection->on(
                'open',
                function (ClientSession $session) use ($connection, $input) {
                    $session->publish(
                        $input->getArgument('topic'),
                        [json_decode($input->getArgument('message'))],
                        [],
                        ["acknowledge" => true]
                    )
                        ->then(
                            function () use ($connection) {
                                echo "Publish Acknowledged!\n";
                                $connection->close();
                            },
                            function ($error) use ($connection) {
                                // publish failed
                                echo "Publish Error {$error}\n";
                                $connection->close();
                            }
                        );
                }
            );

            $connection->open();


        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("WAMP EXCEPTION:" . $e->getMessage());
            $output->writeln("Error... see log for more info");
        }
    }

    public function onData($data)
    {
        echo $data;
    }
}