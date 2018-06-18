<?php

namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\Matchers\RepeatMatch;
use ZxcvbnPhp\Searcher;

class SearcherTest extends \PHPUnit_Framework_TestCase
{

    public function testMinimumEntropyMatchSequence()
    {
        $searcher = new Searcher();
        // Test simple password with no matches.
        $password = 'a';
        $entropy = $searcher->getMinimumEntropy($password, []);
        $this->assertEquals(log(26, 2), $entropy, 'Entropy incorrect for single character lowercase password');

        // Test password with repeat pattern.
        $password = 'aaa';
        $match = new RepeatMatch($password, 0, 2, 'aaa', 'a', 12, [], 3);
        $entropy = $searcher->getMinimumEntropy($password, [$match]);
        $this->assertEquals(log(26 * 3, 2), $entropy, "Entropy incorrect for '$password'");
        $sequence = $searcher->matchSequence;
        $this->assertSame($match, $sequence[0], "Best match incorrect");
    }
}
