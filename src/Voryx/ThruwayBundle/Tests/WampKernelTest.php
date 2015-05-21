<?php


namespace Voryx\ThruwayBundle\Tests;


use JMS\Serializer\SerializationContext;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Voryx\ThruwayBundle\Annotation\Register;
use Voryx\ThruwayBundle\Mapping\URIClassMapping;
use Voryx\ThruwayBundle\WampKernel;

class WampKernelTest extends \PHPUnit_Framework_TestCase
{


    public function testSimpleRPC()
    {

        $args    = [3, "test", "test2"];
        $argsKw  = new \stdClass();
        $details = new \stdClass();

        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

        $reader         = $this->getMockBuilder('Doctrine\Common\Annotations\Reader')->getMock();
        $resourceMapper = new \Voryx\ThruwayBundle\ResourceMapper($reader);
        $dispatcher     = new EventDispatcher();

        $expectedContext = SerializationContext::create();

        $serializer = $this->getMockBuilder('JMS\Serializer\SerializerInterface')->getMock();
        $serializer->expects($this->once())->method('serialize')
            ->with($args, 'json', $this->equalTo($expectedContext));

        $wampkernel = new WampKernel($container, $serializer, $resourceMapper, $dispatcher);

        $controller        = new TestController();
        $reflectController = new \ReflectionClass($controller);

        $container->set('some.controller.service', $controller);

        $reflectMethod = $reflectController->getMethod('echoRPC');
        $rpcAnnotation = new Register(["value" => "test.uri"]);

        $mapping = new URIClassMapping('some.controller.service', $reflectMethod, $rpcAnnotation);

        $wampkernel->handleRPC($args, $argsKw, $details, $mapping);

    }

}