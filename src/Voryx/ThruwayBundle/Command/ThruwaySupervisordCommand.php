<?php


namespace Voryx\ThruwayBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;


/**
 * Class ThruwaySupervisordCommand
 * @package Voryx\ThruwayTestBundle\Command
 */
class ThruwaySupervisordCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:supervisord')
            ->setDescription('Start Thruway Supervisor client')
            ->setHelp("The <info>%command.name%</info> starts the supervisor processes for thruway.")
            ->addArgument('action', InputArgument::REQUIRED, 'Actions, start, stop, status');
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        switch ($input->getArgument('action')) {
            case "start":
                $this->start($output);
                break;
            case "stop":
                $this->stop($output);
                break;
            case "status":
                $this->status($output);
                break;
            default:
                $output->writeln("Expected an action: start, stop, status");
        }

    }


    /**
     * Configure and start the workers
     *
     * @param OutputInterface $output
     */
    protected function start(OutputInterface $output)
    {

        try {

            $config = $this->getContainer()->getParameter('voryx_thruway');

            if (file_exists($config['supervisor']['pidfile'])) {
                $f    = fopen($config['supervisor']['pidfile'], 'r');
                $line = fgets($f);
                fclose($f);
                $output->writeln("Supervisor is already running with PID: {$line}\nYou'll need to stop it before you can start.");
                return;
            }

            //Create the log file if it doesn't already exist
            $logfile    = $this->getContainer()->get('kernel')->getRootDir() . "/logs/" . $config['supervisor']['logfile'];
            $pidfile    = $config['supervisor']['pidfile'];
            $configPath = $this->getContainer()->get('kernel')->locateResource($config['supervisor']['config']);

            touch($logfile);

            $output->writeln("Starting supervisord...");

            $process = new Process("{$config['supervisor']['executable']} -c {$configPath} --pidfile {$pidfile} --logfile {$logfile}");
            $process->run(function ($type, $buffer) use ($output) {
                if (Process::ERR === $type) {
                    $output->writeln("Error while starting supervisord: {$buffer}");
                } else {
                    $output->writeln("Supervisord started");
                }
            });

            $resourceMapper = $this->getContainer()->get('voryx.thruway.resource.mapper');
            $mappings       = $resourceMapper->getAllMappings();
            $supervisor     = $this->getContainer()->get('voryx.thruway.supervisor');
            $phpBinary      = PHP_BINARY;
            $env            = $this->getContainer()->get('kernel')->getEnvironment();

            $output->writeln("The environment is: {$env}");

            try {
                $supervisor->stopProcessGroup('thruway', false);
            } catch (\Exception $e) {
                $output->writeln("Looks like the thruway group is already stopped");
            }

            $processes = $supervisor->getAllProcessInfo();
            foreach ($processes as $process) {
                if ($process['group'] === 'thruway') {
                    $supervisor->removeProcessFromGroup('thruway', $process['name']);
                }
            }

            try {
                $supervisor->addProcessGroup('thruway');
            } catch (\Exception $e) {
                $output->writeln("The group thruway has already been added, which is okay");
            }


            //Onetime workers
            $defaultWorkers = [
                "router"  => "thruway:router:start",
                "manager" => "thruway:manager:start"
            ];

            $onetimeWorkers = array_merge($defaultWorkers, $config['supervisor']['onetime_workers']);

            foreach ($onetimeWorkers as $workerName => $command) {
                $output->writeln("Adding onetime worker: {$workerName}");

                //Add and start the Router
                $supervisor->addProgramToGroup('thruway', $workerName,
                    [
                        'command'      => "{$phpBinary} {$this->getContainer()->get('kernel')->getRootDir()}/console {$command} --env={$env}",
                        'autostart'    => 'true',
                        'autorestart'  => 'true',
                        'startsecs'    => '0',
                        'numprocs'     => 1,
                        'process_name' => '%(program_name)s'
                    ]);
                sleep(3);
            }


            //Add the workers to the config, but don't start them yet.
            foreach ($mappings as $workerName => $mapping) {
                $output->writeln("Adding workers: {$workerName}");
                $workerAnnotation = $resourceMapper->getWorkerAnnotation($workerName);

                if ($workerAnnotation) {
                    $numprocs = $workerAnnotation->getMaxProcesses() ?: $config['supervisor']['workers'];
                } else {
                    $numprocs = $config['supervisor']['workers'];
                }

                $supervisor->addProgramToGroup('thruway', $workerName,
                    [
                        'command'      => "{$phpBinary} {$this->getContainer()->get('kernel')->getRootDir()}/console thruway:worker:start {$workerName} %(process_num)d --env={$env}",
                        'autostart'    => 'false',
                        'autorestart'  => 'true',
                        'startsecs'    => '0',
                        'numprocs'     => $numprocs,
                        'process_name' => '%(program_name)s_%(process_num)02d'

                    ]);
                sleep(3);
                $supervisor->startProcess("thruway:{$workerName}_00");
            }

            $output->writeln("Done");

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("EXCEPTION:" . $e->getMessage());
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function stop(OutputInterface $output)
    {
        try {
            $output->writeln("Stopping supervisord...");

            $supervisor = $this->getContainer()->get('voryx.thruway.supervisor');
            $supervisor->shutdown();

            $output->writeln("Stopped");

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("EXCEPTION:" . $e->getMessage());
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function status(OutputInterface $output)
    {
        try {
            $supervisor = $this->getContainer()->get('voryx.thruway.supervisor');
            $output->writeln("ID: {$supervisor->getIdentification()}");
            $output->writeln("PID: {$supervisor->getPID()}");
            $output->writeln("API Version: {$supervisor->getAPIVersion()}");

            $output->writeln("[PID] group:name - description state");

            foreach ($supervisor->getAllProcessInfo() as $process) {
                $output->writeln("[{$process['pid']}] {$process['group']}:{$process['name']} - {$process['description']} {$process['statename']}");
            }

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("EXCEPTION:" . $e->getMessage());
        }
    }
}