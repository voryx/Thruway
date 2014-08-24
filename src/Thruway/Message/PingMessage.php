<?php

namespace Thruway\Message;


/**
 * This is the ping message type - I ping is sent from Client to Router
 * or Router to Client to test the connection end-to-end and to verify that
 * the other end is handling messages
 *
 * All parameters after request id can be omitted
 *
 * [PING, Request|id, Options|dict, Echo|list, Discard|string]
 * [PONG, PING.Request|id, Details|dict, Echo|list]
 *
 * Class PingMessage
 * @package Thruway\Message
 */
class PingMessage extends Message {
    /**
     * @var int
     */
    private $requestId;

    private $options;

    private $echo;

    private $discard;

    function __construct($requestId, $options = null, $echo = null, $discard = null)
    {
        $this->discard = $discard;
        $this->echo = $echo;
        $this->options = $options;
        $this->requestId = $requestId;
    }

    function getPong() {
        // we are sending no details
        $details = null;
        if ($this->getEcho() !== null) {
            // if there is an echo value, we need to send an empty details dict
            $details = new \stdClass();
        }

        return new PongMessage($this->getRequestId(), $details, $this->getEcho());
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
        return static::MSG_PING;
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

        $options = $this->getOptions();

        // if there is an echo, we need to send the options, so if they
        // are null - set them to an empty dictionary
        if ($options === null && $this->getEcho() !== null) $options = new \stdClass();

        if ($options !== null) {
            array_push($additional, $this->getOptions());
            if ($this->getEcho() !== null) {
                array_push($additional, $this->getEcho());
                if ($this->getDiscard() !== null) {
                    array_push($additional, $this->getDiscard());
                }
            }
        }

        return $additional;
    }

    /**
     * @param null $discard
     */
    public function setDiscard($discard)
    {
        $this->discard = $discard;
    }

    /**
     * @return null
     */
    public function getDiscard()
    {
        return $this->discard;
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
     * @param null $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return null
     */
    public function getOptions()
    {
        return $this->options;
    }



} 