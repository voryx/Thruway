<?php

namespace Thruway\Message;

use Thruway\Message\Traits\ArgumentsTrait;
use Thruway\Message\Traits\OptionsTrait;
use Thruway\Message\Traits\RequestTrait;

/**
 * Class Publish message
 * Sent by a Publisher to a Broker to publish an event.
 * <code>[PUBLISH, Request|id, Options|dict, Topic|uri]</code>
 * <code>[PUBLISH, Request|id, Options|dict, Topic|uri, Arguments|list]</code>
 * <code>[PUBLISH, Request|id, Options|dict, Topic|uri, Arguments|list, ArgumentsKw|dict]</code>
 *
 * @package Thruway\Message
 */
class PublishMessage extends Message implements ActionMessageInterface
{

    use RequestTrait;

    use OptionsTrait;

    /**
     * using arguments trait
     * @see \Thruway\Message\ArgumentsTrait
     */
    use ArgumentsTrait;

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
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    public function __construct($requestId, $options, $topicName, $arguments = null, $argumentsKw = null)
    {
        $this->setRequestId($requestId);
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
        $this->setOptions($options);
        $this->setTopicName($topicName);
    }

    /**
     * Get message code
     *
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
        return "publish";
    }


}
