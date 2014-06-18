<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/12/14
 * Time: 12:35 PM
 */

namespace AutobahnPHP\Message;


/**
 * Class CallMessage
 * @package AutobahnPHP\Message
 */
class CallMessage extends Message
{

    /**
     * @var
     */
    private $requestId;

    /**
     * @var
     */
    private $options;

    /**
     * @var
     */
    private $procedureName;

    /**
     * @var null
     */
    private $arguments;

    /**
     * @var null
     */
    private $argumentsKw;

    /**
     * @param $requestId
     * @param $options
     * @param $procedureName
     * @param $arguments
     * @param $argumentsKw
     */
    function __construct($requestId, $options, $procedureName, $arguments = null, $argumentsKw = null)
    {
        $this->requestId = $requestId;
        $this->options = $options;
        $this->procedureName = $procedureName;
        $this->arguments = $arguments;
        $this->argumentsKw = $argumentsKw;
    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_CALL;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array(
            $this->requestId,
            $this->options,
            $this->procedureName,
            $this->arguments,
            $this->argumentsKw
        );
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
        $this->argumentsKw = $argumentsKw;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return mixed
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @param mixed $procedureName
     */
    public function setProcedureName($procedureName)
    {
        $this->procedureName = $procedureName;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param mixed $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

}