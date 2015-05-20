<?php


namespace Voryx\ThruwayBundle\Process;


use React\Promise\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;

/**
 * Class ProcessManager
 * @package Voryx\ThruwayBundle\Process
 */
class ProcessManager extends Client
{

    /**
     * @var Command[]
     */
    private $commands;

    /** @var  ContainerInterface */
    private $container;

    function __construct($realm, $loop, ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct($realm, $loop);
    }


    /**
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {

        $session->register('add_command', [$this, 'addCommand']);
        $session->register('status', [$this, 'status']);
        $session->register('start_process', [$this, 'startProcess']);
        $session->register('stop_process', [$this, 'stopProcess']);
        $session->register('restart_process', [$this, 'restartProcess']);
        $session->register('add_instance', [$this, 'addInstance']);


        //Congestion Manager Client.  This needs to be a separate client because it needs to listen on the main realm and not `process_manager`.
        $config            = $this->container->getParameter('voryx_thruway');
        $congestionManager = new Client($config['realm'], $session->getLoop());
        $congestionManager->addTransportProvider(new PawlTransportProvider($config['trusted_url']));

        $congestionManager->on('open', function (ClientSession $session) {
            $session->subscribe("thruway.metaevent.procedure.congestion", [$this, "onCongestion"]);
        });

        $congestionManager->start(false);

    }


    /**
     * @param Command $command
     * @throws \Exception
     */
    public function addCommand(Command $command)
    {
        $this->commands[$command->getName()] = $command;
        $command->setLoop($this->getLoop());
        $command->startProcess();

    }

    /**
     * @return array
     */
    public function status()
    {
        $status = [];

        foreach ($this->commands as $command) {

            $processes = $command->getProcesses();

            if (!$processes) {
                continue;
            }

            /** @var  $process Process */
            foreach ($processes as $process) {

                $runningStatus = null;

                if ($process->isRunning()) {
                    $runningStatus = "RUNNING";
                }

                if ($process->isStopped()) {
                    $runningStatus = "STOPPED";
                }

                if ($process->isTerminated()) {
                    $runningStatus = "TERMINATED";
                }

                $status[] = [
                    'name'           => $process->getName(),
                    'pid'            => $process->getPid(),
                    'process_number' => $process->getProcessNumber(),
                    'started_at'     => $process->getStartedAt(),
                    'status'         => $runningStatus,
                    'term_signal'    => $process->getTermSignal()

                ];
            }
        }

        return [$status];

    }

    /**
     * @param $args
     * @return \React\Promise\Promise
     */
    public function startProcess($args)
    {

        $deffer = new Deferred();

        $name = $args[0];
        if (isset($this->commands[$name])) {
            $deffer->resolve($this->commands[$name]->startProcess());
        } else {
            $deffer->reject("Can't find process {$name}");
        };

        return $deffer->promise();
    }

    /**
     * @param $args
     * @return \React\Promise\Promise
     */
    public function stopProcess($args)
    {
        $deffer = new Deferred();

        $name = $args[0];

        if (isset($this->commands[$name])) {
            $deffer->resolve($this->commands[$name]->stopProcess());
        } else {
            $deffer->reject("Unable to find process '{$name}'");
        };

        return $deffer->promise();
    }

    /**
     * @param $args
     */
    public function restartProcess($args)
    {

        $name = $args[0];

        if (isset($this->commands[$name])) {
            $this->stopProcess([$name])->then(function () use ($name) {
                echo "Stopped all process instances for {$name}" . PHP_EOL;
                $this->startProcess([$name])->then(function () use ($name) {
                    echo "Started all process instances for {$name}" . PHP_EOL;
                });
            });

        };
    }

    /**
     * @param $args
     */
    public function addInstance($args)
    {
        $name = $args[0];

        if (isset($this->commands[$name])) {
            $this->commands[$name]->addInstance();
        };
    }

    public function onCongestion($args)
    {
        //Get the name of the worker that handles this RPC
        $worker = $this->container->get('voryx.thruway.resource.mapper')->findWorker($args[0]->name);

        if (!isset($this->commands[$worker])) {
            return;
        }

        $this->commands[$worker]->addInstance();

    }
}