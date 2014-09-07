<?php

namespace Thruway\Message;

abstract class Message implements \JsonSerializable
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

    function __construct()
    {
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
            throw new MessageException("Error decoding json \"${rawMsg}\"");
        }

        if (!is_array($json) || $json !== array_values($json)) {
            throw new MessageException("Invalid WAMP message format");
        }


        switch ($json[0]) {
            case Message::MSG_ABORT:
                return new AbortMessage($json[1], $json[2]);
            case Message::MSG_HELLO:
                return new HelloMessage($json[1], $json[2]);
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
            case Message::MSG_UNREGISTERED:
                return new UnregisteredMessage($json[1]);
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
            case Message::MSG_CHALLENGE:
                $extra = $args = isset($json[3]) ? $json[3] : [];
                return new ChallengeMessage($json[1], $json[2], $extra);
            case Message::MSG_ERROR:
                $args = isset($json[5]) ? $json[5] : null;
                $argsKw = isset($json[6]) ? $json[6] : null;
                return new ErrorMessage($json[1], $json[2], $json[3], $json[4], $args, $argsKw);

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

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return $this->getMessageParts();
    }


    public static function shouldBeDictionary($a) {
        if (is_array($a) && count($a) == 0) $a = new \stdClass();
        return $a;
    }

    public static function isAssoc($arr) {
        // if this is an empty stdClass (which we use as empty dictionaries)
        $arr = (array)$arr;

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}