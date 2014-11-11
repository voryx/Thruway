<?php

namespace Thruway\Message\Traits;

use Thruway\Message\Message;
use Thruway\Result;

/**
 * Arguments trait
 *
 * @package Thruway\Message
 */
trait ArgumentsTrait
{

    /**
     * @var mixed
     */
    private $arguments;

    /**
     * @var mixed
     */
    private $argumentsKw;

    /**
     * Get argument for serialization
     *
     * @return array
     */
    public function getArgumentsForSerialization()
    {
        $a = [];

        $args   = $this->getArguments();
        $argsKw = $this->getArgumentsKw();
        if ($args !== null && is_array($args) && count($args) > 0) {
            $a = array_merge($a, [$args]);
            if ($argsKw !== null && Message::isAssoc($argsKw) && count((array)$argsKw) > 0) {
                $a = array_merge($a, [$argsKw]);
            }
        } else {
            if ($argsKw !== null && Message::isAssoc($argsKw) && count((array)$argsKw) > 0) {
                $a = array_merge($a, [[], $argsKw]);
            }
        }

        return $a;
    }

    /**
     * Get arguments
     *
     * @return mixed
     */
    public function getArguments()
    {
        return $this->arguments ?: [];
    }

    /**
     * Set arguments
     *
     * @param mixed $arguments
     */
    public function setArguments($arguments)
    {
        if (is_array($arguments) || $arguments === null) {
            $this->arguments = $arguments;
        } else {
            $this->arguments = null;
            throw new \InvalidArgumentException();
        }
    }

    /**
     * Get arguments kw
     *
     * @return mixed
     */
    public function getArgumentsKw()
    {
        return $this->argumentsKw;
    }

    /**
     * Set arguments
     *
     * @param mixed $argumentsKw
     */
    public function setArgumentsKw($argumentsKw)
    {
        $this->argumentsKw = (object)$argumentsKw;
    }

    /**
     * Set arguments from result
     *
     * @param \Thruway\Result $result
     */
    public function setArgumentsFromResult(Result $result)
    {
        $this->setArguments($result->getArguments());
        $this->setArgumentsKw($result->getArgumentsKw());
    }

}
