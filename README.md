AutobahnPHP
===========

AutobahnPHP is a Client and Server Library that aims to be compatible with the [Autobahn project](http://autobahn.ws/) 
and [WAMP2](http://wamp.ws/).

Basically, we like the WAMP idea, but we wanted to be able to work with it in PHP.

The project is brand new (as of June 12, 2014), so there is a lot of it that is in flux.

Please feel free to ask us what is going on or make suggests or fork it and make a pull request.


### Quick Start with Composer

Create a directory for the test project

    mkdir autobahnphp

Switch to the new directory

    cd autobahnphp

Download Composer [more info](https://getcomposer.org/doc/00-intro.md#downloading-the-composer-executable)

    curl -sS https://getcomposer.org/installer | php

Download AutobahnPHP and dependencies

    php composer.phar require "voryx/autobahnphp":"dev-master"

Start the WAMP server

    php vendor/voryx/autobahnphp/Examples/SimpleWsServer.php
    
AutobahnPHP is now running on 127.0.0.1 port 9000 

### PHP Client Example (alpha)

    <?php
    
    use AutobahnPHP\ClientSession;
    use AutobahnPHP\Connection;
    
    require __DIR__ . '/vendor/autoload.php';

    $onClose = function ($msg) {
        echo $msg;
    };
    
    $connection = new Connection(
        array(
            "realm" => 'realm1',
            "onClose" => $onClose,
            "url" => 'ws://127.0.0.1:9090',
        )
    );
    
    $connection->on('open', function (ClientSession $session) {
    
            // 1) subscribe to a topic
            $onevent = function ($args) {
                echo "Event {$args[0]}";
            };
            $session->subscribe('com.myapp.hello', $onevent);
    
            // 2) publish an event
            $session->publish('com.myapp.hello', array('Hello, world from PHP!!!'));
    
            // 3) register a procedure for remoting
            $add2 = function ($args) {
                return $args[0] + $args[1];
            };
            $session->register('com.myapp.add2', $add2);
    
            // 4) call a remote procedure
            $session->call('com.myapp.add2', array(2, 3))->then(
                function ($res) {
                    echo "Result: {$res}";
                }
            );
        }
    
    );
    
    $connection->open();

### Java Script Client

For the client, you can use [AutobahnJS](https://github.com/tavendo/AutobahnJS) or any other WAMPv2 compatible client.

Here are some [examples] (https://github.com/tavendo/AutobahnJS#show-me-some-code)

