<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:02 PM
 */

namespace AutobahnPHP\Role;


use AutobahnPHP\AbstractSession;
use AutobahnPHP\ClientSession;
use AutobahnPHP\Message\CallMessage;
use AutobahnPHP\Message\ErrorMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\RegisterMessage;
use AutobahnPHP\Message\ResultMessage;
use AutobahnPHP\Session;
use React\Promise\Deferred;

/**
 * Class Caller
 * @package AutobahnPHP\Role
 */
class Caller extends AbstractRole
{
    /**
     * @var ClientSession
     */
    private $session;

    /**
     * @var array
     */
    private $callRequests;

    /**
     * @param $session
     */
    function __construct($session)
    {
        $this->session = $session;
        $this->callRequests = array();
    }

    /**
     * @param AbstractSession $session
     * @param Message $msg
     * @return mixed
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {
        switch ($msg) {
            case ($msg instanceof ResultMessage):
                $this->processResult($session, $msg);
                break;

            default:
                $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        }
    }

    /**
     * @param ClientSession $session
     * @param ResultMessage $msg
     */
    public function processResult(ClientSession $session, ResultMessage $msg)
    {
        if (isset($this->callRequests[$msg->getRequestId()])) {
            /* @var $futureResult Deferred */
            $futureResult = $this->callRequests[$msg->getRequestId()]['future_result'];
            $futureResult->resolve($msg->getArguments()[0]);
            unset($this->callRequests[$msg->getRequestId()]);
        }
    }

    }

    /**
     * @param Message $msg
     * @return mixed
     */
    public function handlesMessage(Message $msg)
    {
        $handledMessages = array(
            Message::MSG_RESULT,
        );

        return in_array($msg->getMsgCode(), $handledMessages);
    }

    /**
     * @param $procedureName
     * @param $arguments
     * @return \React\Promise\Promise
     */
    public function call($procedureName, $arguments)
    {
        $futureResult = new Deferred();

        $requestId = Session::getUniqueId();

        $this->callRequests[$requestId] = ["procedure_name" => $procedureName, "future_result" => $futureResult];

        $options = new \stdClass();

        $callMsg = new CallMessage($requestId, $options, $procedureName, $arguments);

        $this->session->sendMessage($callMsg);

        return $futureResult->promise();
    }
} 