<?php

namespace Voryx\ThruwayBundle\Process;


use React\EventLoop\LoopInterface;

/**
 * Class Process
 * @package Voryx\ThruwayBundle\Process
 */
class Process extends \React\ChildProcess\Process
{
    /**
     * @var
     */
    private $name;

    /**
     * @var int
     */
    private $processNumber = 0;

    /**
     * @var
     */
    private $startedAt;

    /**
     * @var bool
     */
    private $autoRestart;

    /**
     * {@inheritdoc}
     * @param LoopInterface $loop
     * @param float $interval
     */
    public function start(LoopInterface $loop, $interval = 0.1)
    {

        if ($this->isRunning()) {
            return;
        }

        $this->startedAt = microtime(true);

        $this->once('exit', function ($exitCode, $termSignal) use ($loop) {
            $this->startedAt = null;

            //Auto restart
            if ($termSignal !== 15 && $this->autoRestart === true) {
                $this->start($loop);
            }

        });

        parent::start($loop, $interval);

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
    public function getProcessNumber()
    {
        return $this->processNumber;
    }

    /**
     * @param mixed $processNumber
     */
    public function setProcessNumber($processNumber)
    {
        $this->processNumber = $processNumber;
    }

    /**
     * @return mixed
     */
    public function getStartedAt()
    {
        return $this->startedAt;
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

}