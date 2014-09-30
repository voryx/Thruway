<?php

namespace Thruway;


use React\Dns\Model\Message;

/**
 * Class Result
 * 
 * @package Thruway
 */
class Result 
{
    /**
     * @var array|null
     */
    private $arguments;

    /**
     * @var array|null
     */
    private $argumentsKw;
    
    /**
     * Constructor
     * 
     * @param array|null $arguments
     * @param array|null $argumentsKw
     */
    function __construct($arguments = null, $argumentsKw = null)
    {
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
    }

    /**
     * @param array|null $arguments
     * @throws \InvalidArgumentException
     */
    public function setArguments($arguments)
    {
        if ($arguments !== null && ! is_array($arguments)) {
            throw new \InvalidArgumentException("Arguments must be null or an array");
        }

        $this->arguments = $arguments;
    }

    /**
     * @return array|null
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array|null $argumentsKw
     */
    public function setArgumentsKw($argumentsKw)
    {
        $this->argumentsKw = $argumentsKw;
    }

    /**
     * @return array|null
     */
    public function getArgumentsKw()
    {
        return $this->argumentsKw;
    }



}