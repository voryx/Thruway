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
     * Contructor
     * 
     * @param array $details
     * @param mixed $responseURI
     */
    function __construct($details, $responseURI)
    {
        parent::__construct();

        $this->details     = $details;
        $this->responseURI = $responseURI;
    }

    /**
     * Set abort message details
     * 
     * @param array $details
     */
    public function setDetails(array $details)
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
     * @param mixed $responseURI
     */
    public function setResponseURI($responseURI)
    {
        $this->responseURI = $responseURI;
    }

    /**
     * @return mixed
     */
    public function getResponseURI()
    {
        return $this->responseURI;
    }

    /**
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
        return [$this->getDetails(), $this->getResponseURI()];
    }

}
