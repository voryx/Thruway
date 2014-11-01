<?php

namespace Thruway\Message;

/**
 * Class AbortMessage
 * Sent by a Peer to abort the opening of a WAMP session. No response is expected.
 * <code>[ABORT, Details|dict, Reason|uri]</code>
 *
 * @package Thruway\Message
 */

class AbortMessage extends Message
{

    /**
     * Abort message details
     *
     * @var array
     */
    private $details;

    /**
     * Response URI
     *
     * @var mixed
     */
    private $responseURI;

    /**
     * Constructor
     *
     * @param array $details
     * @param mixed $responseURI
     */
    public function __construct($details, $responseURI)
    {
        parent::__construct();

        $this->setDetails($details);
        $this->setResponseURI($responseURI);
    }

    /**
     * Set abort message details
     *
     * @param array $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * Get abort message details
     *
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set response URI
     * 
     * @param mixed $responseURI
     */
    public function setResponseURI($responseURI)
    {
        $this->responseURI = $responseURI;
    }

    /**
     * get response URL
     * 
     * @return mixed
     */
    public function getResponseURI()
    {
        return $this->responseURI;
    }

    /**
     * Get message code
     * 
     * @return int
     */
    public function getMsgCode()
    {
        return Message::MSG_ABORT;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [(object)$this->getDetails(), $this->getResponseURI()];
    }

}
