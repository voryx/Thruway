<?php

namespace Thruway\Message;

/**
 * Class SubscribeMessage
 * Subscribe request sent by a Subscriber to a Broker to subscribe to a topic.
 * <code>[SUBSCRIBE, Request|id, Options|dict, Topic|uri]</code>
 *
 * @package Thruway\Message
 */
class SubscribeMessage extends Message implements ActionMessageInterface
{

    /**
     *
     * @var array
     */
    private $options;

    /**
     *
     * @var string
     */
    private $topicName;

    /**
     *
     * @var int
     */
    private $requestId;

    /**
     * Constructor
     *
     * @param int $requestId
     * @param array $options
     * @param string $topicName
     */
    public function __construct($requestId, $options, $topicName)
    {
        parent::__construct();

        $this->options   = $options;
        $this->topicName = $topicName;
        $this->setRequestId($requestId);
    }

    /**
     * Get message code
     * 
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
     * Set options
     * 
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Get options
     * 
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set topic name
     * 
     * @param string $topicName
     */
    public function setTopicName($topicName)
    {
        $this->topicName = $topicName;
    }

    /**
     * Get topic name
     * 
     * @return string
     */
    public function getTopicName()
    {
        return $this->topicName;
    }

    /**
     * Set request ID
     * 
     * @param int $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * Get request ID
     * 
     * @return int
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * This returns the Uri so that the authorization manager doesn't have to know
     * exactly the type of object to get the Uri
     *
     * @return mixed
     */
    public function getUri()
    {
        return $this->getTopicName();
    }

    /**
     * This returns the action name "publish", "subscribe", "register", "call"
     *
     * @return mixed
     */
    public function getActionName()
    {
        return "subscribe";
    }


}
