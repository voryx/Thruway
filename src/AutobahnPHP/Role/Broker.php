<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:02 PM
 */

namespace AutobahnPHP\Role;


use AutobahnPHP\Message\ErrorMessage;
use AutobahnPHP\Message\EventMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\PublishedMessage;
use AutobahnPHP\Message\PublishMessage;
use AutobahnPHP\Message\SubscribedMessage;
use AutobahnPHP\Message\SubscribeMessage;
use AutobahnPHP\Message\UnsubscribedMessage;
use AutobahnPHP\Message\UnsubscribeMessage;
use AutobahnPHP\Session;
use AutobahnPHP\TopicManager;

class Broker extends AbstractRole
{

    /**
     * @var TopicManager
     */
    private $topicManager;

    function __construct()
    {
        $this->topicManager = new TopicManager();
    }

    public function onMessage(Session $session, Message $msg)
    {
        if ($msg instanceof SubscribeMessage) {
            $topic = $this->topicManager->getTopic($msg->getTopicName());

            $topic->getSubscription($session);

            $session->addSubscription($topic);

            $subscribedMsg = new SubscribedMessage($msg->getRequestId(), $topic->getTopicName());

            $session->sendMessage($subscribedMsg);

//            $errMsg = ErrorMessage::createErrorMessageFromMessage($msg);
//            $errMsg->setErrorURI("wamp.error.no_such_subscription");
//            $conn->sendMessage($errMsg);
        } elseif ($msg instanceof UnsubscribeMessage) {
            // TODO: should create a separate subscription object
            // instead of using the topic as the subscription id
            $topic = $this->topicManager->getTopic($msg->getSubscriptionId());

            $topic->unsubscribe($session);

            $session->removeSubscription($topic);

            $session->sendMessage(new UnsubscribedMessage($msg->getRequestId()));
        } elseif ($msg instanceof PublishMessage) {
            echo "got publish\n";
            $topic = $this->topicManager->getTopic($msg->getTopicName());
            $topic->publish(
                $session,
                new EventMessage(
                    $topic->getTopicName(),
                    $msg->getRequestId(),
                    new \stdClass,
                    $msg->getArguments(),
                    $msg->getArgumentsKw()
                )
            );

            // see if they wanted confirmation
            $options = $msg->getOptions();
            if (is_array($options)) {
                if (isset($options['acknowledge']) && $options['acknowledge'] == true) {
                    $session->sendMessage(
                        new PublishedMessage($topic->getTopicName(), $msg->getRequestId())
                    );
                } else {
                    $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
                }

            } else {
                $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
            }

        }
    }

    public function handlesMessage(Message $msg)
    {
        $handledMsgCodes = array(
            Message::MSG_SUBSCRIBE,
            Message::MSG_UNSUBSCRIBE,
            Message::MSG_PUBLISH
        );

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } else {
            return false;
        }

    }


} 