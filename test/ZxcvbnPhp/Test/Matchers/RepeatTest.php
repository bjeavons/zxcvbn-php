<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\Repeat;

class RepeatTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = '123';
        $matches = Repeat::match($password);
        $this->assertTrue(empty($matches), "Repeat does not match '$password'");

        $password = 'aa';
        $matches = Repeat::match($password);
        $this->assertTrue(empty($matches), "Repeat does not match '$password'");

        $password = 'aaa';
        $matches = Repeat::match($password);
        $this->assertEquals(1, count($matches), "Repeat does match '$password'");
        $this->assertEquals('aaa', $matches[0]->token, "Repeat matched 'aaa'");
        $this->assertEquals('a', $matches[0]->repeatedChar, "Repeat repeated char is 'a'");

        $password = 'aaa1bbb';
        $matches = Repeat::match($password);
        $this->assertEquals(2, count($matches), "Repeat does match '$password'");
        $this->assertEquals('bbb', $matches[1]->token, "Repeat matched 'bbb'");
        $this->assertEquals('b', $matches[1]->repeatedChar, "Repeat repeated char is 'b'");

        $password = 'taaaaaa';
        $matches = Repeat::match($password);
        $this->assertEquals(1, count($matches), "Repeat does match '$password'");
        $this->assertEquals('aaaaaa', $matches[0]->token, "Repeat matched token");
        $this->assertEquals('a', $matches[0]->repeatedChar, "Repeat repeated char is 'a'");
    }

    public function testEntropy()
    {
        $password = 'aaa';
        $matches = Repeat::match($password);
        $this->assertEquals(log(pow(26, 3), 2), $matches[0]->getEntropy());
    }

}