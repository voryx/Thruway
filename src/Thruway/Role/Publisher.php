<?php

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
 *
 * @package Thruway\Role
 */
class Publisher extends AbstractRole
{

    /**
     * @var array
     */
    private $publishRequests;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->publishRequests = [];
    }

    /**
     * Return supported features
     *
     * @return \stdClass
     */
    public function getFeatures() {
        $features = new \stdClass();

        $features->subscriber_blackwhite_listing = true;
        $features->publisher_exclusion = true;

        return $features;
    }

    /**
     * handle received message
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\Message $msg
     * @return void
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {
        if ($msg instanceof PublishedMessage):
            $this->processPublished($msg);
        elseif ($msg instanceof ErrorMessage):
            $this->processError($msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;
    }

    /**
     * process PublishedMesage
     *
     * @param \Thruway\Message\PublishedMessage $msg
     */
    protected function processPublished(PublishedMessage $msg)
    {
        if (isset($this->publishRequests[$msg->getRequestId()])) {
            /* @var $futureResult Deferred */
            $futureResult = $this->publishRequests[$msg->getRequestId()]["future_result"];
            $futureResult->resolve($msg->getPublicationId());
            unset($this->publishRequests[$msg->getRequestId()]);
        }
    }


    /**
     * process error
     *
     * @param \Thruway\Message\ErrorMessage $msg
     */
    protected function processError(ErrorMessage $msg)
    {
        if (isset($this->publishRequests[$msg->getRequestId()])) {
            /* @var $futureResult Deferred */
            $futureResult = $this->publishRequests[$msg->getRequestId()]["future_result"];
            $futureResult->reject($msg);
            unset($this->publishRequests[$msg->getRequestId()]);
        }
    }

    /**
     * Handle message
     *
     * @param \Thruway\Message\Message $msg
     * @return boolean
     */
    public function handlesMessage(Message $msg)
    {
        $handledMsgCodes = [
            Message::MSG_PUBLISHED,
        ];

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() == Message::MSG_PUBLISH) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * process publish
     *
     * @param \Thruway\ClientSession $session
     * @param string $topicName
     * @param mixed $arguments
     * @param mixed $argumentsKw
     * @param mixed $options
     * @return \React\Promise\Promise
     */
    public function publish(ClientSession $session, $topicName, $arguments, $argumentsKw, $options)
    {
        $options = (object)$options;

        $requestId = Session::getUniqueId();

        if (isset($options->acknowledge) && $options->acknowledge === true) {
            $futureResult                      = new Deferred();
            $this->publishRequests[$requestId] = ['future_result' => $futureResult];
        }


        $publishMsg = new PublishMessage($requestId, $options, $topicName, $arguments, $argumentsKw);

        $session->sendMessage($publishMsg);

        return isset($futureResult) ? $futureResult->promise() : false;
    }

} 