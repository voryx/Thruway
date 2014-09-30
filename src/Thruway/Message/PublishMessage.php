<?php

namespace Thruway\Message;

/**
 * Class Publish message
 * Sent by a Publisher to a Broker to publish an event.
 * <code>[PUBLISH, Request|id, Options|dict, Topic|uri]</code>
 * <code>[PUBLISH, Request|id, Options|dict, Topic|uri, Arguments|list]</code>
 * <code>[PUBLISH, Request|id, Options|dict, Topic|uri, Arguments|list, ArgumentsKw|dict]</code>
 *
 * @package Thruway\Message
 */
class PublishMessage extends Message
{

    /**
     * using arguments trait
     * @see \Thruway\Message\ArgumentsTrait
     */
    use ArgumentsTrait;

    /**
     *
     * @var mixed
     */
    private $options;

    /**
     *
     * @var string
     */
    private $topicName;

    /**
     *
     * @var mixed
     */
    private $requestId;

    /**
     * Constructor
     *
     * @param mixed $requestId
     * @param mixed $options
     * @param string $topicName
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    function __construct($requestId, $options, $topicName, $arguments = null, $argumentsKw = null)
    {
        parent::__construct();

        $this->setRequestId($requestId);

        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
        $this->options   = $options;
        $this->topicName = $topicName;
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_PUBLISH;
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

        $a = [$this->getRequestId(), $options, $this->getTopicName()];

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
     * @param string $topicName
     */
    public function setTopicName($topicName)
    {
        $this->topicName = $topicName;
    }

    /**
     * @return string
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
