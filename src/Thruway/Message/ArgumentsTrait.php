<?php

namespace Thruway\Message;

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
            if ($argsKw !== null && Message::isAssoc($argsKw) && count((array) $argsKw) > 0) {
                $a = array_merge($a, [$argsKw]);
            }
        } else {
            if ($argsKw !== null && Message::isAssoc($argsKw) && count((array) $argsKw) > 0) {
                $a = array_merge($a, [[], $argsKw]);
            }
        }

        return $a;
    }

    /**
     * @return mixed
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param mixed $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return mixed
     */
    public function getArgumentsKw()
    {
        return $this->argumentsKw;
    }

    /**
     * @param mixed $argumentsKw
     */
    public function setArgumentsKw($argumentsKw)
    {
        $this->argumentsKw = Message::shouldBeDictionary($argumentsKw);
    }

    /**
     * @param Result $result
     */
    public function setArgumentsFromResult(Result $result)
    {
        $this->setArguments($result->getArguments());
        $this->setArgumentsKw($result->getArgumentsKw());
    }

}
