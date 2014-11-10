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
    public function __construct()
    {
    }

    /**
     * Get message code
     * 
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
            case Message::MSG_ABORT: // [ABORT, Details|dict, Reason|uri]
                return new AbortMessage($data[1], $data[2]);
            case Message::MSG_HELLO: // [HELLO, Realm|uri, Details|dict]
                return new HelloMessage($data[1], $data[2]);
            case Message::MSG_SUBSCRIBE: // [SUBSCRIBE, Request|id, Options|dict, Topic|uri]
                return new SubscribeMessage($data[1], $data[2], $data[3]);
            case Message::MSG_UNSUBSCRIBE: // [UNSUBSCRIBE, Request|id, SUBSCRIBED.Subscription|id]
                return new UnsubscribeMessage($data[1], $data[2]);
            case Message::MSG_PUBLISH:
                // [PUBLISH, Request|id, Options|dict, Topic|uri]
                // [PUBLISH, Request|id, Options|dict, Topic|uri, Arguments|list]
                // [PUBLISH, Request|id, Options|dict, Topic|uri, Arguments|list, ArgumentsKw|dict]
                return new PublishMessage($data[1], $data[2], $data[3], static::getArgs($data, 4), static::getArgs($data, 5));
            case Message::MSG_GOODBYE: // [GOODBYE, Details|dict, Reason|uri]
                return new GoodbyeMessage($data[1], $data[2]);
            case Message::MSG_AUTHENTICATE: // [AUTHENTICATE, Signature|string, Extra|dict]
                return new AuthenticateMessage($data[1], $data[2]);
            case Message::MSG_REGISTER: // [REGISTER, Request|id, Options|dict, Procedure|uri]
                return new RegisterMessage($data[1], $data[2], $data[3]);
            case Message::MSG_UNREGISTER: // [UNREGISTER, Request|id, REGISTERED.Registration|id]
                return new UnregisterMessage($data[1], $data[2]);
            case Message::MSG_UNREGISTERED: // [UNREGISTERED, UNREGISTER.Request|id]
                return new UnregisteredMessage($data[1]);
            case Message::MSG_CALL:
                // [CALL, Request|id, Options|dict, Procedure|uri]
                // [CALL, Request|id, Options|dict, Procedure|uri, Arguments|list]
                // [CALL, Request|id, Options|dict, Procedure|uri, Arguments|list, ArgumentsKw|dict]
                return new CallMessage($data[1], $data[2], $data[3], static::getArgs($data, 4), static::getArgs($data, 5));
            case Message::MSG_YIELD:
                // [YIELD, INVOCATION.Request|id, Options|dict]
                // [YIELD, INVOCATION.Request|id, Options|dict, Arguments|list]
                // [YIELD, INVOCATION.Request|id, Options|dict, Arguments|list, ArgumentsKw|dict]
                return new YieldMessage($data[1], $data[2], static::getArgs($data, 3), static::getArgs($data, 4));
            case Message::MSG_WELCOME: // [WELCOME, Session|id, Details|dict]
                return new WelcomeMessage($data[1], $data[2]);
            case Message::MSG_SUBSCRIBED: // [SUBSCRIBED, SUBSCRIBE.Request|id, Subscription|id]
                return new SubscribedMessage($data[1], $data[2]);
            case Message::MSG_UNSUBSCRIBED: // [UNSUBSCRIBED, UNSUBSCRIBE.Request|id]
                return new UnsubscribedMessage($data[1]);
            case Message::MSG_EVENT:
                // [EVENT, SUBSCRIBED.Subscription|id, PUBLISHED.Publication|id, Details|dict]
                // [EVENT, SUBSCRIBED.Subscription|id, PUBLISHED.Publication|id, Details|dict, PUBLISH.Arguments|list]
                // [EVENT, SUBSCRIBED.Subscription|id, PUBLISHED.Publication|id, Details|dict, PUBLISH.Arguments|list, PUBLISH.ArgumentsKw|dict]
                return new EventMessage($data[1], $data[2], $data[3], static::getArgs($data, 4), static::getArgs($data, 5));
            case Message::MSG_REGISTERED: // [REGISTERED, REGISTER.Request|id, Registration|id]
                return new RegisteredMessage($data[1], $data[2]);
            case Message::MSG_INVOCATION:
                // [INVOCATION, Request|id, REGISTERED.Registration|id, Details|dict]
                // [INVOCATION, Request|id, REGISTERED.Registration|id, Details|dict, CALL.Arguments|list]
                // [INVOCATION, Request|id, REGISTERED.Registration|id, Details|dict, CALL.Arguments|list, CALL.ArgumentsKw|dict]
                return new InvocationMessage($data[1], $data[2], $data[3], static::getArgs($data, 4), static::getArgs($data, 5));
            case Message::MSG_RESULT:
                // [RESULT, CALL.Request|id, Details|dict]
                // [RESULT, CALL.Request|id, Details|dict, YIELD.Arguments|list]
                // [RESULT, CALL.Request|id, Details|dict, YIELD.Arguments|list, YIELD.ArgumentsKw|dict]
                return new ResultMessage($data[1], $data[2], static::getArgs($data, 3), static::getArgs($data, 4));
            case Message::MSG_PUBLISHED: // [PUBLISHED, PUBLISH.Request|id, Publication|id]
                return new PublishedMessage($data[1], $data[2]);
            case Message::MSG_CHALLENGE: // [CHALLENGE, AuthMethod|string, Extra|dict]
                return new ChallengeMessage($data[1], $data[2]);
            case Message::MSG_HEARTBEAT:
                // [HEARTBEAT, IncomingSeq|integer, OutgoingSeq|integer
                // [HEARTBEAT, IncomingSeq|integer, OutgoingSeq|integer, Discard|string]
                $discard = null;
                if (isset($data[3])) $discard = $data[3];

                return new HeartbeatMessage($data[1], $data[2], $discard);
            case Message::MSG_CANCEL: // [CANCEL, CALL.Request|id, Options|dict]
                return new CancelMessage($data[1], $data[2]);
            case Message::MSG_INTERRUPT: // [INTERRUPT, INVOCATION.Request|id, Options|dict]
                return new InterruptMessage($data[1], $data[2]);
            case Message::MSG_ERROR:
                // [ERROR, REQUEST.Type|int, REQUEST.Request|id, Details|dict, Error|uri]
                // [ERROR, REQUEST.Type|int, REQUEST.Request|id, Details|dict, Error|uri, Arguments|list]
                // [ERROR, REQUEST.Type|int, REQUEST.Request|id, Details|dict, Error|uri, Arguments|list, ArgumentsKw|dict]
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
    public function __toString()
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
        if (is_array($a)) {
            $a = (object)$a;
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
     * 
     * @param array $data
     * @param int $position
     * @return mixed|null
     */
    protected static function getArgs($data, $position)
    {
        return isset($data[$position]) ? $data[$position] : null;
    }

}