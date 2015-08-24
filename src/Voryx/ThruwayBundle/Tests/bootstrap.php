<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

require __DIR__ . '/../../../../vendor/autoload.php';

call_user_func(function () {

    AnnotationRegistry::registerLoader('class_exists');
    AnnotationRegistry::registerFile(__DIR__ . '/../../../../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
});