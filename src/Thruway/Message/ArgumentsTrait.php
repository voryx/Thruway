<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 9/6/14
 * Time: 10:23 PM
 */

namespace Thruway\Message;


use Thruway\Result;

trait ArgumentsTrait {
    /**
     * @var null
     */
    private $arguments;

    /**
     * @var null
     */
    private $argumentsKw;

    public function getArgumentsForSerialization() {
        $a = array();

        $args = $this->getArguments();
        $argsKw = $this->getArgumentsKw();
        if ($args !== null && is_array($args) && count($args) > 0) {
            $a = array_merge($a, array($args));
            if ($argsKw !== null && Message::isAssoc($argsKw) && count((array)$argsKw) > 0) {
                $a = array_merge($a, array($argsKw));
            }
        } else {
            if ($argsKw !== null && Message::isAssoc($argsKw) && count((array)$argsKw) > 0) {
                $a = array_merge($a, array(array(), $argsKw));
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
    public function setArgumentsFromResult(Result $result) {
        $this->setArguments($result->getArguments());
        $this->setArgumentsKw($result->getArgumentsKw());
    }
} 