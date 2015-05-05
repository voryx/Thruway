<?php


class WelcomeMessageTest extends \PHPUnit_Framework_TestCase
{

    public function testBrokerFeatures()
    {

        $brokerFeatures = null;
        $session        = new \Thruway\Session(new \Thruway\Transport\DummyTransport());
        $broker         = new \Thruway\Role\Broker();

        $session->dispatcher->addRealmSubscriber($broker);

        $welcomeMessage = new \Thruway\Message\WelcomeMessage($session->getSessionId(), new stdClass());

        $session->dispatcher->addListener('SendWelcomeMessageEvent', function (\Thruway\Event\MessageEvent $event) use (&$brokerFeatures, &$dealerFeatures) {
            $brokerFeatures = $event->message->getDetails()->roles->broker->features;

        });

        $session->dispatchMessage($welcomeMessage, "Send");

        $this->assertInstanceOf('stdClass', $brokerFeatures);
        $this->assertTrue($brokerFeatures->subscriber_blackwhite_listing);
        $this->assertTrue($brokerFeatures->publisher_exclusion);
        $this->assertTrue($brokerFeatures->subscriber_metaevents);

    }

    public function testDealerFeatures()
    {


        $dealerFeatures = null;

        $session = new \Thruway\Session(new \Thruway\Transport\DummyTransport());
        $dealer  = new \Thruway\Role\Dealer();

        $session->dispatcher->addRealmSubscriber($dealer);

        $welcomeMessage = new \Thruway\Message\WelcomeMessage($session->getSessionId(), new stdClass());

        $session->dispatcher->addListener('SendWelcomeMessageEvent', function (\Thruway\Event\MessageEvent $event) use (&$brokerFeatures, &$dealerFeatures) {
            $dealerFeatures = $event->message->getDetails()->roles->dealer->features;
        });

        $session->dispatchMessage($welcomeMessage, "Send");

        $this->assertInstanceOf('stdClass', $dealerFeatures);
        $this->assertTrue($dealerFeatures->caller_identification);
        $this->assertTrue($dealerFeatures->progressive_call_results);


    }

}