<?php

namespace Voryx\ThruwayBundle;


use Thruway\Logging\Logger;
use Thruway\Message\ErrorMessage;
use Voryx\ThruwayBundle\Annotation\Register;
use Voryx\ThruwayBundle\Event\SessionEvent;

/**
 * Class TopicStateHandler
 * Registers Topic State Handlers
 * @package Voryx\ThruwayBundle
 */
class TopicStateHandler
{
    /**
     * @param SessionEvent $event
     */
    public function onOpen(SessionEvent $event)
    {

        /* @var $mapping \Voryx\ThruwayBundle\Mapping\URIClassMapping */
        foreach ($event->getResourceMappings() as $name => $mapping) {

            $annotation = $mapping->getAnnotation();
            if (!$annotation instanceof Register) {
                continue;
            }

            $topicStateHandler = $annotation->getTopicStateHandlerFor();
            if (!$topicStateHandler) {
                continue;
            }

            $session                   = $event->getSession();
            $registration              = new \stdClass();
            $registration->handler_uri = $annotation->getName();

            //Register Topic Handlers
            $registration->topic = $topicStateHandler;
            $session->call('add_state_handler', [$registration])->then(
                function ($res) use ($annotation){
                    Logger::info($this,
                        "Registered topic handler RPC: '{$annotation->getName()}'' for topic: '{$annotation->getTopicStateHandlerFor()}'"
                    );
                },
                function (ErrorMessage $error) use ($annotation) {
                    Logger::error($this,
                        "Unable to register topic handler RPC: '{$annotation->getName()}'' for topic: '{$annotation->getTopicStateHandlerFor()}'' Error: '{$error->getErrorURI()}''"
                    );
                });

        }

    }
}