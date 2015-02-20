ThruwayBundle
===========

This a Symfony Bundle for [Thruway](https://github.com/voryx/Thruway), which is a php implementation of WAMP (Web Application Messaging Protocol).

Note:  This project is still undergoing a lot of changes, so the API will change.

### Quick Start with Composer


Download the Thruway Bundle (and dependancies)

      $ php composer.phar require "voryx/thruway":"dev-master"
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
    realm: 'myrealm'
    enable_logging: true
    #user_provider: 'in_memory_user_provider' 
    router:
        ip: '127.0.0.1'  # the ip that the router should start on
        port: '8080'  # public facing port
        #authentication: 'in_memory'
    locations:
        bundles: ["AppBundle"]
        files:
            - "Acme\\DemoBundle\\Controller\\DemoController"
      
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
    
    use Voryx\ThruwayBundle\Annotation\RPC;

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

Start up the the WAMP server

    $ php app/console thruway:client:start

By default, the server starts on ws://127.0.0.1:8080

### Javascript Client

For the client, you can use [AutobahnJS](https://github.com/tavendo/AutobahnJS) or any other WAMPv2 compatible client.

Here are some [examples](https://github.com/tavendo/AutobahnJS#show-me-some-code)
