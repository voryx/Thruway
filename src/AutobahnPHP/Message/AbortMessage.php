<?php

namespace AutobahnPHP\Message;

class AbortMessage extends Message {
    const MSG_CODE = Message::MSG_ABORT;

    private $details;

    private $responseURI;

    function __construct($details, $responseURI)
    {
        parent::__construct();

        $this->details = $details;
        $this->responseURI = $responseURI;
    }

    /**
     * @param array $details
     */
    public function setDetails(array $details)
    {
        $this->details = $details;
    }

    /**
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
    public function getMsgCode() { return static::MSG_CODE; }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return array($this->getDetails(), $this->getResponseURI());
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        return array(Wamp2Connection::STATE_ESTABLISHED);
    }


} 