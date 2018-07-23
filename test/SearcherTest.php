<?php

namespace ZxcvbnPhp\Test;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matchers\RepeatMatch;
use ZxcvbnPhp\Searcher;

/**
 * @covers \ZxcvbnPhp\Searcher
 */
class SearcherTest extends TestCase
{
    public function testMinimumEntropyMatchSequence()
    {
        $searcher = new Searcher();
        // Test simple password with no matches.
        $password = 'a';
        $entropy = $searcher->getMinimumEntropy($password, []);
        $this->assertSame(log(26, 2), $entropy, 'Entropy incorrect for single character lowercase password');

        // Test password with repeat pattern.
        $password = 'aaa';
        $match = new RepeatMatch($password, 0, 2, 'aaa', 'a');
        $entropy = $searcher->getMinimumEntropy($password, [$match]);
        $this->assertSame(log(26 * 3, 2), $entropy, "Entropy incorrect for '${password}'");
        $sequence = $searcher->matchSequence;
        $this->assertSame($match, $sequence[0], 'Best match incorrect');
    }
}
