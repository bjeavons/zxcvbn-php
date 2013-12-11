<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\L33tMatch;

class L33tTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        // Test non-translated dictionary word.
        $password = 'pass';
        $matches = L33tMatch::match($password);
        $this->assertEmpty($matches);

        $password = 'p4ss';
        $matches = L33tMatch::match($password);
        $this->assertCount(5, $matches);

        $password = 'p4ssw0rd';
        $matches = L33tMatch::match($password);
        $this->assertCount(11, $matches);

        // Test translated characters that are not a dictionary word.
        $password = '76+(';
        $matches = L33tMatch::match($password);
        $this->assertEmpty($matches);
    }

    public function testEntropy()
    {
        $password = 'p4ss';
        $matches = L33tMatch::match($password);
        // 'pass' has a rank of 35 and l33t entropy of 1.
        $this->assertEquals(log(35, 2) + 1, $matches[0]->getEntropy());

        $password = 'p45s';
        $matches = L33tMatch::match($password);
        $this->assertEquals(log(35, 2) + 2, $matches[0]->getEntropy());
    }
}