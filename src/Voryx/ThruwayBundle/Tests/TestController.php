<?php


namespace Voryx\ThruwayBundle\Tests;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class TestController extends Controller
{

    public function echoRPC($firstArg)
    {
        return func_get_args();
    }
}