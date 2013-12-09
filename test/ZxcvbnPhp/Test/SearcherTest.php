<?php

namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\Searcher;
use ZxcvbnPhp\Matchers\Repeat;

class SearcherTest extends \PHPUnit_Framework_TestCase
{

    public function testMinimumEntropyMatchSequence()
    {
        // Test simple password with no matches.
        $password = 'a';
        list($entropy, ) = Searcher::getMinimumEntropyMatchSequence($password, array());
        $this->assertEquals(log(26, 2), $entropy, 'Entropy correct for single character lowercase password');

        // Test password with repeat pattern.
        $password = 'aaa';
        $match = new Repeat($password, 0, 2, 'aaa', 'a');
        list($entropy, $sequence) = Searcher::getMinimumEntropyMatchSequence($password, array($match));
        $this->assertEquals(log(pow(26, 3), 2), $entropy, "Entropy correct for '$password'");
        $this->assertEquals(get_class($match), get_class($sequence[0]), 'Best sequence contains Repeat match');
    }
}