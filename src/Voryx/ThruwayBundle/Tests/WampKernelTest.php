<?php


namespace Voryx\ThruwayBundle\Tests;


use JMS\Serializer\SerializationContext;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Thruway\ClientSession;
use Voryx\ThruwayBundle\Annotation\Register;
use Voryx\ThruwayBundle\Mapping\URIClassMapping;
use Voryx\ThruwayBundle\WampKernel;

class WampKernelTest extends \PHPUnit_Framework_TestCase
{

    /** @var  Container */
    private $container;

    /** @var  WampKernel */
    private $wampkernel;

    public function setup(){

        $this->container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

        //Create a WampKernel instance
        $reader         = $this->getMockBuilder('Doctrine\Common\Annotations\Reader')->getMock();
        $resourceMapper = new \Voryx\ThruwayBundle\ResourceMapper($reader);
        $dispatcher     = new EventDispatcher();
        $serializer     = $this->getMockBuilder('JMS\Serializer\SerializerInterface')->getMock();
        $this->wampkernel     = new WampKernel($this->container, $serializer, $resourceMapper, $dispatcher);

    }

    public function testSimpleRPC()
    {

        //Create the test controller and service
        $controller = new TestController();
        $this->container->set('some.controller.service', $controller);

        //Create a URI mapping
        $reflectController = new \ReflectionClass($controller);
        $reflectMethod     = $reflectController->getMethod('echoRPC');
        $rpcAnnotation     = new Register(["value" => "test.uri"]);
        $mapping           = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);

        $args    = [3, "test", "test2"];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $result = $this->wampkernel->handleRPC($args, $argsKw, $details, $mapping);

        $this->assertEquals($args, $result);

    }


    public function testGetResourceMapper(){
        $resourceMapper = $this->wampkernel->getResourceMapper();

        $this->assertInstanceOf('Voryx\ThruwayBundle\ResourceMapper', $resourceMapper);
    }

}