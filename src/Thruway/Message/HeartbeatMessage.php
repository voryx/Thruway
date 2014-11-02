<?php


namespace Thruway\Message;


class HeartbeatMessage extends Message {
    /**
     * @var int
     */
    private $incomingSeq;
    /**
     * @var int
     */
    private $outgoingSeq;
    /**
     * @var string
     */
    private $discard;

    /**
     * @param $discard
     * @param $incomingSeq
     * @param $outgoingSeq
     */
    function __construct($incomingSeq, $outgoingSeq, $discard = null)
    {
        $this->setDiscard($discard);
        $this->setIncomingSeq($incomingSeq);
        $this->setOutgoingSeq($outgoingSeq);
    }

    /**
     * Get message code
     *
     * @return int
     */
    public function getMsgCode()
    {
        return Message::MSG_HEARTBEAT;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        $a = [$this->getIncomingSeq(), $this->getOutgoingSeq()];

        if (is_string($this->getDiscard())) {
            array_push($a, $this->getDiscard());
        }

        return $a;
    }

    /**
     * @return string
     */
    public function getDiscard()
    {
        return $this->discard;
    }

    /**
     * @param string $discard
     */
    public function setDiscard($discard)
    {
        $this->discard = $discard;
    }

    /**
     * @return int
     */
    public function getIncomingSeq()
    {
        return $this->incomingSeq;
    }

    /**
     * @param int $incomingSeq
     */
    public function setIncomingSeq($incomingSeq)
    {
        $this->incomingSeq = $incomingSeq;
    }

    /**
     * @return int
     */
    public function getOutgoingSeq()
    {
        return $this->outgoingSeq;
    }

    /**
     * @param int $outgoingSeq
     */
    public function setOutgoingSeq($outgoingSeq)
    {
        $this->outgoingSeq = $outgoingSeq;
    }


}