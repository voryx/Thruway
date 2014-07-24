<?php

namespace Thruway\Authentication;

use React\Promise\Promise;
use Thruway\Peer\Client;


class AbstractAuthProviderClient extends Client {

  function __construct() {


    /*
     * Set authorization the realm. Defaults to "thruway.auth"
     *
     * This realm is only used between the Authentication Provider Client and the Authentication Manager Client on the server.
     *
     */
    parent::__construct('thruway.auth');

  }

  public function processHello(array $args) {

    return array(
      "CHALLENGE",
      array(
        "challenge" => new \stdClass(),
        "challenge_method" => $this->getMethodName()
      )
    );
  }

  public function onSessionStart($session, $transport) {
    $this->getCallee()->register(
      $session,
      "thruway.auth.{$this->getMethodName()}.onhello",
      array($this, 'processHello')
    )->then(
      function () use ($session) {
        $this->getCallee()->register(
          $session,
          "thruway.auth.{$this->getMethodName()}.onauthenticate",
          array($this, 'preProcessAuthenticate')
        )->then(
          function () use ($session) {
            $this->getCaller()->call(
              $session,
              'thruway.auth.registermethod',
              array(
                $this->getMethodName(),
                array(
                  "onhello" => "thruway.auth.{$this->getMethodName()}.onhello",
                  "onauthenticate" => "thruway.auth.{$this->getMethodName()}.onauthenticate"
                )
              )
            )->then(
              function ($args) {
                print_r($args);
              }
            );
          }
        );
      }
    );

  }

  public function start() {

  }

  public function preProcessAuthenticate(array $args) {

    $signature = isset($args['signature']) ? $args['signature'] : NULL;
    $extra = isset($args['extra']) ? $args['extra'] : NULL;

    if (!$signature) {
      return array("ERROR");
    }

    return $this->processAuthenticate($signature, $extra);

  }

  public function processAuthenticate($signature, $extra = NULL) {

    return array("SUCCESS");

  }
} 