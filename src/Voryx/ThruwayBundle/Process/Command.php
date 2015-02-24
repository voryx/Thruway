<?php


namespace Voryx\ThruwayBundle\Process;


use React\EventLoop\LoopInterface;
use Voryx\ThruwayBundle\Process\Process;

/**
 * Class Command
 * @package Voryx\ThruwayBundle\Process
 */
class Command
{

    /**
     * @var
     */
    private $name;

    /**
     * @var
     */
    private $command;

    /**
     * @var
     */
    private $minInstances;

    /**
     * @var
     */
    private $maxInstances;

    /**
     * @var Process[]
     */
    private $processes;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var bool
     */
    private $autoRestart;

    /**
     * @param $name
     * @param $command
     * @param int $minInstances
     * @param int $maxInstances
     * @param bool $autoRestart
     */
    function __construct($name, $command, $minInstances = 1, $maxInstances = 1, $autoRestart = true)
    {
        $this->name         = $name;
        $this->command      = $command;
        $this->minInstances = $minInstances;
        $this->maxInstances = $maxInstances;
        $this->autoRestart  = $autoRestart;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param mixed $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return boolean
     */
    public function isAutoRestart()
    {
        return $this->autoRestart;
    }

    /**
     * @param boolean $autoRestart
     */
    public function setAutoRestart($autoRestart)
    {
        $this->autoRestart = $autoRestart;
    }


    /**
     * @return \React\ChildProcess\Process[]
     */
    public function getProcesses()
    {
        return $this->processes;
    }

    /**
     * @throws \Exception
     */
    public function startProcess()
    {

        for ($x = 0; $x < $this->minInstances; $x++) {

            //Check to see if the process is already running
            if (isset($this->processes[$x])) {
                $this->startInstance($x);
            } else {
                $this->addInstance($x);
            }

        }

    }

    /**
     *
     */
    public function addInstance()
    {
        if (count($this->processes) >= $this->maxInstances) {
            return;
        }

        $nextInstanceNumber = count($this->processes);
        $this->startInstance($nextInstanceNumber);
    }

    /**
     * @param int $processNumber
     */
    public function startInstance($processNumber = 0)
    {
        $process = isset($this->processes[$processNumber]) ? $this->processes[$processNumber] : null;

        if (!$process) {
            $process = new Process($this->command);
            $process->setName($this->name);
            $process->setProcessNumber($processNumber);
            $process->setAutoRestart($this->autoRestart);
            $this->processes[$processNumber] = $process;
        }

        if (!$process->isRunning()) {
            $process->start($this->loop);
            $process->stdout->on('data', function ($output) use ($process) {
                echo "[{$process->getName()} {$process->getProcessNumber()} {$process->getPid()}] {$output}";
            });
            $process->stdout->on('error', function ($output) use ($process) {
                echo "[{$process->getName()} {$process->getProcessNumber()} {$process->getPid()}] {$output}";
            });
        } else {
            //throw an already running exception
        }
    }

    /**
     * @param int $processNumber
     */
    public function stopInstance($processNumber = 0)
    {

        if (isset($this->processes[$processNumber])) {
            $this->processes[$processNumber]->terminate();
        }

    }

    /**
     * Stops all processes for this command
     */
    public function stopProcess()
    {

        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->terminate();
            }
        }

    }

    /**
     * @param LoopInterface $loop
     */
    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @return mixed
     */
    public function getMinInstances()
    {
        return $this->minInstances;
    }

    /**
     * @param mixed $minInstances
     */
    public function setMinInstances($minInstances)
    {
        $this->minInstances = $minInstances;
    }

    /**
     * @return mixed
     */
    public function getMaxInstances()
    {
        return $this->maxInstances;
    }

    /**
     * @param mixed $maxInstances
     */
    public function setMaxInstances($maxInstances)
    {
        $this->maxInstances = $maxInstances;
    }

}