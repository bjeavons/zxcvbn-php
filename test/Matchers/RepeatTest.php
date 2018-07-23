<?php

namespace ZxcvbnPhp\Test\Matchers;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matchers\RepeatMatch;

/**
 * @covers \ZxcvbnPhp\Matchers\RepeatMatch
 */
class RepeatTest extends TestCase
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
        $this->assertSame('aaa', $matches[0]->token, 'Token incorrect');
        $this->assertSame('a', $matches[0]->repeatedChar, 'Repeated character incorrect');

        $password = 'aaa1bbb';
        $matches = RepeatMatch::match($password);
        $this->assertCount(2, $matches);
        $this->assertSame('bbb', $matches[1]->token, 'Token incorrect');
        $this->assertSame('b', $matches[1]->repeatedChar, 'Repeated character incorrect');

        $password = 'taaaaaa';
        $matches = RepeatMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame('aaaaaa', $matches[0]->token, 'Token incorrect');
        $this->assertSame('a', $matches[0]->repeatedChar, 'Repeated character incorrect');
    }

    public function testEntropy()
    {
        $password = 'aaa';
        $matches = RepeatMatch::match($password);
        $this->assertSame(log(26 * 3, 2), $matches[0]->getEntropy());

        $password = '..................';
        $matches = RepeatMatch::match($password);
        $this->assertSame(log(33 * strlen($password), 2), $matches[0]->getEntropy());
    }
}
