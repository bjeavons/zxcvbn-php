<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\Digit;

class DigitTest extends \PHPUnit_Framework_TestCase
{
  public function testMatch() {
    $password = 'password';
    $matches = Digit::match($password);
    $this->assertTrue(empty($matches), "Digit does not match '$password'");

    $password = '123';
    $matches = Digit::match($password);
    $this->assertEquals(1, count($matches), "Digit does match '$password'");
    $this->assertEquals($password, $matches[0]->token, "Token matches password");
    $this->assertEquals($password, $matches[0]->password, "Match password matches password");

    $password = 'password123';
    $matches = Digit::match($password);
    $this->assertEquals(1, count($matches), "1 match in '$password'");
    $this->assertEquals(strpos($password, '1'), $matches[0]->begin, "Beginning token position is correct");
    $this->assertEquals(strlen($password), $matches[0]->end, "End token position is correct");

    $password = '123 456546';
    $matches = Digit::match($password);
    $this->assertEquals(2, count($matches), "2 matches in '$password'");
  }

  public function testEntropy() {
    $password = '123';
    $matches = Digit::match($password);
    $this->assertEquals(log(pow(10, 3), 2), $matches[0]->getEntropy());
  }

}