<?php

namespace Thruway;

use Thruway\Message\ResultMessage;

/**
 * Class CallResult
 *
 * @package Thruway
 */
class CallResult extends \ArrayObject
{

    /**
     * @var \Thruway\Message\ResultMessage
     */
    private $resultMessage;

    /**
     * Constructor
     *
     * @param \Thruway\Message\ResultMessage $msg
     */
    public function __construct(ResultMessage $msg)
    {
        $this->resultMessage = $msg;

        parent::__construct($msg->getArguments());
    }

    /**
     * Conver to string
     * 
     * @return string
     */
    public function __toString()
    {
        return isset($this[0]) ? (string)$this[0] : "";
    }

    /**
     * Set Result Message
     *
     * @param \Thruway\Message\ResultMessage $msg
     */
    public function setResultMessage(ResultMessage $msg)
    {
        $this->resultMessage = $msg;
    }

    /**
     * @return \Thruway\Message\ResultMessage
     */
    public function getResultMessage()
    {
        return $this->resultMessage;
    }

    /**
     * Get arguments
     * 
     * @return mixed
     */
    public function getArguments()
    {
        return $this->getResultMessage()->getArguments();
    }

    /**
     * Get arguments kw
     * 
     * @return mixed
     */
    public function getArgumentsKw()
    {
        return $this->getResultMessage()->getArgumentsKw();
    }

    /**
     * Get result detail
     * 
     * @return mixed
     */
    public function getDetails()
    {
        return $this->getResultMessage()->getDetails();
    }

//    // ArrayAccess interface
//    /**
//     * (PHP 5 &gt;= 5.0.0)<br/>
//     * Whether a offset exists
//     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
//     * @param mixed $offset <p>
//     * An offset to check for.
//     * </p>
//     * @return boolean true on success or false on failure.
//     * </p>
//     * <p>
//     * The return value will be casted to boolean if non-boolean was returned.
//     */
//    public function offsetExists($offset)
//    {
//        $args = $this->getArguments();
//
//        if ($args === null) return false;
//
//        return isset($args[$offset]);
//    }
//
//    /**
//     * (PHP 5 &gt;= 5.0.0)<br/>
//     * Offset to retrieve
//     * @link http://php.net/manual/en/arrayaccess.offsetget.php
//     * @param mixed $offset <p>
//     * The offset to retrieve.
//     * </p>
//     * @return mixed Can return all value types.
//     */
//    public function offsetGet($offset)
//    {
//        $args = $this->getArguments();
//
//        return $args[$offset];
//    }
//
//    /**
//     * (PHP 5 &gt;= 5.0.0)<br/>
//     * Offset to set
//     * @link http://php.net/manual/en/arrayaccess.offsetset.php
//     * @param mixed $offset <p>
//     * The offset to assign the value to.
//     * </p>
//     * @param mixed $value <p>
//     * The value to set.
//     * </p>
//     * @return void
//     */
//    public function offsetSet($offset, $value)
//    {
//        if ($offset === null) {
//            $this->getArguments()[] = $value;
//        } else {
//            $this->getArguments()[$offset] = $value;
//        }
//    }
//
//    /**
//     * (PHP 5 &gt;= 5.0.0)<br/>
//     * Offset to unset
//     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
//     * @param mixed $offset <p>
//     * The offset to unset.
//     * </p>
//     * @return void
//     */
//    public function offsetUnset($offset)
//    {
//        unset($this->getArguments()[$offset]);
//    }

} 