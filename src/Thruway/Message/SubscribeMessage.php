<?php

namespace Thruway\Message;

use Thruway\Message\Traits\OptionsMatchTypeTrait;
use Thruway\Message\Traits\OptionsTrait;
use Thruway\Message\Traits\RequestTrait;

/**
 * Class SubscribeMessage
 * Subscribe request sent by a Subscriber to a Broker to subscribe to a topic.
 * <code>[SUBSCRIBE, Request|id, Options|dict, Topic|uri]</code>
 *
 * @package Thruway\Message
 */
class SubscribeMessage extends Message implements ActionMessageInterface
{

    use RequestTrait;
    use OptionsTrait;
    use OptionsMatchTypeTrait;

    /**
     *
     * @var string
     */
    private $topicName;

    /**
     * Constructor
     *
     * @param int $requestId
     * @param \stdClass $options
     * @param string $topicName
     */
    public function __construct($requestId, $options, $topicName)
    {
        $this->setOptions($options);
        $this->setTopicName($topicName);
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
        return [$this->getRequestId(), (object)$this->getOptions(), $this->getTopicName()];
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

    /**
     * @param $options
     * @return string
     */
    static public function getMatchTypeFromOption($options)
    {
        if (is_object($options) && isset($options->match) && is_scalar($options->match)) {
            return $options->match;
        }

        return "exact";
    }

}
