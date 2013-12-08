<?php

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Matchers\MatchInterface;

abstract class Match implements MatchInterface {

  /**
   * @var
   */
  public $password;

  /**
   * @var
   */
  public $begin;

  /**
   * @var
   */
  public $end;

  /**
   * @var
   */
  public $token;

  /**
   * @var
   */
  public $pattern;

  /**
   * @var null
   */
  public $entropy;

  /**
   * @param $password
   * @param $begin
   * @param $end
   * @param $token
   */
  public function __construct($password, $begin, $end, $token) {
    $this->password = $password;
    $this->begin = $begin;
    $this->end = $end;
    $this->token = $token;
    $this->entropy = null;
  }

  /**
   * @param string $password
   * @return array
   *   Array of Match objects
   */
  public static function match($password) {}

  /**
   * @return float
   *   Entropy of the matched token in the password.
   */
  public function getEntropy() {}

  /**
   * Find all occorences of regular expression in a string.
   *
   * @param string $string
   *   String to search.
   * @param string $regex
   *   Regular expression with captures.
   * @return array
   *   Array of captures with named indexes.
   */
  public static function findAll($string, $regex) {
    $captures = array();
    preg_match_all($regex, $string, $matches, PREG_OFFSET_CAPTURE);

    if (isset($matches[1])) {
      foreach ($matches[1] as $capture) {
        list($token, $begin) = $capture;
        $captures[] = array(
          'begin' => $begin,
          'end' => $begin + strlen($token),
          'token' => $token,
        );
      };
    };

    return $captures;

  }

  protected function log($number) {
    return log($number, 2);
  }
}