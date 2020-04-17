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
        
        $this->assertArrayNotHasKey('some.proc', $dealer->getProcedures());

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

    public function testUnregisterNonExistentProcedure() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $unregisterMsg = new UnregisterMessage(1, 12345);
        $dealer->handleUnregisterMessage(new MessageEvent($calleeSession, $unregisterMsg));

        /** @var ErrorMessage $errorMsg */
        $errorMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errorMsg);
        $this->assertEquals('wamp.error.no_such_registration', $errorMsg->getErrorURI());
    }

    public function testRegisterHookForNonExistentProcedure() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true],
            'test.rpc'
        );
        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));

        $this->assertInstanceOf(ErrorMessage::class, $calleeTransport->getLastMessageSent());
    }

    public function testRegisterHookWithNonSingleInvoke() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(1, (object)[], 'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true, 'invoke' => \Thruway\Registration::ROUNDROBIN_REGISTRATION],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        /** @var ErrorMessage $errorMessage */
        $errorMessage = $hookTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errorMessage);
        $this->assertEquals('thruway.error.hook.failed', $errorMessage->getErrorURI());

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true,
                'invoke' => 'single',
                'thruway_multiregister' => true
            ],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $errorMessage = $hookTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errorMessage);
        $this->assertEquals('thruway.error.hook.failed', $errorMessage->getErrorURI());
    }

    public function testSimpleHook() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(1, (object)[], 'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        $hookRegistrationId = $hookTransport->getLastMessageSent()->getRegistrationId();
        
        $callerTransport = new DummyTransport();
        $callerSession = new Session($callerTransport);
        $callMsg = new CallMessage(1, (object)[], 'test.rpc');
        $dealer->handleCallMessage(new MessageEvent($callerSession, $callMsg));

        // the hook should get this invocation
        $this->assertInstanceOf(InvocationMessage::class, $hookTransport->getLastMessageSent());
        // the callee should have heard nothing new
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());

        $unregMsg = new UnregisterMessage(5, $hookRegistrationId);
        $dealer->handleUnregisterMessage(new MessageEvent($hookSession, $unregMsg));
        $this->assertInstanceOf(UnregisteredMessage::class, $hookTransport->getLastMessageSent());

        // next call should go to original caller
        $callMsg->setRequestId(10);
        $callMsg->setArguments(['New call']);
        $dealer->handleCallMessage(new MessageEvent($callerSession, $callMsg));
        /** @var InvocationMessage $invocationMsg */
        $invocationMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(InvocationMessage::class, $invocationMsg);
        $this->assertEquals(['New call'], $invocationMsg->getArguments());
    }

    public function testDoubleHook() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(1, (object)[], 'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        $hookRegistrationId = $hookTransport->getLastMessageSent()->getRegistrationId();

        $hook2Transport = new DummyTransport();
        $hook2Session = new Session($hook2Transport);
        $dealer->handleRegisterMessage(new MessageEvent($hook2Session, $hookRegisterMsg));
        /** @var RegisteredMessage $hook2RegisteredMsg */
        $hook2RegisteredMsg = $hook2Transport->getLastMessageSent();
        $this->assertInstanceOf(RegisteredMessage::class, $hook2RegisteredMsg);

        $callerTransport = new DummyTransport();
        $callerSession = new Session($callerTransport);
        $callMsg = new CallMessage(1, (object)[], 'test.rpc');
        $dealer->handleCallMessage(new MessageEvent($callerSession, $callMsg));

        // the second hook should get this invocation
        $this->assertInstanceOf(InvocationMessage::class, $hook2Transport->getLastMessageSent());
        // the first hook should have heard nothing new
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        // the callee should have heard nothing new
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());

        // unregister middle hook
        $unregMsg = new UnregisterMessage(5, $hookRegistrationId);
        $dealer->handleUnregisterMessage(new MessageEvent($hookSession, $unregMsg));
        $this->assertInstanceOf(UnregisteredMessage::class, $hookTransport->getLastMessageSent());

        // this call should go to hook2 still
        $callMsg->setRequestId(9);
        $callMsg->setArguments(['Call to hook2']);
        $dealer->handleCallMessage(new MessageEvent($callerSession, $callMsg));
        /** @var InvocationMessage $invocationMsg */
        $invocationMsg = $hook2Transport->getLastMessageSent();
        $this->assertInstanceOf(InvocationMessage::class, $invocationMsg);
        $this->assertEquals(['Call to hook2'], $invocationMsg->getArguments());

        // unregister hook2
        $unregMsg = new UnregisterMessage(6, $hook2RegisteredMsg->getRegistrationId());
        $dealer->handleUnregisterMessage(new MessageEvent($hook2Session, $unregMsg));
        $this->assertInstanceOf(UnregisteredMessage::class, $hook2Transport->getLastMessageSent());

        // next call should go to original caller
        $callMsg->setRequestId(10);
        $callMsg->setArguments(['New call']);
        $dealer->handleCallMessage(new MessageEvent($callerSession, $callMsg));
        /** @var InvocationMessage $invocationMsg */
        $invocationMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(InvocationMessage::class, $invocationMsg);
        $this->assertEquals(['New call'], $invocationMsg->getArguments());
    }

    public function testHookCallingHooked() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(1, (object)[], 'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        $hookRegistrationId = $hookTransport->getLastMessageSent()->getRegistrationId();

        $callMsg = new CallMessage(
            47,
            (object)['x_thruway_call_hooked' => (object)[ 'registration_id' => $hookRegistrationId ]],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var InvocationMessage $invocationMsg */
        $invocationMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(InvocationMessage::class, $invocationMsg);
        $this->assertEquals(['Calling hooked'], $invocationMsg->getArguments());
    }

    public function testHookCallingHookedBadOptions() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(1, (object)[], 'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        $hookRegistrationId = $hookTransport->getLastMessageSent()->getRegistrationId();

        $callMsg = new CallMessage(
            47,
            (object)['x_thruway_call_hooked' => 12345],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var ErrorMessage $errorMsg */
        $errorMsg = $hookTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errorMsg);
        $this->assertEquals(47, $errorMsg->getRequestId());
        $this->assertEquals('thruway.error.hook.invalid_call_options', $errorMsg->getErrorURI());
    }

    public function testHookCallingHookedWithCallerFrom() {
        $realm = new Realm('my_realm');
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);
        $calleeSession->setRealm($realm);

        $registerMsg = new RegisterMessage(
            1,
            (object)['disclose_caller'=>true],
            'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true, 'disclose_caller' => true],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $hookSession->setRealm($realm);
        $hookAuthDetails = new \Thruway\Authentication\AuthenticationDetails();
        $hookAuthDetails->setAuthId('admin');
        $hookAuthDetails->setAuthRoles(['admin', 'something']);
        $hookSession->setAuthenticated(true);
        $hookSession->setAuthenticationDetails($hookAuthDetails);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        $hookRegistrationId = $hookTransport->getLastMessageSent()->getRegistrationId();

        // setup a caller here to copy the session info of
        $callerTransport = new DummyTransport();
        $callerSession = new Session($callerTransport);
        $callerAuthDetails = new \Thruway\Authentication\AuthenticationDetails();
        $callerAuthDetails->setAuthId('the_user');
        $callerAuthDetails->setAuthRoles(['the_role']);
        $callerSession->setRealm($realm);
        $callerSession->setAuthenticationDetails($callerAuthDetails);

        $origCall = new CallMessage(9876, (object)[], 'test.rpc', ['some arg']);

        $dealer->handleCallMessage(new MessageEvent($callerSession, $origCall));
        // The hook should get the invocation
        /** @var InvocationMessage $origInvocationMessage */
        $origInvocationMessage = $hookTransport->getLastMessageSent();
        $this->assertInstanceOf(InvocationMessage::class, $origInvocationMessage);
        $this->assertObjectHasAttribute('caller', $origInvocationMessage->getDetails());
        $this->assertEquals($callerSession->getSessionId(), $origInvocationMessage->getDetails()->caller);

        $callMsg = new CallMessage(
            47,
            (object)[
                'x_thruway_call_hooked' => (object)[
                    'registration_id' => $hookRegistrationId,
                    'with_caller_from' => $origInvocationMessage->getRequestId()
                ]
            ],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var InvocationMessage $invocationMsg */
        $invocationMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(InvocationMessage::class, $invocationMsg);
        $this->assertEquals(['Calling hooked'], $invocationMsg->getArguments());
        $this->assertObjectHasAttribute('caller', $invocationMsg->getDetails());
        $this->assertEquals($callerSession->getSessionId(), $invocationMsg->getDetails()->caller);
        $this->assertEquals('the_user', $invocationMsg->getDetails()->authid);

        // call with non-existent invocation id
        $callMsg = new CallMessage(
            47,
            (object)[
                'x_thruway_call_hooked' => (object)[
                    'registration_id' => $hookRegistrationId,
                    'with_caller_from' => 1234
                ]
            ],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var ErrorMessage $errMsg */
        $errMsg = $hookTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errMsg);
        $this->assertEquals('thruway.error.hook.caller_from_invalid', $errMsg->getErrorURI());

        // call with invocation id from callee's invocation - I should only be able to use
        // and invocation that was sent to the hook's session
        $callMsg = new CallMessage(
            47,
            (object)[
                'x_thruway_call_hooked' => (object)[
                    'registration_id' => $hookRegistrationId,
                    'with_caller_from' => $invocationMsg->getRequestId()
                ]
            ],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var ErrorMessage $errMsg */
        $errMsg = $hookTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errMsg);
        $this->assertEquals('thruway.error.hook.caller_from_not_yours', $errMsg->getErrorURI());

        // call with invalid with_caller_from
        $callMsg = new CallMessage(
            47,
            (object)[
                'x_thruway_call_hooked' => (object)[
                    'registration_id' => $hookRegistrationId,
                    'with_caller_from' => (object)['x' => 'y']
                ]
            ],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var ErrorMessage $errMsg */
        $errMsg = $hookTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errMsg);
        $this->assertEquals('thruway.error.hook.caller_from_invalid', $errMsg->getErrorURI());

        // call with no registration_id
        $callMsg = new CallMessage(
            47,
            (object)[
                'x_thruway_call_hooked' => (object)[
                    'with_caller_from' => (object)['x' => 'y']
                ]
            ],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var ErrorMessage $errMsg */
        $errMsg = $hookTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errMsg);
        $this->assertEquals('thruway.error.hook.invalid_call_registration_id', $errMsg->getErrorURI());
    }

    public function testHookCallingUnownedHooked() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(1, (object)[], 'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        $hookRegistrationId = $hookTransport->getLastMessageSent()->getRegistrationId();

        $callMsg = new CallMessage(
            47,
            (object)['x_thruway_call_hooked' => (object)[ 'registration_id' => $hookRegistrationId ]],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($calleeSession, $callMsg));

        /** @var ErrorMessage $errorMsg */
        $errorMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errorMsg);
        $this->assertEquals(47, $errorMsg->getRequestId());
        $this->assertEquals('thruway.error.hook.not_yours', $errorMsg->getErrorURI());
    }

    public function testHookCallingUnhooked() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(1, (object)[], 'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());
        /** @var RegisteredMessage $originalRegisteredMsg */
        $originalRegisteredMsg = $calleeTransport->getLastMessageSent();

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        $hookRegistrationId = $hookTransport->getLastMessageSent()->getRegistrationId();

        $callMsg = new CallMessage(
            47,
            (object)['x_thruway_call_hooked' => (object)[
                    'registration_id' => $originalRegisteredMsg->getRegistrationId()
                ]
            ],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($calleeSession, $callMsg));

        /** @var ErrorMessage $errorMsg */
        $errorMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errorMsg);
        $this->assertEquals(47, $errorMsg->getRequestId());
        $this->assertEquals('thruway.error.hook.not_hooked', $errorMsg->getErrorURI());
    }

    public function testHookCallingNonexistentHooked() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(1, (object)[], 'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $calleeTransport->getLastMessageSent());
        /** @var RegisteredMessage $originalRegisteredMsg */
        $originalRegisteredMsg = $calleeTransport->getLastMessageSent();

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        $hookRegistrationId = $hookTransport->getLastMessageSent()->getRegistrationId();

        $callMsg = new CallMessage(
            47,
            (object)['x_thruway_call_hooked' => (object)[ 'registration_id' => 12345 ]],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($calleeSession, $callMsg));

        /** @var ErrorMessage $errorMsg */
        $errorMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(ErrorMessage::class, $errorMsg);
        $this->assertEquals(47, $errorMsg->getRequestId());
        $this->assertEquals('thruway.error.hook.bad_registration', $errorMsg->getErrorURI());
    }

    public function testRegisterRegularAfterHooked() {
        $dealer = new Dealer();
        $helloMessage = new HelloMessage('some.realm', (object)[]);

        $calleeTransport = new DummyTransport();
        $calleeSession = new Session($calleeTransport);
        // make sure this callee supports call cancellation
        $calleeSession->setHelloMessage($helloMessage);

        $registerMsg = new RegisterMessage(
            1,
            (object)[ 'invoke' => \Thruway\Registration::ROUNDROBIN_REGISTRATION ],
            'test.rpc');

        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg));
        /** @var RegisteredMessage $registeredMessage */
        $registeredMessage = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(RegisteredMessage::class, $registeredMessage);

        $hookRegisterMsg = new RegisterMessage(
            1,
            (object)['x_thruway_hook' => true],
            'test.rpc'
        );
        $hookTransport = new DummyTransport();
        $hookSession = new Session($hookTransport);
        $dealer->handleRegisterMessage(new MessageEvent($hookSession, $hookRegisterMsg));
        $this->assertInstanceOf(RegisteredMessage::class, $hookTransport->getLastMessageSent());
        $hookRegistrationId = $hookTransport->getLastMessageSent()->getRegistrationId();

        $callMsg = new CallMessage(
            47,
            (object)['x_thruway_call_hooked' => (object)[ 'registration_id' => $hookRegistrationId ]],
            'test.rpc',
            ['Calling hooked']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var InvocationMessage $invocationMsg */
        $invocationMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(InvocationMessage::class, $invocationMsg);
        $this->assertEquals(['Calling hooked'], $invocationMsg->getArguments());
        $this->assertEquals($registeredMessage->getRegistrationId(), $invocationMsg->getRegistrationId());

        // register another to see if it adds the registration to the correct place
        $registerMsg2 = new RegisterMessage(
            100,
            (object)[ 'invoke' => \Thruway\Registration::ROUNDROBIN_REGISTRATION ],
            'test.rpc');
        $dealer->handleRegisterMessage(new MessageEvent($calleeSession, $registerMsg2));
        /** @var RegisteredMessage $registeredMessage2 */
        $registeredMessage2 = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(RegisteredMessage::class, $registeredMessage2);

        // make another call - this should call the new one because of the round robin
        $callMsg = new CallMessage(
            48,
            (object)['x_thruway_call_hooked' => (object)[ 'registration_id' => $hookRegistrationId ]],
            'test.rpc',
            ['Calling hooked again']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var InvocationMessage $invocationMsg */
        $invocationMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(InvocationMessage::class, $invocationMsg);
        $this->assertEquals(['Calling hooked again'], $invocationMsg->getArguments());
        $this->assertEquals($registeredMessage2->getRegistrationId(), $invocationMsg->getRegistrationId());

        // make a 3rd call - this should invoke the first one because of the round robin
        $callMsg = new CallMessage(
            49,
            (object)['x_thruway_call_hooked' => (object)[ 'registration_id' => $hookRegistrationId ]],
            'test.rpc',
            ['Calling hooked - 3rd time']
        );
        $dealer->handleCallMessage(new MessageEvent($hookSession, $callMsg));

        /** @var InvocationMessage $invocationMsg */
        $invocationMsg = $calleeTransport->getLastMessageSent();
        $this->assertInstanceOf(InvocationMessage::class, $invocationMsg);
        $this->assertEquals(['Calling hooked - 3rd time'], $invocationMsg->getArguments());
        $this->assertEquals($registeredMessage->getRegistrationId(), $invocationMsg->getRegistrationId());
    }

//    public function testRegisteringOnHookedRPC() {
//        $this->markTestSkipped('Not implemented');
//    }
//
//    public function testHookPassingCredentialsOfCaller() {
//        $this->markTestSkipped('Not implemented');
//    }
}
