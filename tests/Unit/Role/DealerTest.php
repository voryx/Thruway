<?php

use Thruway\Event\LeaveRealmEvent;
use Thruway\Event\MessageEvent;
use Thruway\Message\CallMessage;
use Thruway\Message\CancelMessage;
use Thruway\Message\ErrorMessage;
use Thruway\Message\HelloMessage;
use Thruway\Message\InterruptMessage;
use Thruway\Message\InvocationMessage;
use Thruway\Message\Message;
use Thruway\Message\RegisteredMessage;
use Thruway\Message\RegisterMessage;
use Thruway\Message\UnregisteredMessage;
use Thruway\Message\UnregisterMessage;
use Thruway\Realm;
use Thruway\Role\Dealer;
use Thruway\Session;
use Thruway\Transport\DummyTransport;

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

        $dealer->handleRegisterMessage(new \Thruway\Event\MessageEvent($calleeSession, $registerMsg));

        $callerSession = $this->getMockBuilder('\Thruway\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $callMsg = new \Thruway\Message\CallMessage(\Thruway\Common\Utils::getUniqueId(), [], "test.procedure");

        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($callerSession, $callMsg));
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

        $dealer->handleRegisterMessage(new \Thruway\Event\MessageEvent($callee0Session, $registerMsg));

        $callMsg = new \Thruway\Message\CallMessage(\Thruway\Common\Utils::getUniqueId(), [], "qpanmy_proc0");

        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($callerSession, $callMsg));
        $callMsg->setRequestId(\Thruway\Common\Utils::getUniqueId());
        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($callerSession, $callMsg));


        $yieldMsg = new \Thruway\Message\YieldMessage(
            $invocationIDs[0], []
        );
        $dealer->handleYieldMessage(new \Thruway\Event\MessageEvent($callee0Session, $yieldMsg));

        $yieldMsg = new \Thruway\Message\YieldMessage(
            $invocationIDs[1], []
        );
        $dealer->handleYieldMessage(new \Thruway\Event\MessageEvent($callee0Session, $yieldMsg));

        // there are now zero calls on proc0
        $registerMsg = new \Thruway\Message\RegisterMessage(\Thruway\Common\Utils::getUniqueId(), [], "qpanmy_proc1");

        $callProc1Msg = new \Thruway\Message\CallMessage(\Thruway\Common\Utils::getUniqueId(), [], "qpanmy_proc1");

        $dealer->handleRegisterMessage(new \Thruway\Event\MessageEvent($callee0Session, $registerMsg));

        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($callerSession, $callProc1Msg));

        // this should cause congestion and queuing because it should be busy with proc1
        $callMsg->setRequestId(\Thruway\Common\Utils::getUniqueId());
        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($callerSession, $callMsg));

        // yield on proc1 - this should cause proc0 to process queue
        $yieldMsg = new \Thruway\Message\YieldMessage(
            $invocationIDs[2], []
        );
        $dealer->handleYieldMessage(new \Thruway\Event\MessageEvent($callee0Session, $yieldMsg));
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
        $dealer->handleRegisterMessage(new \Thruway\Event\MessageEvent($eeSession, $registerMsg));

        $callMsg = new \Thruway\Message\CallMessage(1, (object)[], 'test.procedure');
        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($erSession, $callMsg));

        $this->assertEquals(1, $eeSession->getPendingCallCount());

        $cancelMsg = new \Thruway\Message\CancelMessage(1, (object)[]);
        $dealer->handleCancelMessage(new \Thruway\Event\MessageEvent($erSession, $cancelMsg));

        $eeSession->setHelloMessage($this->_helloMessage);

        $dealer->handleCancelMessage(new \Thruway\Event\MessageEvent($erSession, $cancelMsg));

        $errorMsgFromEe = \Thruway\Message\ErrorMessage::createErrorMessageFromMessage($interruptMessage);
        $errorMsgFromEe->setErrorURI("wamp.error.canceled");
        $dealer->handleErrorMessage(new \Thruway\Event\MessageEvent($eeSession, $errorMsgFromEe));

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
        $dealer->handleRegisterMessage(new \Thruway\Event\MessageEvent($eeSession, $registerMsg));

        $callMessage = new \Thruway\Message\CallMessage(2, (object)[], 'cancel_queued_call_procedure');
        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($erSession, $callMessage)); // this should get through to callee
        $callMessage->setRequestId(3);
        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($erSession, $callMessage)); // this should be in the queue now

        $cancelMessage = new \Thruway\Message\CancelMessage(3, (object)[]);

        $dealer->handleCancelMessage(new \Thruway\Event\MessageEvent($erSession, $cancelMessage));

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
        $dealer->handleRegisterMessage(new \Thruway\Event\MessageEvent($eeSession, $registerMsg));

        $callMsg = new \Thruway\Message\CallMessage(1, (object)[], 'test.procedure');
        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($erSession, $callMsg));

        $this->assertEquals(1, $eeSession->getPendingCallCount());

        $cancelMsg = new \Thruway\Message\CancelMessage(1, (object)["mode" => "killnowait"]);
        $eeSession->setHelloMessage($this->_helloMessage);

        $dealer->handleCancelMessage(new \Thruway\Event\MessageEvent($erSession, $cancelMsg));

        $errorMsgFromEe = \Thruway\Message\ErrorMessage::createErrorMessageFromMessage($interruptMessage);
        $errorMsgFromEe->setErrorURI("wamp.error.canceled");
        $dealer->handleErrorMessage(new \Thruway\Event\MessageEvent($eeSession, $errorMsgFromEe));

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

    public function testInvocationError() {
        $dealer = new \Thruway\Role\Dealer();

        $callerTransport = new \Thruway\Transport\DummyTransport();
        $callerSession = new Session($callerTransport);

        $calleeTransport = new \Thruway\Transport\DummyTransport();
        $calleeSession = new Session($calleeTransport);

        // register from callee
        $registerMsg = new \Thruway\Message\RegisterMessage(1, new stdClass(), 'test_proc_name');
        $dealer->handleRegisterMessage(new \Thruway\Event\MessageEvent($calleeSession, $registerMsg));

        $this->assertInstanceOf('\Thruway\Message\RegisteredMessage', $calleeTransport->getLastMessageSent());

        // call from one session
        $callRequestId = \Thruway\Common\Utils::getUniqueId();
        $callMsg = new \Thruway\Message\CallMessage($callRequestId, new stdClass(), 'test_proc_name');
        $dealer->handleCallMessage(new \Thruway\Event\MessageEvent($callerSession, $callMsg));

        $this->assertInstanceOf('\Thruway\Message\InvocationMessage', $calleeTransport->getLastMessageSent());

        $errorMsg = \Thruway\Message\ErrorMessage::createErrorMessageFromMessage($calleeTransport->getLastMessageSent(), 'the.error.uri');
        $dealer->handleErrorMessage(new \Thruway\Event\MessageEvent($calleeSession, $errorMsg));

        /** @var \Thruway\Message\ErrorMessage $returnedError */
        $returnedError = $callerTransport->getLastMessageSent();
        $this->assertInstanceOf('\Thruway\Message\ErrorMessage', $returnedError);
        $this->assertEquals(Message::MSG_CALL, $returnedError->getErrorMsgCode());
        $this->assertEquals($callRequestId, $returnedError->getErrorRequestId());
        $this->assertEquals('the.error.uri', $returnedError->getErrorURI());
    }

    public function testCallCancelWithNoCall()
    {
        $dealer = new Dealer();

        $callerTransport = new DummyTransport();
        $callerSession = new Session($callerTransport);

        $dealer->handleCancelMessage(new MessageEvent($callerSession, new CancelMessage(1234, (object)[])));
        $this->assertEquals(
            json_encode(new ErrorMessage(Message::MSG_CANCEL, 1234, (object)[], 'wamp.error.no_such_call')),
            json_encode($callerTransport->getLastMessageSent())
        );
    }
    
    public function testInterruptSentToSupportingClient()
    {
        $dealer = new Dealer();

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage(new HelloMessage(
            'some.realm',
            (object)[
                "roles" => (object)[
                    'callee' => (object)[
                        'features' => (object)[
                            'call_canceling' => true
                        ]
                    ]
                ]
            ]
        ));

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, new RegisterMessage(1234, (object)[], 'some.proc')));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());
        $registrationId = $calleeTransport->getLastMessageSent()->getRegistrationId();

        $callerTransport = new DummyTransport();
        $callerSession = new Session($callerTransport);
        $callMessage = new CallMessage(2345, (object)[], 'some.proc');

        $dealer->handleCallMessage(new MessageEvent($callerSession, $callMessage));

        $this->assertInstanceOf(InvocationMessage::class, $calleeTransport->getLastMessageSent());
        $invocationId = $calleeTransport->getLastMessageSent()->getRequestId();

        $dealer->handleCancelMessage(new MessageEvent($callerSession, new CancelMessage(2345, (object)[])));
        $this->assertInstanceOf(
            InterruptMessage::class,
            $calleeTransport->getLastMessageSent()
        );
        
        $this->assertEquals($invocationId, $calleeTransport->getLastMessageSent()->getRequestId());
    }

    public function testInterruptSentToNonSupportingClient()
    {
        $dealer = new Dealer();

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage(new HelloMessage(
            'some.realm',
            (object)[
                "roles" => (object)[
                    'callee' => (object)[
                        'features' => (object)[
                            'call_canceling' => false
                        ]
                    ]
                ]
            ]
        ));

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, new RegisterMessage(1234, (object)[], 'some.proc')));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());
        $registrationId = $calleeTransport->getLastMessageSent()->getRegistrationId();

        $callerTransport = new DummyTransport();
        $callerSession = new Session($callerTransport);
        $callMessage = new CallMessage(2345, (object)[], 'some.proc');

        $dealer->handleCallMessage(new MessageEvent($callerSession, $callMessage));

        $this->assertInstanceOf(InvocationMessage::class, $calleeTransport->getLastMessageSent());
        $invocationId = $calleeTransport->getLastMessageSent()->getRequestId();

        $dealer->handleCancelMessage(new MessageEvent($callerSession, new CancelMessage(2345, (object)[])));
        
        // callee should not have received anything
        $this->assertInstanceOf(
            InvocationMessage::class,
            $calleeTransport->getLastMessageSent()
        );

    }

    public function testCancelAfterUnregister()
    {
        $dealer = new Dealer();

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage(new HelloMessage(
            'some.realm',
            (object)[
                "roles" => (object)[
                    'callee' => (object)[
                        'features' => (object)[
                            'call_canceling' => true
                        ]
                    ]
                ]
            ]
        ));

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, new RegisterMessage(1234, (object)[], 'some.proc')));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());
        $registrationId = $calleeTransport->getLastMessageSent()->getRegistrationId();
        
        $callerTransport = new DummyTransport();
        $callerSession = new Session($callerTransport);
        $callMessage = new CallMessage(2345, (object)[], 'some.proc');

        $dealer->handleCallMessage(new MessageEvent($callerSession, $callMessage));

        $this->assertInstanceOf(InvocationMessage::class, $calleeTransport->getLastMessageSent());
        $invocationId = $calleeTransport->getLastMessageSent()->getRequestId();

        // unregister
        $dealer->handleUnregisterMessage(new MessageEvent(
            $calleeSession,
            new UnregisterMessage(3456, $registrationId)
        ));

        // this may need to be addressed as the behavior of unregistration is still
        // not completely defined if you have calls pending
        $this->assertInstanceOf(UnregisteredMessage::class, $calleeTransport->getLastMessageSent());
        
        //$this->assertEquals(0, count($dealer->getProcedures()));
        
        $this->assertEquals(0, count($dealer->getProcedures()['some.proc']->getRegistrations()));

        $dealer->handleCancelMessage(new MessageEvent($callerSession, new CancelMessage(2345, (object)[])));

        $call = $dealer->getCallByRequestId(2345);
        
        $this->assertNotNull($call);
        
        $dealer->handleLeaveRealm(new LeaveRealmEvent(new Realm('some.realm'), $callerSession));
        $dealer->handleLeaveRealm(new LeaveRealmEvent(new Realm('some.realm'), $calleeSession));

        $call = $dealer->getCallByRequestId(2345);

        $this->assertNotNull($call);

    }

    public function testCanceledProgressiveCallRemovesRouterReferences()
    {
        $dealer = new Dealer();

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage(new HelloMessage(
            'some.realm',
            (object)[
                "roles" => (object)[
                    'callee' => (object)[
                        'features' => (object)[
                            'call_canceling' => true
                        ]
                    ]
                ]
            ]
        ));

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, new RegisterMessage(1234, (object)[], 'some.proc')));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());
        $registrationId = $calleeTransport->getLastMessageSent()->getRegistrationId();

        $callerTransport = new DummyTransport();
        $callerSession = new Session($callerTransport);
        $callMessage = new CallMessage(2345, (object)['receive_progress' => true], 'some.proc');

        $dealer->handleCallMessage(new MessageEvent($callerSession, $callMessage));

        $this->assertInstanceOf(InvocationMessage::class, $calleeTransport->getLastMessageSent());
        /** @var InvocationMessage $invocationMessage */
        $invocationMessage = $calleeTransport->getLastMessageSent();
        $invocationId = $invocationMessage->getRequestId();

        $this->assertEquals(1, $dealer->getProcedures()['some.proc']->getRegistrations()[0]->getCurrentCallCount());

        $cancelMsg = new CancelMessage($callMessage->getRequestId(), (object)[]);

        $dealer->handleCancelMessage(new MessageEvent($callerSession, $cancelMsg));

        $this->assertInstanceOf(InterruptMessage::class, $calleeTransport->getLastMessageSent());
        /** @var InterruptMessage $interruptMessage */
        $interruptMessage = $calleeTransport->getLastMessageSent();
        $this->assertEquals($invocationMessage->getRequestId(), $interruptMessage->getRequestId());

        $this->assertEquals(0, $dealer->getProcedures()['some.proc']->getRegistrations()[0]->getCurrentCallCount());
    }
}
