<?php

if (file_exists(__DIR__.'/../../../../autoload.php')) {
    require __DIR__.'/../../../../autoload.php';
} else {
    require __DIR__ . '/../../vendor/autoload.php';
}

class SimpleAuthProviderClient extends \Thruway\Peer\Client {

    private $methodName;

    function __construct()
    {
        parent::__construct('thruway.auth');

        $this->methodName = 'simplysimple';
    }

    public function onSessionStart($session, $transport) {
        $this->getCallee()->register($session, 'thruway.auth.' . $this->getMethodName() . '.onhello',
            array($this, 'processHello')
        )->then(function () use ($session) {
                $this->getCallee()->register($session, 'thruway.auth.' . $this->getMethodName() . '.onauthenticate',
                    array($this, 'processAuthenticate')
                )->then(function () use ($session) {
                        $this->getCaller()->call($session, 'thruway.auth.registermethod',
                            array(
                                $this->getMethodName(),
                                array(
                                    'onhello' => 'thruway.auth.' . $this->getMethodName() . '.onhello',
                                    'onauthenticate' => 'thruway.auth.' . $this->getMethodName() . '.onauthenticate'
                                )
                            )
                        )->then(function ($args) { print_r($args); });
                    });
            });

    }

    public function start() {

    }

    /**
     * @param string $methodName
     */
    public function setMethodName($methodName)
    {
        $this->methodName = $methodName;
    }

    /**
     * @return string
     */
    public function getMethodName()
    {
        return $this->methodName;
    }

    public function processHello(array $args) {
        return array("CHALLENGE", array(
            "challenge" => new stdClass(),
            "challenge_method" => $this->getMethodName()
        ));
    }

    public function processAuthenticate(array $args) {
        if (! isset($args['signature'])) return "ERROR";

        if ($args['signature'] == "letMeIn") {
            return array("SUCCESS");
        } else {
            return array("FAILURE");
        }
    }
}