<?php

use Thruway\Message\Message;
use Thruway\Session;

class DealerTest extends PHPUnit_Framework_TestCase {
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

        $registerMsg = new \Thruway\Message\RegisterMessage(Session::getUniqueId(), [], "test.procedure");

        $dealer->onMessage($calleeSession, $registerMsg);

        $callerSession = $this->getMockBuilder('\Thruway\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $callMsg = new \Thruway\Message\CallMessage(Session::getUniqueId(), [], "test.procedure");

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
            Session::getUniqueId(), ["thruway_multiregister" => true], "qpanmy_proc0");

        $dealer->onMessage($callee0Session, $registerMsg);

        $callMsg = new \Thruway\Message\CallMessage(Session::getUniqueId(), [], "qpanmy_proc0");

        $dealer->onMessage($callerSession, $callMsg);
        $callMsg->setRequestId(Session::getUniqueId());
        $dealer->onMessage($callerSession, $callMsg);

        $dealer->onMessage($callee0Session, new \Thruway\Message\YieldMessage(
            $invocationIDs[0], []
        ));

        $dealer->onMessage($callee0Session, new \Thruway\Message\YieldMessage(
            $invocationIDs[1], []
        ));

        // there are now zero calls on proc0
        $registerMsg = new \Thruway\Message\RegisterMessage(Session::getUniqueId(), [], "qpanmy_proc1");

        $callProc1Msg = new \Thruway\Message\CallMessage(Session::getUniqueId(), [], "qpanmy_proc1");

        $dealer->onMessage($callee0Session, $registerMsg);

        $dealer->onMessage($callerSession, $callProc1Msg);

        // this should cause congestion and queuing because it should be busy with proc1
        $callMsg->setRequestId(Session::getUniqueId());
        $dealer->onMessage($callerSession, $callMsg);

        // yield on proc1 - this should cause proc0 to process queue
        $dealer->onMessage($callee0Session, new \Thruway\Message\YieldMessage(
            $invocationIDs[2], []
        ));

    }
} 