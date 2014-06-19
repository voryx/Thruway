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

        if ($msg instanceof ResultMessage):
            $this->processResult($session, $msg);
        elseif ($msg instanceof ErrorMessage):
            $this->processError($session, $msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;
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

    /**
     * @param ClientSession $session
     * @param ErrorMessage $msg
     */
    public function processError(ClientSession $session, ErrorMessage $msg)
    {
        if (isset($this->callRequests[$msg->getRequestId()])) {
            /* @var $futureResult Deferred */
            $futureResult = $this->callRequests[$msg->getRequestId()]['future_result'];
            $futureResult->reject($msg->getErrorURI());
            unset($this->callRequests[$msg->getRequestId()]);
        }
    }

    /**
     * @param Message $msg
     * @return mixed
     */
    public function handlesMessage(Message $msg)
    {

        $handledMsgCodes = array(
            Message::MSG_RESULT,
        );

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() == Message::MSG_CALL) {
            return true;
        } else {
            return false;
        }
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