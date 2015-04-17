<?php


namespace Voryx\ThruwayBundle\Command;


use Psr\Log\NullLogger;
use React\Promise\Deferred;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thruway\ClientSession;
use Thruway\Connection;
use Thruway\Logging\Logger;
use Thruway\Transport\PawlTransportProvider;
use Voryx\ThruwayBundle\Process\Command;
use Voryx\ThruwayBundle\Process\ProcessManager;


/**
 * Class ThruwayProcessCommand
 *
 * @package Voryx\ThruwayTestBundle\Command
 */
class ThruwayProcessCommand extends ContainerAwareCommand
{

    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * @var
     */
    private $config;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:process')
            ->setAliases(['tp'])
            ->setDescription('Thruway Process Manager')
            ->setHelp("The <info>%command.name%</info> manages thruway sub processes (workers).")
            ->addArgument('action', InputArgument::REQUIRED, 'Actions: start, status')
            ->addArgument('worker', InputArgument::OPTIONAL, 'Actions for individual workers: start, stop, restart');
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Logger::set(new NullLogger());

        $this->input  = $input;
        $this->output = $output;
        $this->config = $this->getContainer()->getParameter('voryx_thruway');

        switch ($input->getArgument('action')) {
            case "start":
                $this->start();
                break;
            case "stop":
                $this->stop();
                break;
            case "restart":
                $this->restart();
                break;
            case "status":
                $this->status();
                break;
            case "add":
                $this->add();
                break;
            default:
                $output->writeln("Expected an action: start, stop, status");
        }

    }


    /**
     * Configure and start the workers
     *
     */
    protected function start()
    {

        if ($this->input->getArgument('worker')) {
            $this->startWorker($this->input->getArgument('worker'));
        } else {
            $this->startManager();
        }
    }


    /**
     *
     */
    private function startManager()
    {
        try {

            $env  = $this->getContainer()->get('kernel')->getEnvironment();
            $loop = $this->getContainer()->get('voryx.thruway.loop');

            $this->processManager = new ProcessManager("process_manager", $loop);
            $this->processManager->addTransportProvider(new PawlTransportProvider($this->config['trusted_url']));

            $this->output->writeln("Starting Thruway Workers...");
            $this->output->writeln("The environment is: {$env}");

            //Add processes for Symfony Command Workers
            $this->addSymfonyCmdWorkers($env);

            //Add external guest Workers
            $this->addShellCmdWorkers();

            //Add processes for regular Workers defined by annotations
            $this->addWorkers($env);

            $this->output->writeln("Done");

            $this->processManager->start();

        } catch (\Exception $e) {
            $logger = $this->getContainer()->get('logger');
            $logger->addCritical("EXCEPTION:" . $e->getMessage());
        }
    }


    /**
     * Make WAMP call
     *
     * @param $uri
     * @param array $args
     * @return null
     */
    private function call($uri, $args = [])
    {
        $result = null;
        $realm  = 'process_manager';

        $connection = new Connection(['realm' => $realm, 'url' => $this->config['trusted_url'], "max_retries" => 0]);
        $connection->on('open', function (ClientSession $session) use ($uri, $args, $connection, &$result) {
            $session->call($uri, $args)->then(
                function ($res) use ($connection, &$result) {
                    $result = $res[0];
                    $connection->close();
                },
                function ($error) use ($connection, &$result) {
                    $result = $error;
                    $connection->close();
                }
            );
        });

        $connection->open();

        return $result;
    }

    /**
     * @param $worker
     */
    private function startWorker($worker)
    {
        $this->call('start_process', [$worker]);
    }

    /**
     * Stop Worker
     */
    protected function stop()
    {
        if (!$this->input->getArgument('worker')) {
            return;
        }

        $worker = $this->input->getArgument('worker');
        $this->call('stop_process', [$worker]);
    }


    /**
     *
     */
    protected function restart()
    {
        if (!$this->input->getArgument('worker')) {
            return;
        }

        $worker = $this->input->getArgument('worker');
        $this->call('restart_process', [$worker]);
    }

    /**
     * Get the process status
     *
     */
    protected function status()
    {
        $statuses = $this->call('status');

        if (!$statuses) {
            return;
        }

        foreach ($statuses as $status) {

            $uptime = "Not Started";
            if (isset($status->started_at) && $status->status === "RUNNING") {
                $uptime = "up since " . date("l F jS \@ g:i:s a", $status->started_at);
            }

            $pid = null;
            if (isset($status->pid) && $status->status === "RUNNING") {
                $pid = "pid {$status->pid}";
            }

            $this->output->writeln(sprintf("%-25s %-3s %-10s %s, %s ", $status->name, $status->process_number, $status->status,
                $pid, $uptime));
        }
    }

    /**
     * Add a new worker instance to the process
     */
    protected function add()
    {
        if (!$this->input->getArgument('worker')) {
            return;
        }
        $worker = $this->input->getArgument('worker');
        $this->call('add_instance', [$worker]);

    }

    /**
     * Add symfony command workers.  These are workers that will only ever have one instance running
     * @param $env
     */
    protected function addSymfonyCmdWorkers($env)
    {

        $phpBinary = PHP_BINARY;

        //Default Symfony Command Workers
        $defaultWorkers = [
            "router"  => "thruway:router:start",
            "manager" => "thruway:manager:start"
        ];

        $onetimeWorkers = array_merge($defaultWorkers, $this->config['workers']['symfony_commands']);

        foreach ($onetimeWorkers as $workerName => $command) {

            if (!$command) {
                continue;
            }

            $this->output->writeln("Adding onetime Symfony worker: {$workerName}");

            $cmd     = "{$phpBinary} {$this->getContainer()->get('kernel')->getRootDir()}/console --env={$env} {$command}";
            $command = new Command($workerName, $cmd);

            $this->processManager->addCommand($command);

        }
    }


    /**
     * Add regular shell command workers.
     */
    protected function addShellCmdWorkers()
    {

        $shellWorkers = $this->config['workers']['shell_commands'];

        foreach ($shellWorkers as $workerName => $command) {

            if (!$command) {
                continue;
            }

            $this->output->writeln("Adding onetime shell worker: {$workerName}");
            $command = new Command($workerName, $command);

            $this->processManager->addCommand($command);

        }
    }


    /**
     * Add regular workers.  Theses are workers that can have multiple instances running
     *
     * @param $env
     */
    protected function addWorkers($env)
    {
        $phpBinary      = PHP_BINARY;
        $resourceMapper = $this->getContainer()->get('voryx.thruway.resource.mapper');
        $mappings       = $resourceMapper->getAllMappings();

        foreach ($mappings as $workerName => $mapping) {
            $this->output->writeln("Adding workers: {$workerName}");

            $workerAnnotation = $resourceMapper->getWorkerAnnotation($workerName);
            $numprocs         = $workerAnnotation ? $workerAnnotation->getMaxProcesses() : 1;
            $cmd              = "{$phpBinary} {$this->getContainer()->get('kernel')->getRootDir()}/console --env={$env} thruway:worker:start {$workerName} 0";
            $command          = new Command($workerName, $cmd);

            $command->setMaxInstances($numprocs);
            $this->processManager->addCommand($command);

        }
    }
}
