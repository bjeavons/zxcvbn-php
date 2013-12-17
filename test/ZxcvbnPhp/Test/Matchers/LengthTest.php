<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\LengthMatch;

class LengthTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = 'hello';
        $matches = LengthMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame($password, $matches[0]->token, "Token incorrect");
        $this->assertSame($password, $matches[0]->password, "Password incorrect");

        $password = 'helloworld';
        $matches = LengthMatch::match($password);
        $this->assertEmpty($matches);
    }

    public function testEntropy()
    {
        $password = 'hello';
        $matches = LengthMatch::match($password);
        $this->assertEquals(log(pow(10, 5), 2), $matches[0]->getEntropy());
    }
}