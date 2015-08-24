<?php


namespace Voryx\ThruwayBundle\Tests;


use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Metadata\MetadataFactory;
use MyProject\Proxies\__CG__\stdClass;
use PhpCollection\Map;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Thruway\ClientSession;
use Voryx\ThruwayBundle\Annotation\Register;
use Voryx\ThruwayBundle\Mapping\URIClassMapping;
use Voryx\ThruwayBundle\Tests\Fixtures\Person;
use Voryx\ThruwayBundle\WampKernel;

class WampKernelTest extends \PHPUnit_Framework_TestCase
{

    /** @var  Container */
    private $container;

    /** @var  Serializer */
    private $serializer;

    /** @var  WampKernel */
    private $wampkernel;

    public function setup()
    {

        $this->container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

        //Create a WampKernel instance
        $reader           = $this->getMockBuilder('Doctrine\Common\Annotations\Reader')->getMock();
        $resourceMapper   = new \Voryx\ThruwayBundle\ResourceMapper($reader);
        $dispatcher       = new EventDispatcher();
        $namingStrategy   = new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy());
        $this->serializer = new Serializer(
            new MetadataFactory(new AnnotationDriver(new AnnotationReader())),
            new HandlerRegistry(),
            new UnserializeObjectConstructor(),
            new Map(['json' => new JsonSerializationVisitor($namingStrategy)]),
            new Map(['json' => new JsonDeserializationVisitor($namingStrategy)])
        );

        $this->wampkernel = new WampKernel($this->container, $this->serializer, $resourceMapper, $dispatcher, new NullLogger());

    }

    /**
     * @test
     */
    public function simple_rpc()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('simpleRPCTest');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);

        $args    = [3, "test", "test2"];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals($args, $result);

    }

    /**
     * @test
     */
    public function simple_rpc_with_default_value()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('simpleRPCTestWithDefault');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);

        $args    = null;
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals("test", $result);

    }

    /**
     * @test
     */
    public function rpc_test_with_type()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithType');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [["name" => "dave"]];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([new Person("dave")], $result);

    }


    /**
     * @test
     * @expectedException \Exception
     */
    public function rpc_test_with_type_bad_data()
    {
        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithType');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [new Person("badman")];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

    }


    /**
     * @test
     */
    public function rpc_test_with_multiple_types()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithMultipleTypes');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [["name" => "dave"], ["name" => "matt"], ["name" => "jim"]];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([new Person("dave"), new Person("matt"), new Person("jim")], $result);

    }


    /**
     * @test
     */
    public function rpc_test_with_mixed_types()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithMixedTypes');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [["name" => "dave"], "matt"];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([new Person("dave"), "matt"], $result);

    }

    /**
     * @test
     */
    public function rpc_test_with_mixed_types_and_default_value()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('RPCTestWithMixedTypesAndDefault');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);


        $args    = [["name" => "dave"], "matt"];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals([new Person("dave"), "matt", "test"], $result);

    }

    /**
     * @test
     */
    public function get_resource_mapper()
    {
        $resourceMapper = $this->wampkernel->getResourceMapper();

        $this->assertInstanceOf('Voryx\ThruwayBundle\ResourceMapper', $resourceMapper);
    }

}