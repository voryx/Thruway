<?php

namespace Thruway\Message;


class PublishMessage extends Message
{
    use ArgumentsTrait;

    const MSG_CODE = Message::MSG_PUBLISH;

    private $options;
    private $topicName;
    private $requestId;

    function __construct($requestId, $options, $topicName, $arguments = null, $argumentsKw = null)
    {
        parent::__construct();

        $this->setRequestId($requestId);

        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
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

        $options = (object)$this->getOptions();

        $a = array($this->getRequestId(), $options, $this->getTopicName());

        $a = array_merge($a, $this->getArgumentsForSerialization());

        return $a;
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

    /**
     * @param mixed $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }



} 