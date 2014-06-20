<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:03 PM
 */

namespace Thruway\Role;


use Thruway\AbstractSession;
use Thruway\ClientSession;
use Thruway\Message\ErrorMessage;
use Thruway\Message\Message;
use Thruway\Message\PublishedMessage;
use Thruway\Message\PublishMessage;
use Thruway\Session;
use React\Promise\Deferred;

/**
 * Class Publisher
 * @package Thruway\Role
 */
class Publisher extends AbstractRole
{

    /**
     * @var array
     */
    private $publishRequests;

    /**
     * @param $session
     */
    function __construct()
    {
        $this->publishRequests = array();
    }

    /**
     * @param \Thruway\AbstractSession $session
     * @param Message $msg
     * @return mixed
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {
        if ($msg instanceof PublishedMessage):
            $this->processPublished($session, $msg);
        elseif ($msg instanceof ErrorMessage):
            $this->processError($session, $msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;
    }

    /**
     * @param ClientSession $session
     * @param PublishedMessage $msg
     */
    public function processPublished(ClientSession $session, PublishedMessage $msg)
    {
        if (isset($this->publishRequests[$msg->getRequestId()])) {
            /* @var $futureResult Deferred */
            $futureResult = $this->publishRequests[$msg->getRequestId()]["future_result"];
            $futureResult->resolve($msg->getPublicationId());
            unset($this->publishRequests[$msg->getRequestId()]);
        }
    }


    /**
     * @param ClientSession $session
     * @param ErrorMessage $msg
     */
    public function processError(ClientSession $session, ErrorMessage $msg)
    {
        if (isset($this->publishRequests[$msg->getRequestId()])) {
            /* @var $futureResult Deferred */
            $futureResult = $this->publishRequests[$msg->getRequestId()]["future_result"];
            $futureResult->reject($msg->getErrorMsgCode());
            unset($this->publishRequests[$msg->getRequestId()]);
        }
    }

    /**
     * @param Message $msg
     * @return mixed
     */
    public function handlesMessage(Message $msg)
    {
        $handledMsgCodes = array(
            Message::MSG_PUBLISHED,
        );

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() == Message::MSG_PUBLISH) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * @param $topicName
     * @param $arguments
     * @return \React\Promise\Promise
     */
    public function publish(ClientSession $session, $topicName, $arguments, $argumentsKw, $options)
    {
        $requestId = Session::getUniqueId();

        if (isset($options['acknowledge']) && $options['acknowledge'] === true) {
            $futureResult = new Deferred();
            $this->publishRequests[$requestId] = ['future_result' => $futureResult];
        }


        $publishMsg = new PublishMessage($requestId, $options, $topicName, $arguments, $argumentsKw);

        $session->sendMessage($publishMsg);

        return isset($futureResult) ? $futureResult->promise() : false;
    }

} 