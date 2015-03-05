<?php

/**
 */
class InternalClient extends \Thruway\Module\RouterModuleClient
{

    public function onSessionStart($session, $transport)
    {
        $this->getCallee()->register($this->session, 'com.example.testcall', [$this, 'callTheTestCall']);

        $this->getCallee()->register($this->session, 'com.example.publish', [$this, 'callPublish']);

        $this->getCallee()->register(
            $this->session,
            'com.example.ping',
            [$this, 'callPing'],
            ['disclose_caller' => true]
        );

        $this->getCallee()->register(
            $this->session,
            'com.example.failure_from_rejected_promise',
            [$this, 'callFailureFromRejectedPromise']
        );

        $this->getCallee()->register(
            $this->session,
            'com.example.failure_from_exception',
            [$this, 'callFailureFromException']
        );

        $this->getCallee()->register(
            $this->session,
            'com.example.echo_with_argskw',
            [$this, 'callEchoWithKw']
        );

        $this->getCallee()->register(
            $this->session,
            'com.example.echo_with_argskw_with_promise',
            [$this, 'callEchoWithKwWithPromise']
        );

        //callWithProgressOption
        $this->getCallee()->register(
            $this->session,
            'com.example.progress_option',
            [$this, 'callWithProgressOption']
        );

        //callReturnSomeProgress
        $this->getCallee()->register(
            $this->session,
            'com.example.return_some_progress',
            [$this, 'callReturnSomeProgress']
        );

        $this->getCallee()->register(
            $this->session,
            'com.example.get_hello_details',
            [$this, 'callGetHelloDetails'],
            ['disclose_caller' => true]
        );
    }

    public function testCallWithArguments($res)
    {

    }

    public function callTheTestCall($res)
    {
        return [$res[0]];
    }

    public function callGetHelloDetails($args, $argsKw, $details)
    {
        $callingSession = $this->router->getSessionBySessionId($details->caller);

        $roleFeatures = $callingSession->getRoleFeatures();

        return [$roleFeatures];
    }

    public function callPublish($args)
    {
        $deferred = new \React\Promise\Deferred();

        $this->getPublisher()->publish($this->session, "com.example.publish", [$args[0]], ["key1" => "test1", "key2" => "test2"],
            ["acknowledge" => true])
            ->then(
                function () use ($deferred) {
                    $deferred->resolve('ok');
                },
                function ($error) use ($deferred) {
                    $deferred->reject("failed: {$error}");
                }
            );

        return $deferred->promise();
    }

    public function callPing($args, $kwArgs, $details)
    {
        if ($this->router === null) {
            throw new \Exception("Router must be set before calling ping.");
        }

        if (is_object($details) && isset($details->caller)) {
            $sessionIdToPing = $details->caller;

            $theSession = $this->router->getSessionBySessionId($sessionIdToPing);

            // ping returns a promise - we can just return it
            return $theSession->getTransport()->ping(2);
        }

        return ["no good"];
    }

    public function callFailureFromRejectedPromise()
    {
        $deferred = new \React\Promise\Deferred();
        //$deferred->reject("Call has failed :(");
        $this->getLoop()->addTimer(0, function () use ($deferred) {
            $deferred->reject("Call has failed :(");
        });

        return $deferred->promise();
    }

    public function callFailureFromException()
    {
        throw new \Exception('Exception Happened');
    }

    public function callEchoWithKw($args, $argsKw)
    {
        return new \Thruway\Result($args, $argsKw);
    }

    public function callEchoWithKwWithPromise($args, $argsKw)
    {
        $deferred = new \React\Promise\Deferred();

        $this->getLoop()->addTimer(0, function () use ($deferred, $args, $argsKw) {
            $deferred->resolve(new \Thruway\Result($args, $argsKw));
        });

        return $deferred->promise();
    }

    public function callWithProgressOption($args, $argsKw, $details)
    {
        if (is_object($details) && isset($details->receive_progress) && $details->receive_progress) {
            return "SUCCESS";
        } else {
            throw new \Exception("receive_progress option not set");
        }
    }

    public function callReturnSomeProgress($args, $argsKw, $details)
    {
        if (is_object($details) && isset($details->receive_progress) && $details->receive_progress) {
            $deferred = new \React\Promise\Deferred();

            $this->getLoop()->addTimer(1, function () use ($deferred) {
                $deferred->progress(1);
            });
            $this->getLoop()->addTimer(2, function () use ($deferred) {
                $deferred->progress(2);
            });
            $this->getLoop()->addTimer(3, function () use ($deferred) {
                $deferred->resolve("DONE");
            });

            return $deferred->promise();
        } else {
            throw new \Exception("receive_progress option not set");
        }
    }

}