<?php

namespace AutobahnPHP\Message;


class SubscribeMessage extends Message {
    const MSG_CODE = Message::MSG_SUBSCRIBE;

    private $options;
    private $topicName;

    function __construct($requestId, $options, $topicName)
    {
        parent::__construct();

        $this->options = $options;
        $this->topicName = $topicName;
        $this->setRequestId($requestId);
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
        return array($this->getRequestId(), $this->getOptions(), $this->getTopicName());
    }

    /**
     * @return array
     */
    public function getValidConnectionStates()
    {
        return array(Wamp2Connection::STATE_ESTABLISHED);
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $topicName
     */
    public function setTopicName($topicName)
    {
        $this->topicName = $topicName;
    }

    /**
     * @return mixed
     */
    public function getTopicName()
    {
        return $this->topicName;
    }


} 