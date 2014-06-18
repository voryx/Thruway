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
     * @param $rawMsg
     * @throws MessageException
     * @return Message
     */
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
                $args = isset($json[4]) ? $json[4] : null;
                $argsKw = isset($json[5]) ? $json[5] : null;

                return new PublishMessage($json[1], $json[2], $json[3], $args, $argsKw);
            case Message::MSG_GOODBYE:
                return new GoodbyeMessage($json[1], $json[2]);
            case Message::MSG_AUTHENTICATE:
                return new AuthenticateMessage($json[1]);
            case Message::MSG_REGISTER:
                return new RegisterMessage($json[1], $json[2], $json[3]);
            case Message::MSG_UNREGISTER:
                return new UnregisterMessage($json[1], $json[2]);
            case Message::MSG_CALL:
                $args = isset($json[4]) ? $json[4] : null;
                $argsKw = isset($json[5]) ? $json[5] : null;

                return new CallMessage($json[1], $json[2], $json[3], $args, $argsKw);
            case Message::MSG_YIELD:
                $args = isset($json[3]) ? $json[3] : null;
                $argsKw = isset($json[4]) ? $json[4] : null;

                return new YieldMessage($json[1], $json[2], $args, $argsKw);
            case Message::MSG_WELCOME:
                return new WelcomeMessage($json[1], $json[2]);
            case Message::MSG_SUBSCRIBED:
                return new SubscribedMessage($json[1], $json[2]);
            case Message::MSG_EVENT:
                $args = isset($json[4]) ? $json[4] : null;
                $argsKw = isset($json[5]) ? $json[5] : null;

                return new EventMessage($json[1], $json[2], $json[3], $args, $argsKw);
            case Message::MSG_REGISTERED:
                return new RegisteredMessage($json[1], $json[2]);
            case Message::MSG_INVOCATION:
                $args = isset($json[4]) ? $json[4] : null;
                $argsKw = isset($json[5]) ? $json[5] : null;

                return new InvocationMessage($json[1], $json[2], $json[3], $args, $argsKw);
            case Message::MSG_RESULT:
                $args = isset($json[3]) ? $json[3] : null;
                $argsKw = isset($json[4]) ? $json[4] : null;

                return new ResultMessage($json[1], $json[2], $args, $argsKw);
            case Message::MSG_PUBLISHED:
                return new PublishedMessage($json[1], $json[2]);
            case Message::MSG_ERROR:
                return new ErrorMessage($json[1], $json[2], $json[3], $json[4]);
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