<?php

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Matchers\Match;

class Sequence extends Match {

  /**
   * @var
   */

  public static function match($password) {

  }

  /**
   * @param $password
   * @param $begin
   * @param $end
   * @param $token
   */
  public function __construct($password, $begin, $end, $token, $char) {
    parent::__construct($password, $begin, $end, $token);
    $this->pattern = 'sequence';
  }

  public function entropy() {

  }
}