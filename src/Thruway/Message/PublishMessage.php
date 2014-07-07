<?php

namespace Thruway\Message;


class PublishMessage extends Message
{
    const MSG_CODE = Message::MSG_PUBLISH;

    private $options;
    private $topicName;
    private $arguments;
    private $argumentsKw;

    function __construct($requestId, $options, $topicName, $arguments = null, $argumentsKw = null)
    {
        parent::__construct();

        $this->setRequestId($requestId);

        $this->arguments = $arguments;
        $this->argumentsKw = $argumentsKw;
        $this->options = $options;
        $this->topicName = $topicName;
    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_CODE;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        if ($this->getOptions() === null) {
            $this->setOptions(new \stdClass());
        }

        $a = array($this->getRequestId(), $this->getOptions(), $this->getTopicName());

        if ($this->getArguments() != null) {
            $a = array_merge($a, array($this->getArguments()));
            if ($this->getArgumentsKw() != null) {
                $a = array_merge($a, array($this->getArgumentsKw()));
            }
        }

        return $a;
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
    public function getArguments()
    {
        return $this->arguments;
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
    public function getArgumentsKw()
    {
        return $this->argumentsKw;
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
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $topicName
     */
    public function setTopicName($topicName)
    {
        $this->topicName = $topicName;
    }

    /**
     * @return mixed
     */
    public function getTopicName()
    {
        return $this->topicName;
    }


} 