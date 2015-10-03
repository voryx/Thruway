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
    use OptionsTrait {
        setOptions as traitSetOptions;
    }
    use ArgumentsTrait;

    /**
     *
     * @var string
     */
    private $topicName;


    /**
     * @var boolean
     */
    private $acknowledge;

    /**
     * @var boolean
     */
    private $exclude_me;

    /**
     * @var array
     */
    private $exclude;

    /**
     * @var array
     */
    private $eligible;

    /** @var array */
    private $eligible_authroles;

    /** @var array */
    private $eligible_authids;

    /**
     * @var int
     */
    private $publicationId;

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

        $a = [$this->getRequestId(), $this->getOptions(), $this->getTopicName()];

        return array_merge($a, $this->getArgumentsForSerialization());

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


    /**
     * @param $options
     */
    public function setOptions($options)
    {
        $this->traitSetOptions($options);

        //Get the options that have been cast to an object
        $options = $this->getOptions();

        $this->acknowledge        = isset($options->acknowledge) && $options->acknowledge === true ? true : false;
        $this->exclude_me         = isset($options->exclude_me) && $options->exclude_me === false ? false : true;
        $this->exclude            = isset($options->exclude) && is_array($options->exclude) ? $options->exclude : [];
        $this->eligible           = isset($options->eligible) && is_array($options->eligible) ? $options->eligible : null;
        $this->eligible_authroles = []; // default to no auth roles eligible

        if (isset($options->_thruway_eligible_authroles)) {
            if (is_array($options->_thruway_eligible_authroles)) {
                $this->eligible_authroles = $options->_thruway_eligible_authroles;
            }
        } else {
            $this->eligible_authroles = null; // null says every authrole is valid
        }

        $this->eligible_authids = []; // default to no authids eligible
        if (isset($options->_thruway_eligible_authids)) {
            if (is_array($options->_thruway_eligible_authids)) {
                $this->eligible_authids = $options->_thruway_eligible_authids;
            }
        } else {
            $this->eligible_authids = null; // null says every authid is valid
        }
    }

    /**
     * @return boolean
     */
    public function acknowledge()
    {
        return $this->acknowledge;
    }

    /**
     * @return boolean
     */
    public function excludeMe()
    {
        return $this->exclude_me;
    }

    /**
     * @return array
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * @return array | null
     */
    public function getEligible()
    {
        return $this->eligible;
    }

    /**
     * @param $sessionId
     * @return bool
     */
    public function isWhiteListed($sessionId)
    {
        return null === $this->getEligible() || in_array($sessionId, $this->getEligible());
    }

    /**
     * @param $sessionId
     * @return bool
     */
    public function isExcluded($sessionId)
    {
        return in_array($sessionId, $this->getExclude());
    }

    /**
     * @return int
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }

    /**
     * @param int $publicationId
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;
    }

    public function hasEligibleAuthrole($authroles)
    {
        if (!is_array($authroles)) {
            $authroles = [];
        }
        if ($this->eligible_authroles === null) {
            return true;
        }
        $intersect = array_intersect($authroles, $this->eligible_authroles);
        if (count($intersect) > 0) {
            return true;
        }

        return false;
    }

    public function hasEligibleAuthid($authid)
    {
        if ($this->eligible_authids === null) {
            return true;
        }
        if (in_array($authid, $this->eligible_authids)) {
            return true;
        }

        return false;
    }
}


