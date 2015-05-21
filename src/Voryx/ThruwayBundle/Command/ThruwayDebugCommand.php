<?php

namespace Voryx\ThruwayBundle\Command;


use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Thruway\Peer\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thruway\Transport\PawlTransportProvider;
use Voryx\ThruwayBundle\Annotation\AnnotationInterface;
use Voryx\ThruwayBundle\Annotation\Register;
use Voryx\ThruwayBundle\Annotation\Subscribe;
use Voryx\ThruwayBundle\Mapping\URIClassMapping;

class ThruwayDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:debug')
            ->setDescription('List registered RPC and Subscriptions')
            ->addArgument('uri', InputArgument::OPTIONAL, 'URI name to get additional information');

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $name           = $input->getArgument('uri');
        $kernel         = $this->getContainer()->get('wamp_kernel');
        $resourceMapper = $kernel->getResourceMapper();


        if ($name) {
            $mappings = $resourceMapper->getMappings();
            if (isset($mappings[$name])) {
                /** @var URIClassMapping $mapping */
                $mapping = $mappings[$name];
                $output->writeln("URI:    {$name}");
                $output->writeln("File:   {$mapping->getMethod()->getFileName()}");
                $output->writeln("Method: {$mapping->getMethod()->getName()}");
                $output->writeln("Type:   {$this->getAnnotationType($mapping->getAnnotation())}");
                $output->writeln("Todo:   Add more annotation info");

            } else {
                $output->writeln("Sorry, we couldn't find {$name}");
            }


        } else {
            $workers = $resourceMapper->getAllMappings();

            $table = new Table($output);
            $table->setStyle('compact');
            $table->setHeaders(['URI', 'Type', 'Worker', 'File', 'Method']);

            /** @var  URIClassMapping[] $mappings */
            foreach ($workers as $workerName => $mappings) {
                foreach ($mappings as $uri => $mapping) {

                    $table->addRow([
                        $uri,
                        $this->getAnnotationType($mapping->getAnnotation()),
                        $workerName,
                        $mapping->getMethod()->getFileName(),
                        $mapping->getMethod()->getName()
                    ]);
                }
            }

            $table->render();
        }

    }

    /**
     * @param AnnotationInterface $annotation
     * @return string
     */
    private function getAnnotationType(AnnotationInterface $annotation)
    {

        if ($annotation instanceof Register) {
            return "RPC";
        }

        if ($annotation instanceof Subscribe) {
            return "Subscription";
        }

    }

}
