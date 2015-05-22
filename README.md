ThruwayBundle
===========

This a Symfony Bundle for [Thruway](https://github.com/voryx/Thruway), which is a php implementation of WAMP (Web Application Messaging Protocol).

Note:  This project is still undergoing a lot of changes, so the API will change.

### Quick Start with Composer


Install the Thruway Bundle

      $ php composer.phar require "voryx/thruway-bundle":"dev-master"
      

Update AppKernel.php

```php
$bundles = array(
    // ...
    new JMS\SerializerBundle\JMSSerializerBundle(),
    new Voryx\ThruwayBundle\VoryxThruwayBundle($this),
    // ...
);
```

### Configuration

```yml
#app/config/config.yml

voryx_thruway:
    realm: 'realm1'
    url: 'ws://127.0.0.1:8081' #The url that the clients will use to connect to the router
    router:
        ip: '127.0.0.1'  # the ip that the router should start on
        port: '8080'  # public facing port.  If authentication is enabled, this port will be protected
        trusted_port: '8081' # Bypasses all authentication.  Use this for trusted clients.
#        authentication: 'in_memory'
    locations:
        bundles: ["AppBundle"]
#        files:
#            - "Acme\\DemoBundle\\Controller\\DemoController"
      
```
If you enable ```authentication: 'in_memory'```, you'll need to add a ```thruway``` to the security firewall and set the ``in_memory_user_provider``.

```yml
#app/config/security.yml

security: 
   firewalls:
        thruway:
            security: false	     
```

You can also tag services with `thruway.resource` and any annotation will get picked up

```xml
<service id="some.service" class="Acme\Bundle\SomeService">
    <tag name="thruway.resource"/>
</service>

```


### Authentication with FOSUserBundle via WampCRA

Change the Password Encoder (tricky on existing sites) to master wamp challenge

```yml
#app/config/security.yml

security:
    ...
    encoders:
        FOS\UserBundle\Model\UserInterface:
            algorithm:            pbkdf2
            hash_algorithm:       sha256
            encode_as_base64:     true
            iterations:           1000
            key_length:           32
```

set voryx_thruway.user_provider to "fos_user.user_manager"

```yml
#app/config/config.yml

voryx_thruway:
    user_provider: 'fos_user.user_manager' 
```

## Usage


#### Register RPC

```php
    use Voryx\ThruwayBundle\Annotation\Register;
    
    /**
     *
     * @Register("com.example.add")
     *
     */
    public function addAction($num1, $num2)
    {
        return $num1 + $num2;
    }
```

#### Call RPC

```php
    public function call($value)
    {
        $client = $this->container->get('thruway.client');
        $client->call("com.myapp.add", [2, 3])->then(
            function ($res) {
                echo $res[0];
            }
        );
    }
```

#### Subscribe	

```php	
     use Voryx\ThruwayBundle\Annotation\Subscribe;

    /**
     *
     * @Subscribe("com.example.subscribe")
     *
     */
    public function subscribe($value)
    {
        echo $value;
    }
```


#### Publish

```php
    public function publish($value)
    {
        $client = $this->container->get('thruway.client');
        $client->publish("com.myapp.hello_pubsub", [$value]);
    }
```

It uses JMS Serializer, so it can serialize and deserialize Entities

```php
    
    use Voryx\ThruwayBundle\Annotation\Register;

    /**
     *
     * @Register("com.example.addrpc", serializerEnableMaxDepthChecks=true)
     *
     */
    public function addAction(Post $post)
    {
        //Do something to $post

        return $post;
    }
```

#### Start the Thruway Process

You can start the default Thruway workers (router and client workers), without any additional configuration.

    $ nohup php app/console thruway:process start &
    
By default, the router starts on ws://127.0.0.1:8080
    
     
##Workers

The Thruway bundle will start up a separate process for the router and each defined worker.  If you haven't defined any workers, all of the annotated calls and subscriptions will be started within the `default` worker.

There are two main ways to break your application apart into multiple workers.

1.  Use the `worker` property on the `Register` and `Subscribe` annotations.  The following RPC will be added to the `posts` worker.
     
    ```PHP
      use Voryx\ThruwayBundle\Annotation\Register;
    
      /**
      * @Register("com.example.addrpc", serializerEnableMaxDepthChecks=true, worker="posts")
      */
      public function addAction(Post $post)
    ```
2.  Use the `@Worker` annotation on the class.  The following annotation will create a worker called `chat` that can have a max of 5 instances.
     
    ```PHP
      use Voryx\ThruwayBundle\Annotation\Worker;
    
      /**
      * @Worker("chat", maxProcesses="5")
      */
      class ChatController
    ```
 
If a worker is shut down with anything other than `SIGTERM`, it will automatically be restarted.
  
##More Commands

#####To see a list of running processes (workers)
     
    $ php app/console thruway:process status
    
#####Stop a process, i.e. `default`

    $ php app/console thruway:process stop default
    
#####Start a process, i.e. `default`
    
    $ php app/console thruway:process start default
    

### Javascript Client

For the client, you can use [AutobahnJS](https://github.com/tavendo/AutobahnJS) or any other WAMPv2 compatible client.

Here are some [examples](https://github.com/tavendo/AutobahnJS#show-me-some-code)
