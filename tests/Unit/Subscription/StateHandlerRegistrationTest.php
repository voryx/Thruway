<?php

require_once __DIR__ . '/../../bootstrap.php';

class StateHandlerRegistrationTest extends PHPUnit_Framework_TestCase {
    public function testSubgroupStateHandlerCheck() {
        $session = $this->getMockBuilder('Thruway\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $options = (object)["match" => "prefix"];
        $stateHandlerRegistration = new Thruway\Subscription\StateHandlerRegistration(
            $session,
            "some.procedure",
            "a.b",
            $options,
            new Thruway\Subscription\PrefixMatcher()
        );

        $this->assertTrue($stateHandlerRegistration->handlesStateFor(
            new Thruway\Subscription\SubscriptionGroup(
                new Thruway\Subscription\PrefixMatcher(),
                "a.b.c",
                $options
            )
        ), "prefix SubscriptionGroup with longer URI is subgroup of short prefix state handler");

        $this->assertTrue($stateHandlerRegistration->handlesStateFor(
            new Thruway\Subscription\SubscriptionGroup(
                new Thruway\Subscription\PrefixMatcher(),
                "a.b.",
                $options
            )
        ), "prefix SubscriptionGroup with identical URI is subgroup of short prefix state handler");

        $this->assertTrue($stateHandlerRegistration->handlesStateFor(
            new Thruway\Subscription\SubscriptionGroup(
                new Thruway\Subscription\ExactMatcher(),
                "a.b.c",
                (object)[]
            )
        ), "exact SubscriptionGroup with longer URI is subgroup of short prefix state handler");

        $this->assertTrue($stateHandlerRegistration->handlesStateFor(
            new Thruway\Subscription\SubscriptionGroup(
                new Thruway\Subscription\ExactMatcher(),
                "a.b",
                (object)[]
            )
        ), "exact SubscriptionGroup with identical URI is subgroup of short prefix state handler");
    }
} 