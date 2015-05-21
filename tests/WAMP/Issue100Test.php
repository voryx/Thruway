<?php


class Issue100Test extends PHPUnit_Framework_TestCase {
    public function testUnsubscribeSendingUnsubThenError() {
        $router = new \Thruway\Peer\Router();


        $transport = new \Thruway\Transport\DummyTransport();
        $router->onOpen($transport);

        $helloMsg = new \Thruway\Message\HelloMessage('issueRealm', new stdClass());
        $router->onMessage($transport, $helloMsg);

        /** @var \Thruway\Message\WelcomeMessage $welcomeMsg */
        $welcomeMsg = $transport->getLastMessageSent();
        $this->assertInstanceOf('\Thruway\Message\WelcomeMessage', $welcomeMsg);

        $subscribeMsg = new \Thruway\Message\SubscribeMessage(123, new stdClass(), 'issue100');

        $router->onMessage($transport, $subscribeMsg);

        /** @var \Thruway\Message\SubscribedMessage $msg */
        $msg = $transport->getLastMessageSent();
        $this->assertInstanceOf('\Thruway\Message\SubscribedMessage', $msg);
        $this->assertEquals(123, $msg->getRequestId());
        $subId = $msg->getSubscriptionId();

        // subscribe to a second that is in a different sub group
        $subscribeMsg = new \Thruway\Message\SubscribeMessage(234, new stdClass(), 'issue100_again');

        $router->onMessage($transport, $subscribeMsg);

        /** @var \Thruway\Message\SubscribedMessage $msg */
        $msg = $transport->getLastMessageSent();
        $this->assertInstanceOf('\Thruway\Message\SubscribedMessage', $msg);
        $this->assertEquals(234, $msg->getRequestId());
        $subId2 = $msg->getSubscriptionId();

        // unsubscribe from the first
        $unsubscribeMsg = new \Thruway\Message\UnsubscribeMessage(456, $subId);
        $router->onMessage($transport, $unsubscribeMsg);

        /** @var \Thruway\Message\UnsubscribedMessage $unsubedMsg */
        $unsubedMsg = $transport->getLastMessageSent();
        $this->assertInstanceOf('\Thruway\Message\UnsubscribedMessage', $unsubedMsg);
        $this->assertEquals(456, $unsubedMsg->getRequestId());

        // try unsubscribing again
        $unsubscribeMsg = new \Thruway\Message\UnsubscribeMessage(789, $subId);
        $router->onMessage($transport, $unsubscribeMsg);

        /** @var \Thruway\Message\ErrorMessage $unsubedErrMsg */
        $unsubedErrMsg = $transport->getLastMessageSent();
        $this->assertInstanceOf('\Thruway\Message\ErrorMessage', $unsubedErrMsg);
        $this->assertEquals(\Thruway\Message\Message::MSG_UNSUBSCRIBE, $unsubedErrMsg->getErrorMsgCode());
        $this->assertEquals(789, $unsubedErrMsg->getRequestId());
    }
}