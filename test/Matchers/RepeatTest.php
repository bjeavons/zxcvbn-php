<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\RepeatMatch;

class RepeatTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = '123';
        $matches = RepeatMatch::match($password);
        $this->assertEmpty($matches);

        $password = 'aa';
        $matches = RepeatMatch::match($password);
        $this->assertEmpty($matches);

        $password = 'aaa';
        $matches = RepeatMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertEquals('aaa', $matches[0]->token, "Token incorrect");
        $this->assertEquals('a', $matches[0]->repeatedChar, "Repeated character incorrect");

        $password = 'aaa1bbb';
        $matches = RepeatMatch::match($password);
        $this->assertCount(2, $matches);
        $this->assertEquals('bbb', $matches[1]->token, "Token incorrect");
        $this->assertEquals('b', $matches[1]->repeatedChar, "Repeated character incorrect");

        $password = 'taaaaaa';
        $matches = RepeatMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame('aaaaaa', $matches[0]->token, "Token incorrect");
        $this->assertSame('a', $matches[0]->repeatedChar, "Repeated character incorrect");
    }

    public function testEntropy()
    {
        $password = 'aaa';
        $matches = RepeatMatch::match($password);
        $this->assertEquals(log(26 * 3, 2), $matches[0]->getEntropy());

        $password = '..................';
        $matches = RepeatMatch::match($password);
        $this->assertEquals(log(33 * strlen($password), 2), $matches[0]->getEntropy());
    }

}