<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:03 PM
 */

namespace AutobahnPHP\Role;


use AutobahnPHP\AbstractSession;
use AutobahnPHP\ClientSession;
use AutobahnPHP\Message\ErrorMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\PublishedMessage;
use AutobahnPHP\Message\PublishMessage;
use AutobahnPHP\Session;
use React\Promise\Deferred;

/**
 * Class Publisher
 * @package AutobahnPHP\Role
 */
class Publisher extends AbstractRole
{
    /**
     * @var ClientSession
     */
    private $session;

    /**
     * @var array
     */
    private $publishRequests;

    /**
     * @param $session
     */
    function __construct($session)
    {
        $this->session = $session;
        $this->publishRequests = array();
    }

    /**
     * @param \AutobahnPHP\AbstractSession $session
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
    public function publish($topicName, $arguments, $argumentsKw, $options)
    {
        $requestId = Session::getUniqueId();

        if (isset($options['acknowledge']) && $options['acknowledge'] === true) {
            $futureResult = new Deferred();
            $this->publishRequests[$requestId] = ['future_result' => $futureResult];
        }


        $publishMsg = new PublishMessage($requestId, $options, $topicName, $arguments, $argumentsKw);

        $this->session->sendMessage($publishMsg);

        return isset($futureResult) ? $futureResult->promise() : false;
    }

} 