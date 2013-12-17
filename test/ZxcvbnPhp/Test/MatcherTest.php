<?php

namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\Matcher;

class MatcherTest extends \PHPUnit_Framework_TestCase
{

    public function testGetMatches()
    {
        $matcher = new Matcher();
        $matches = $matcher->getMatches("jjj");
        $this->assertSame('repeat', $matches[0]->pattern, "Pattern incorrect");
        $this->assertCount(1, $matches);

        $matches = $matcher->getMatches("jjjjj");
        $this->assertSame('repeat', $matches[0]->pattern, "Pattern incorrect");
    }
}