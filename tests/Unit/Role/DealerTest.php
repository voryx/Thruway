<?php

use Thruway\Message\Message;
use Thruway\Session;

class DealerTest extends PHPUnit_Framework_TestCase {
    /** @var \Thruway\Message\HelloMessage */
    private $_helloMessage;

    public function setup() {
        $this->_helloMessage = new \Thruway\Message\HelloMessage("some_realm", (object)[
            "roles" => (object)[
                "callee" => (object)[
                    "features" => (object)[
                        "call_canceling" => true
                    ]
                ]
            ]
        ]);
    }

    public function testCallQueue() {
        $echo = function ($args) {
            return $args;
        };

        $dealer = new \Thruway\Role\Dealer();

        $calleeSession = $this->getMockBuilder('\Thruway\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $calleeSession->expects($this->exactly(2))
            ->method("sendMessage")
            ->withConsecutive(
                $this->isInstanceOf('\Thruway\Message\RegisteredMessage'),
                $this->isInstanceOf('\Thruway\Message\InvocationMessage')
                );

        $registerMsg = new \Thruway\Message\RegisterMessage(\Thruway\Common\Utils::getUniqueId(), [], "test.procedure");

        $dealer->onMessage($calleeSession, $registerMsg);

        $callerSession = $this->getMockBuilder('\Thruway\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $callMsg = new \Thruway\Message\CallMessage(\Thruway\Common\Utils::getUniqueId(), [], "test.procedure");

        $dealer->onMessage($callerSession, $callMsg);

        //$yieldMsg = new \Thruway\Message\YieldMessage()
    }

    public function testQueueProcessAfterNonMultiYield() {
        $seq = 0;

        // there will be two callees
        //// Mocking
        $realm = $this->getMockBuilder('\Thruway\Realm')
            ->setConstructorArgs(["theRealm"])
            ->setMethods(["publishMeta"]) // so we can intercept the meta event
            ->getMock();

        $sessionMockBuilder = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage']) // only override sendmessage
            ->disableOriginalConstructor()
        ;
        $callee0Session = $sessionMockBuilder
            ->getMock();
        $callee1Session = $sessionMockBuilder
            ->getMock();
        $callerSession = $sessionMockBuilder
            ->getMock();

        $callee0Session->setRealm($realm);
        $callee1Session->setRealm($realm);
        $callerSession->setRealm($realm);
        //// End of Mocking

        $realm->expects($this->exactly(2))
            ->method("publishMeta")
            ->with($this->equalTo('thruway.metaevent.procedure.congestion'),
                $this->equalTo([
                    ["name" => "qpanmy_proc0"]
                ])
            );

        $invocationIDs = [];

        $callee0Session->expects($this->exactly(6))
            ->method("sendMessage")
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\RegisteredMessage')],
                [$this->callback(function ($value) use (&$invocationIDs) {
                    $this->assertInstanceOf('\Thruway\Message\InvocationMessage', $value);

                    $invocationIDs[0] = $value->getRequestId();

                    return true;
                })],
                [$this->callback(function ($value) use (&$invocationIDs) {
                    $this->assertInstanceOf('\Thruway\Message\InvocationMessage', $value);

                    $invocationIDs[1] = $value->getRequestId();

                    return true;
                })],
                [$this->isInstanceOf('\Thruway\Message\RegisteredMessage')], // for proc1
                [$this->callback(function ($value) use (&$invocationIDs) {
                    $this->assertInstanceOf('\Thruway\Message\InvocationMessage', $value);

                    $invocationIDs[2] = $value->getRequestId();

                    return true;
                })],
                [$this->callback(function ($value) use (&$invocationIDs) { // invocation after yield
                    $this->assertInstanceOf('\Thruway\Message\InvocationMessage', $value);

                    $invocationIDs[3] = $value->getRequestId();

                    return true;
                })]
            );

        // create a dealer
        $dealer = new \Thruway\Role\Dealer();

        // register proc0 as multi
        $registerMsg = new \Thruway\Message\RegisterMessage(
            \Thruway\Common\Utils::getUniqueId(), ["thruway_multiregister" => true], "qpanmy_proc0");

        $dealer->onMessage($callee0Session, $registerMsg);

        $callMsg = new \Thruway\Message\CallMessage(\Thruway\Common\Utils::getUniqueId(), [], "qpanmy_proc0");

        $dealer->onMessage($callerSession, $callMsg);
        $callMsg->setRequestId(\Thruway\Common\Utils::getUniqueId());
        $dealer->onMessage($callerSession, $callMsg);

        $dealer->onMessage($callee0Session, new \Thruway\Message\YieldMessage(
            $invocationIDs[0], []
        ));

        $dealer->onMessage($callee0Session, new \Thruway\Message\YieldMessage(
            $invocationIDs[1], []
        ));

        // there are now zero calls on proc0
        $registerMsg = new \Thruway\Message\RegisterMessage(\Thruway\Common\Utils::getUniqueId(), [], "qpanmy_proc1");

        $callProc1Msg = new \Thruway\Message\CallMessage(\Thruway\Common\Utils::getUniqueId(), [], "qpanmy_proc1");

        $dealer->onMessage($callee0Session, $registerMsg);

        $dealer->onMessage($callerSession, $callProc1Msg);

        // this should cause congestion and queuing because it should be busy with proc1
        $callMsg->setRequestId(\Thruway\Common\Utils::getUniqueId());
        $dealer->onMessage($callerSession, $callMsg);

        // yield on proc1 - this should cause proc0 to process queue
        $dealer->onMessage($callee0Session, new \Thruway\Message\YieldMessage(
            $invocationIDs[2], []
        ));

    }

    public function testCallCancelNoOptions() {
        $sessionMockBuilder = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage']) // only override sendmessage
            ->disableOriginalConstructor();

        $eeSession = $sessionMockBuilder->getMock();
        $erSession = $sessionMockBuilder->getMock();
        $dealer = new \Thruway\Role\Dealer();

        $interruptMessage = null;

        $eeSession->expects($this->exactly(3))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\RegisteredMessage')],
                [$this->isInstanceOf('\Thruway\Message\InvocationMessage')],
                [$this->callback(function (\Thruway\Message\InterruptMessage $msg) use (&$interruptMessage) {
                    $interruptMessage = $msg;
                    return true;
                })]
            );

        $erSession->expects($this->exactly(2))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("wamp.error.not_supported", $msg->getErrorURI());
                    return true;
                })],
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("wamp.error.canceled", $msg->getErrorURI());
                    return true;
                })]
            );

        $registerMsg = new \Thruway\Message\RegisterMessage(12345, (object)[], 'test.procedure');
        $dealer->onMessage($eeSession, $registerMsg);

        $callMsg = new \Thruway\Message\CallMessage(1, (object)[], 'test.procedure');
        $dealer->onMessage($erSession, $callMsg);

        $this->assertEquals(1, $eeSession->getPendingCallCount());

        $cancelMsg = new \Thruway\Message\CancelMessage(1, (object)[]);
        $dealer->onMessage($erSession, $cancelMsg); // this will send back not supported message

        $eeSession->setHelloMessage($this->_helloMessage);

        $dealer->onMessage($erSession, $cancelMsg); // this gets interrupt sent through to ee

        $errorMsgFromEe = \Thruway\Message\ErrorMessage::createErrorMessageFromMessage($interruptMessage);
        $errorMsgFromEe->setErrorURI("wamp.error.canceled");
        $dealer->onMessage($eeSession, $errorMsgFromEe);

        /** @var \Thruway\Session $eeSession */
        $this->assertEquals(0, $eeSession->getPendingCallCount());
    }

    public function testCallCancelCallInQueue() {
        $sessionMockBuilder = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage']) // only override sendmessage
            ->disableOriginalConstructor();

        $realm = new \Thruway\Realm('realm1');

        /** @var Session $eeSession */
        $eeSession = $sessionMockBuilder->getMock();
        $eeSession->setRealm($realm);
        $eeSession->setHelloMessage($this->_helloMessage);
        $erSession = $sessionMockBuilder->getMock();
        $erSession->setRealm($realm);

        $erSession->expects($this->exactly(1))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("wamp.error.canceled", $msg->getErrorURI());
                    $this->assertObjectHasAttribute("_thruway_removed_from_queue", $msg->getDetails());
                    $this->assertTrue($msg->getDetails()->_thruway_removed_from_queue);
                    return true;
                })]
            );

        $dealer = new \Thruway\Role\Dealer();

        $registerMsg = new \Thruway\Message\RegisterMessage(1, (object)["thruway_multiregister" => true], "cancel_queued_call_procedure");
        $dealer->onMessage($eeSession, $registerMsg);

        $callMessage = new \Thruway\Message\CallMessage(2, (object)[], 'cancel_queued_call_procedure');
        $dealer->onMessage($erSession, $callMessage); // this should get through to callee
        $callMessage->setRequestId(3);
        $dealer->onMessage($erSession, $callMessage); // this should be in the queue now

        $cancelMessage = new \Thruway\Message\CancelMessage(3, (object)[]);

        $dealer->onMessage($erSession, $cancelMessage);

    }

    public function testCallCancelKillNowait() {
        $sessionMockBuilder = $this->getMockBuilder('\Thruway\Session')
            ->setMethods(['sendMessage']) // only override sendmessage
            ->disableOriginalConstructor();

        $eeSession = $sessionMockBuilder->getMock();
        $erSession = $sessionMockBuilder->getMock();
        $dealer = new \Thruway\Role\Dealer();

        $interruptMessage = null;

        $eeSession->expects($this->exactly(3))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->isInstanceOf('\Thruway\Message\RegisteredMessage')],
                [$this->isInstanceOf('\Thruway\Message\InvocationMessage')],
                [$this->callback(function (\Thruway\Message\InterruptMessage $msg) use (&$interruptMessage) {
                    $interruptMessage = $msg;
                    return true;
                })]
            );

        $erSession->expects($this->exactly(1))
            ->method('sendMessage')
            ->withConsecutive(
                [$this->callback(function (\Thruway\Message\ErrorMessage $msg) {
                    $this->assertEquals("wamp.error.canceled", $msg->getErrorURI());
                    return true;
                })]
            );

        $registerMsg = new \Thruway\Message\RegisterMessage(12345, (object)[], 'test.procedure');
        $dealer->onMessage($eeSession, $registerMsg);

        $callMsg = new \Thruway\Message\CallMessage(1, (object)[], 'test.procedure');
        $dealer->onMessage($erSession, $callMsg);

        $this->assertEquals(1, $eeSession->getPendingCallCount());

        $cancelMsg = new \Thruway\Message\CancelMessage(1, (object)["mode" => "killnowait"]);
        $eeSession->setHelloMessage($this->_helloMessage);

        $dealer->onMessage($erSession, $cancelMsg);

        $errorMsgFromEe = \Thruway\Message\ErrorMessage::createErrorMessageFromMessage($interruptMessage);
        $errorMsgFromEe->setErrorURI("wamp.error.canceled");
        $dealer->onMessage($eeSession, $errorMsgFromEe);

        /** @var \Thruway\Session $eeSession */
        $this->assertEquals(0, $eeSession->getPendingCallCount());
    }

    public function testDealerHandlesCancelInterruptAndError() {
        $dealer = new \Thruway\Role\Dealer();

        $cancelMessage = new \Thruway\Message\CancelMessage(1, (object)[]);
        $this->assertTrue($dealer->handlesMessage($cancelMessage));
        $interruptMessage = new \Thruway\Message\InterruptMessage(1, (object)[]);
        $this->assertTrue($dealer->handlesMessage($interruptMessage));
        $interruptErrorMessage = \Thruway\Message\ErrorMessage::createErrorMessageFromMessage($interruptMessage);
        $this->assertTrue($dealer->handlesMessage($interruptErrorMessage));
    }

//    Will need to wait until we know what we are supposed to do to do testing
//
//    public function testCallCancelNoResponseFromCallee() {
//        $this->assertTrue(false);
//    }
//
//    public function testCallCancelThenResultFromCallee() {
//        $this->assertTrue(false);
//    }
//
//    public function testCallCancelNonCancelErrorFromCallee() {
//        $this->assertTrue(false);
//    }
//    public function testCallCancelAfterCancel() {
//        $this->assertTrue(false);
//    }
} 