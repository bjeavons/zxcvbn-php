<?php

namespace ZxcvbnPhp\Test\Matchers;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matchers\DigitMatch;

/**
 * @covers \ZxcvbnPhp\Matchers\DigitMatch
 */
class DigitTest extends TestCase
{
    public function testMatch()
    {
        $password = 'password';
        $matches = DigitMatch::match($password);
        $this->assertEmpty($matches);

        $password = '123';
        $matches = DigitMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame($password, $matches[0]->token, 'Token incorrect');
        $this->assertSame($password, $matches[0]->password, 'Password incorrect');

        $password = 'password123';
        $matches = DigitMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame(strpos($password, '1'), $matches[0]->begin, 'Match begin character incorrect');
        $this->assertSame(strlen($password) - 1, $matches[0]->end, 'Match end character incorrect');
        $this->assertSame(3, strlen($matches[0]->token), 'Token length incorrect');

        $password = '123 456546';
        $matches = DigitMatch::match($password);
        $this->assertCount(2, $matches);
    }

    public function testEntropy()
    {
        $password = '123';
        $matches = DigitMatch::match($password);
        $this->assertSame(log(10 ** 3, 2), $matches[0]->getEntropy());
    }
}
