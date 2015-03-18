<?php


namespace Voryx\ThruwayBundle\Process;


use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
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

        $promises = [];

        for ($x = 0; $x < $this->minInstances; $x++) {
            $deffer = new Deferred();

            //Check to see if the process is already running
            if (isset($this->processes[$x])) {
                $deffer->resolve($this->startInstance($x));
            } else {
                $deffer->resolve($this->addInstance($x));
            }

            $promises[] = $deffer->promise();

        }

        return \React\Promise\all($promises);

    }

    /**
     * Add a new instance to the Command
     *
     * @return null|\React\Promise\FulfilledPromise|\React\Promise\Promise
     */
    public function addInstance()
    {
        if (count($this->processes) >= (int)$this->maxInstances) {
            return \React\Promise\resolve();
        }

        $nextInstanceNumber = count($this->processes);

        return $this->startInstance($nextInstanceNumber);
    }

    /**
     * @param int $processNumber
     * @return \React\Promise\Promise
     */
    public function startInstance($processNumber = 0)
    {
        $deffer = new Deferred();

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
            $process->stdout->on('data', function ($output) use ($process, $deffer) {
                $deffer->resolve($output);
                echo "[{$process->getName()} {$process->getProcessNumber()} {$process->getPid()}] {$output}";
            });
            $process->stdout->on('error', function ($output) use ($process, $deffer) {
                $deffer->reject($output);
                echo "[{$process->getName()} {$process->getProcessNumber()} {$process->getPid()}] {$output}";
            });
        } else {
            $deffer->reject("Process {$this->name} is already running");
        }

        return $deffer->promise();
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
        $promises = [];

        if (count($this->processes) < 1){
            return \React\Promise\reject("No Process to stop");
        }

        foreach ($this->processes as $process) {
            $deffer = new Deferred();
            if ($process->isRunning()) {
                $process->terminate();
            }

            $process->on('exit', function () use ($deffer) {
                $deffer->resolve();
            });

            $promises[] = $deffer->promise();
        }

        return \React\Promise\all($promises);
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