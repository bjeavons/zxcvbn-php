<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\YearMatch;

class YearTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = 'password';
        $matches = YearMatch::match($password);
        $this->assertTrue(empty($matches), "Year does not match '$password'");

        $password = '1900';
        $matches = YearMatch::match($password);
        $this->assertEquals(1, count($matches), "Year does match '$password'");
        $this->assertEquals($password, $matches[0]->token, "Year matches password");
        $this->assertEquals($password, $matches[0]->password, "Match password matches password");
  }

    public function testEntropy()
    {
        $password = '1900';
        $matches = YearMatch::match($password);
        $this->assertEquals(log(119, 2), $matches[0]->getEntropy());
    }
}