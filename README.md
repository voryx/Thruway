ThruwayBundle
===========

This a Symfony Bundle for [Thruway](https://github.com/voryx/Thruway), which is a php implementation of WAMP (Web Application Messaging Protocol).



### Quick Start with Composer


Download the Thruway Bundle

      $ php composer.phar require "ratchet/pawl":"dev-master"
      $ php composer.phar require "voryx/thruway":"dev-master"
      $ php composer.phar require "voryx/thruway-bundle":"dev-master"
      

Update AppKernel.php

```php
$bundles = array(
    // ...
    new JMS\SerializerBundle\JMSSerializerBundle(),
    new Voryx\ThruwayBundle\VoryxThruwayBundle(),
    // ...
);
```

### Configuration

```yml
#app/config/config.yml

voryx_thruway:
    realm: 'myrealm1'
    enable_manager: false
    enable_logging: true
    php_path: '/usr/local/bin/php'
    authentication: 'in_memory'
    resources:
      - "Acme\\DemoBundle\\Controller\\DemoController"
```
If you enable ```authentication: 'in_memory'```, you'll need to add a ```thruway``` to the security firewall.

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


### Usage


```php

    use Voryx\ThruwayBundle\Annotation\RPC;
    
    /**
     *
     * @RPC("com.example.add")
     *
     */
    public function addAction($num1, $num2)
    {
        return $num1 + $num2;
    }
```

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

It uses JMS Serializer, so it can serialize and deserialize Entities

```php
    
    use Voryx\ThruwayBundle\Annotation\RPC;

    /**
     *
     * @RPC("com.example.addrpc", serializerEnableMaxDepthChecks=true)
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


### Javascript Client

For the client, you can use [AutobahnJS](https://github.com/tavendo/AutobahnJS) or any other WAMPv2 compatible client.

Here are some [examples](https://github.com/tavendo/AutobahnJS#show-me-some-code)
