<?php

namespace Thruway\Message;

/**
 * abstract class message
 *
 * @package Thruway\Message
 */
abstract class Message implements \JsonSerializable
{

    /**
     * Message code
     * @const int
     */
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
     * Constructor
     */
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
     * Create message factory
     *
     * @param $data
     * @throws \Thruway\Message\MessageException
     * @return \Thruway\Message\Message
     */
    static public function createMessageFromArray($data)
    {
        if (!is_array($data) || $data !== array_values($data)) {
            throw new MessageException("Invalid WAMP message format");
        }

        switch ($data[0]) {
            case Message::MSG_ABORT:
                return new AbortMessage($data[1], $data[2]);
            case Message::MSG_HELLO:
                return new HelloMessage($data[1], $data[2]);
            case Message::MSG_SUBSCRIBE:
                return new SubscribeMessage($data[1], $data[2], $data[3]);
            case Message::MSG_UNSUBSCRIBE:
                return new UnsubscribeMessage($data[1], $data[2]);
            case Message::MSG_PUBLISH:
                return new PublishMessage($data[1], $data[2], $data[3], static::getArgs($data, 4), static::getArgs($data, 5));
            case Message::MSG_GOODBYE:
                return new GoodbyeMessage($data[1], $data[2]);
            case Message::MSG_AUTHENTICATE:
                return new AuthenticateMessage($data[1]);
            case Message::MSG_REGISTER:
                return new RegisterMessage($data[1], $data[2], $data[3]);
            case Message::MSG_UNREGISTER:
                return new UnregisterMessage($data[1], $data[2]);
            case Message::MSG_UNREGISTERED:
                return new UnregisteredMessage($data[1]);
            case Message::MSG_CALL:
                return new CallMessage($data[1], $data[2], $data[3], static::getArgs($data, 4), static::getArgs($data, 5));
            case Message::MSG_YIELD:
                return new YieldMessage($data[1], $data[2], static::getArgs($data, 3), static::getArgs($data, 4));
            case Message::MSG_WELCOME:
                return new WelcomeMessage($data[1], $data[2]);
            case Message::MSG_SUBSCRIBED:
                return new SubscribedMessage($data[1], $data[2]);
            case Message::MSG_EVENT:
                return new EventMessage($data[1], $data[2], $data[3], static::getArgs($data, 4), static::getArgs($data, 5));
            case Message::MSG_REGISTERED:
                return new RegisteredMessage($data[1], $data[2]);
            case Message::MSG_INVOCATION:
                return new InvocationMessage($data[1], $data[2], $data[3], static::getArgs($data, 4), static::getArgs($data, 5));
            case Message::MSG_RESULT:
                return new ResultMessage($data[1], $data[2], static::getArgs($data, 3), static::getArgs($data, 4));
            case Message::MSG_PUBLISHED:
                return new PublishedMessage($data[1], $data[2]);
            case Message::MSG_CHALLENGE:
                return new ChallengeMessage($data[1], $data[2]);
            case Message::MSG_ERROR:
                return new ErrorMessage($data[1], $data[2], $data[3], $data[4], static::getArgs($data, 5),
                    static::getArgs($data, 6));
            default:
                throw new MessageException("Unhandled message type: " . $data[0]);
        }
    }

    /**
     * This returns an array of all the parts of the message
     *
     * @return array
     */
    public function getMessageParts()
    {
        return array_merge([$this->getMsgCode()], $this->getAdditionalMsgFields());
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

    /**
     * Convert object to string
     *
     * @return string
     */
    function __toString()
    {
        return "[" . get_class($this) . "]";
    }

    /**
     * Check and convert empty array to \stdClass object
     *
     * @param mixed $a
     * @return \stdClass|mixed
     */
    public static function shouldBeDictionary($a)
    {
        if (is_array($a) && count($a) == 0) {
            $a = new \stdClass();
        }
        return $a;
    }

    /**
     * Check array is associative array
     * @param array $arr
     * @return boolean
     */
    public static function isAssoc($arr)
    {
        // if this is an empty stdClass (which we use as empty dictionaries)
        $arr = (array)$arr;

        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Get the args from the message data
     * @param $data
     * @param $position
     * @return null
     */
    protected static function getArgs($data, $position)
    {
        return isset($data[$position]) ? $data[$position] : null;
    }

}