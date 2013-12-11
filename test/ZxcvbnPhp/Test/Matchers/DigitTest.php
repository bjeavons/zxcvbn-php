<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\DigitMatch;

class DigitTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = 'password';
        $matches = DigitMatch::match($password);
        $this->assertEmpty($matches);

        $password = '123';
        $matches = DigitMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame($password, $matches[0]->token, "Token incorrect");
        $this->assertSame($password, $matches[0]->password, "Password incorrect");

        $password = 'password123';
        $matches = DigitMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertEquals(strpos($password, '1'), $matches[0]->begin);
        $this->assertEquals(strlen($password), $matches[0]->end);

        $password = '123 456546';
        $matches = DigitMatch::match($password);
        $this->assertCount(2, $matches);
  }

    public function testEntropy()
    {
        $password = '123';
        $matches = DigitMatch::match($password);
        $this->assertEquals(log(pow(10, 3), 2), $matches[0]->getEntropy());
    }
}