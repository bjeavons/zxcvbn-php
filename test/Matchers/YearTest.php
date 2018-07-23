<?php

namespace ZxcvbnPhp\Test\Matchers;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matchers\YearMatch;

/**
 * @covers \ZxcvbnPhp\Matchers\YearMatch
 */
class YearTest extends TestCase
{
    public function testMatch()
    {
        $password = 'password';
        $matches = YearMatch::match($password);
        $this->assertEmpty($matches);

        $password = '1900';
        $matches = YearMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame($password, $matches[0]->token, 'Token incorrect');
        $this->assertSame($password, $matches[0]->password, 'Password incorrect');

        $password = 'password1900';
        $matches = YearMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame('1900', $matches[0]->token, 'Token incorrect');
    }

    public function testEntropy()
    {
        $password = '1900';
        $matches = YearMatch::match($password);
        $this->assertSame(log(119, 2), $matches[0]->getEntropy());
    }
}
