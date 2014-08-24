<?php

namespace Thruway\Message;


/**
 * This is the pong message type - pong is sent in response to a ping from Client to Router
 * or Router to Client to test the connection end-to-end and to verify that
 * the other end is handling messages
 *
 * All parameters after request id can be omitted
 *
 * [PING, Request|id, Options|dict, Echo|list, Discard|string]
 * [PONG, PING.Request|id, Details|dict, Echo|list]
 *
 * Class PongMessage
 * @package Thruway\Message
 */
class PongMessage extends Message {
    /**
     * @var int
     */
    private $requestId;

    private $details;

    private $echo;

    function __construct($requestId, $details = null, $echo = null)
    {
        $this->echo = $echo;
        $this->details = $details;
        $this->requestId = $requestId;
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

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_PONG;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        $additional = array($this->getRequestId());

        $details = $this->getDetails();

        // if there is an echo payload, we need to send details
        // so if there are no details also, we set it to an empty object
        if ($details === null && $this->getEcho() !== null) $details = new \stdClass();

        if ($details !== null) {
            array_push($additional, $this->getDetails());
            if ($this->getEcho() !== null) {
                array_push($additional, $this->getEcho());
            }
        }

        return $additional;
    }

    /**
     * @param null $echo
     */
    public function setEcho($echo)
    {
        $this->echo = $echo;
    }

    /**
     * @return null
     */
    public function getEcho()
    {
        return $this->echo;
    }

    /**
     * @param null $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * @return null
     */
    public function getDetails()
    {
        return $this->details;
    }
}