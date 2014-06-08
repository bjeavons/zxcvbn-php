<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\SequenceMatch;

class SequenceTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = 'password';
        $matches = SequenceMatch::match($password);
        $this->assertEmpty($matches);

        $password = '12ab78UV';
        $matches = SequenceMatch::match($password);
        $this->assertEmpty($matches);

        $password = '12345';
        $matches = SequenceMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame($password, $matches[0]->token, "Token incorrect");
        $this->assertSame($password, $matches[0]->password, "Password incorrect");

        $password = 'ZYX';
        $matches = SequenceMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame($password, $matches[0]->token, "Token incorrect");
        $this->assertSame($password, $matches[0]->password, "Password incorrect");

        $password = 'pass123wordZYX';
        $matches = SequenceMatch::match($password);
        $this->assertCount(2, $matches);
        $this->assertSame('123', $matches[0]->token, "First match token incorrect");
        $this->assertSame('ZYX', $matches[1]->token, "Second match token incorrect");

        $password = 'wordZYX ';
        $matches = SequenceMatch::match($password);
        $this->assertEquals('ZYX', $matches[0]->token, "First match token incorrect");

        $password = 'XYZ123 ';
        $matches = SequenceMatch::match($password);
        $this->assertEquals('XYZ', $matches[0]->token, "First match token incorrect");
        $this->assertEquals('123', $matches[1]->token, "Second match token incorrect");

        $password = 'abc213456de';
        $matches = SequenceMatch::match($password);
        $this->assertEquals('abc', $matches[0]->token, "First match token incorrect");
        $this->assertEquals('3456', $matches[1]->token, "Second match token incorrect");
    }

    public function testEntropy()
    {
        $password = '12345';
        $matches = SequenceMatch::match($password);
        $this->assertEquals(log(5, 2) + 1, $matches[0]->getEntropy());
    }
}