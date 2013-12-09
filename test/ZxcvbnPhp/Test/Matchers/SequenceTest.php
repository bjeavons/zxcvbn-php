<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\SequenceMatch;

class SequenceTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = 'password';
        $matches = SequenceMatch::match($password);
        $this->assertTrue(empty($matches), "Sequence does not match '$password'");

        $password = '12ab78UV';
        $matches = SequenceMatch::match($password);
        $this->assertTrue(empty($matches), "Sequence does not match '$password'");

        $password = '12345';
        $matches = SequenceMatch::match($password);
        $this->assertEquals(1, count($matches), "Sequence does match '$password'");
        $this->assertEquals($password, $matches[0]->token, "Token matches password");
        $this->assertEquals($password, $matches[0]->password, "Match password matches password");

        $password = 'ZYX';
        $matches = SequenceMatch::match($password);
        $this->assertEquals(1, count($matches), "Sequence does match '$password'");
        $this->assertEquals($password, $matches[0]->token, "Token matches password");
        $this->assertEquals($password, $matches[0]->password, "Match password matches password");

        $password = 'pass123wordZYX';
        $matches = SequenceMatch::match($password);
        $this->assertEquals(2, count($matches), "Found 2 sequences in '$password'");
        $this->assertEquals('123', $matches[0]->token, "First token is correct");
        $this->assertEquals('ZYX', $matches[1]->token, "Second token is correct");

        $password = 'wordZYX ';
        $matches = SequenceMatch::match($password);
        $this->assertEquals('ZYX', $matches[0]->token, "First token is correct");
    }

    public function testEntropy()
    {
        $password = '12345';
        $matches = SequenceMatch::match($password);
        $this->assertEquals(log(5, 2) + 1, $matches[0]->getEntropy());
    }
}