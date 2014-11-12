<?php

namespace Voryx\ThruwayBundle\Serialization;


use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;

/**
 * Class StdClassHandler
 * @package Voryx\ThruwayBundle\Serialization
 */
class StdClassHandler implements SubscribingHandlerInterface
{

    /**
     * @return array
     */
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'type'      => 'stdClass',
                'format'    => 'json',
                'method'    => 'serializeStdClass',
            ]
        ];
    }


    /**
     * @param JsonSerializationVisitor $visitor
     * @param \stdClass $stdClass
     * @param array $type
     * @param Context $context
     * @return array
     */
    public function serializeStdClass(JsonSerializationVisitor $visitor, \stdClass $stdClass, array $type, Context $context)
    {
        return (array)$stdClass;
    }
}