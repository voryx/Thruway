<?php

namespace AutobahnPHP\Message;

abstract class Message
{

    const MSG_UNKNOWN = 0;
    const MSG_HELLO = 1;
    const MSG_WELCOME = 2;
    const MSG_ABORT = 3;
    const MSG_CHALLENGE = 4; // advanced
    const MSG_AUTHENTICATE = 5; // advanced
    const MSG_GOODBYE = 6;
    const MSG_HEARTBEAT = 7; // advanced
    const MSG_ERROR = 8;
    const MSG_PUBLISH = 16;
    const MSG_PUBLISHED = 17;
    const MSG_SUBSCRIBE = 32;
    const MSG_SUBSCRIBED = 33;
    const MSG_UNSUBSCRIBE = 34;
    const MSG_UNSUBSCRIBED = 35;
    const MSG_EVENT = 36;
    const MSG_CALL = 48;
    const MSG_CANCEL = 49; // advanced
    const MSG_RESULT = 50;
    const MSG_REGISTER = 64;
    const MSG_REGISTERED = 65;
    const MSG_UNREGISTER = 66;
    const MSG_UNREGISTERED = 67;
    const MSG_INVOCATION = 68;
    const MSG_INTERRUPT = 69; // advanced
    const MSG_YIELD = 70;

    /**
     * @var int
     */
    private $requestId;

    function __construct()
    {
        $this->requestId = static::MSG_UNKNOWN;
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
    abstract public function getMsgCode();

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    abstract public function getAdditionalMsgFields();

    /**
     * @return array
     */
    abstract public function getValidConnectionStates();

    /**
     * @param Wamp2Connection $conn
     * @return bool
     * @throws \UnexpectedValueException
     */
    public function isValidForConnection(Wamp2Connection $conn)
    {
        if (!is_array($this->getValidConnectionStates())) {
            throw new \UnexpectedValueException("getValidConnectionStates did not return an array");
        }

        foreach ($this->getValidConnectionStates() as $connState) {
            if ($connState == Wamp2Connection::STATE_ALL) {
                return true;
            }
            if ($conn->getConnectionState() == $connState) {
                return true;
            }
        }

        return false;
    }


    static public function createMessageFromRaw($rawMsg)
    {
        if (null === ($json = @json_decode($rawMsg, true))) {
            throw new MessageException("Error decoding json");
        }

        if (!is_array($json) || $json !== array_values($json)) {
            throw new MessageException("Invalid WAMP message format");
        }

        $authMethods = isset($json[2]['authmethods']) ? $json[2]['authmethods'] : array();

        switch ($json[0]) {
            case Message::MSG_HELLO:
                return new HelloMessage($json[1], $json[2], $authMethods);
            case Message::MSG_SUBSCRIBE:
                return new SubscribeMessage($json[1], $json[2], $json[3]);
            case Message::MSG_UNSUBSCRIBE:
                return new UnsubscribeMessage($json[1], $json[2]);
            case Message::MSG_PUBLISH:
                $args = null;
                if (count($json) >= 5) {
                    $args = $json[4];
                }

                $argsKw = null;
                if (count($json) >= 6) {
                    $argsKw = $json[5];
                }

                return new PublishMessage($json[1], $json[2], $json[3], $args, $argsKw);
            case Message::MSG_GOODBYE:
                return new GoodbyeMessage($json[1], $json[2]);
            case Message::MSG_AUTHENTICATE:
                return new AuthenticateMessage($json[1]);
            case Message::MSG_REGISTER:
                return new RegisterMessage($json[1], $json[2], $json[3]);
            case Message::MSG_UNREGISTER:
                return new UnregisterMessage($json[1], $json[2]);
            default:
                throw new MessageException("Unhandled message type: " . $json[0]);
        }
    }

    /**
     * This returns an array of all the parts of the message
     *
     * @return array
     */
    public function getMessageParts()
    {
        return array_merge(array($this->getMsgCode()), $this->getAdditionalMsgFields());
    }

    public function getSerializedMessage()
    {
        return json_encode($this->getMessageParts());
    }


}