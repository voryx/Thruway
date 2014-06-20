<?php

namespace Thruway\Message;

class RegisterMessage extends Message
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
     * @param $requestId
     * @param $options
     * @param $procedureName
     */
    function __construct($requestId, $options, $procedureName)
    {
        $this->options = $options;
        $this->procedureName = $procedureName;
        $this->requestId = $requestId;
    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_REGISTER;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
       return array($this->requestId, $this->getOptions(), $this->getProcedureName());
    }


    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return mixed
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }



}