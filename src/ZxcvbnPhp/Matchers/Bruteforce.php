<?php

namespace ZxcvbnPhp\Matchers;

class Bruteforce extends Match {

  /**
   * @var
   */
  protected $cardinality;

  /**
   * @param string $password
   * @return array
   */
  public static function match($password) {
    $match = new self($password, 0, strlen($password) - 1, $password);
    return array($match);
  }

  /**
   * @param $password
   * @param $begin
   * @param $end
   * @param $token
   */
  public function __construct($password, $begin, $end, $token) {
    parent::__construct($password, $begin, $end, $token);
    $this->pattern = 'bruteforce';
    $this->cardinality = null;
  }

  /**
   *
   */
  public function getEntropy() {
    if (is_null($this->entropy)) {
      $this->entropy = $this->log(pow($this->getCardinality(), strlen($this->token)));
    }
    return $this->entropy;
  }

  /**
   * @return int
   */
  public function getCardinality() {
    if (!is_null($this->cardinality)) {
      return $this->cardinality;
    }
    $lower = $upper = $digits = $symbols = $unicode = 0;

    // Use token instead of password to support bruteforce matches on sub-string
    // of password.
    $chars = str_split($this->token);
    foreach ($chars as $char) {
      $ord = ord($char);

      if ($ord >= 0x30 && $ord <= 0x39) {
        $digits = 10;
      }
      elseif ($ord >= 0x41 && $ord <= 0x5a) {
        $upper = 26;
      }
      elseif ($ord >= 0x61 && $ord <= 0x7a) {
        $lower = 26;
      }
      elseif ($ord <= 0x7f) {
        $symbols = 33;
      }
      else {
        $unicode = 100;
      }
    }
    $this->cardinality = $lower + $digits + $upper + $symbols + $unicode;
    return $this->cardinality;
  }

}