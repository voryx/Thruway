<?php


namespace Voryx\ThruwayBundle\Process;


use React\Promise\Deferred;
use Thruway\Peer\Client;

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
}