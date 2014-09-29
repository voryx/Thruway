<?php

namespace Thruway\Message;

/**
 * Class SubscribeMessage
 * Subscribe request sent by a Subscriber to a Broker to subscribe to a topic.
 * <code>[SUBSCRIBE, Request|id, Options|dict, Topic|uri]</code>
 * 
 * @package Thruway\Message
 */
class SubscribeMessage extends Message
{
    
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
     * Contructor
     * 
     * @param mixed $requestId
     * @param mixed $options
     * @param string $topicName
     */
    function __construct($requestId, $options, $topicName)
    {
        parent::__construct();

        $this->options   = $options;
        $this->topicName = $topicName;
        $this->setRequestId($requestId);
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_SUBSCRIBE;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->getRequestId(), $this->getOptions(), $this->getTopicName()];
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
     * @param int $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * @return int
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    
}
