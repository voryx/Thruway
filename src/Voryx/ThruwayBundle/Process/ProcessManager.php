<?php


namespace Voryx\ThruwayBundle\Process;


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
            /** @var  $process Process */
            foreach ($command->getProcesses() as $process) {

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
     */
    public function startProcess($args)
    {
        $name = $args[0];
        if (isset($this->commands[$name])) {
            $this->commands[$name]->startProcess();
        };
    }

    /**
     * @param $args
     */
    public function stopProcess($args)
    {
        $name = $args[0];

        if (isset($this->commands[$name])) {
            $this->commands[$name]->stopProcess();
        };
    }

    /**
     * @param $args
     */
    public function restartProcess($args)
    {
        $name = $args[0];

        if (isset($this->commands[$name])) {
            $this->stopProcess([$name]);
            $this->startProcess([$name]);
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