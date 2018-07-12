<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\Bruteforce;

class BruteforceTest extends \PHPUnit_Framework_TestCase
{
    public function testGuessesMax()
    {
        $token = str_repeat('a', 1000);
        $match = new Bruteforce($token, 0, 999, $token);
        $this->assertNotEquals(INF, $match->getGuesses(), "long string doesn't return infinite guesses");
    }

    public function testCardinality()
    {
        $match = new Bruteforce('99', 0, 1, '99');
        $this->assertSame(10, $match->getCardinality());

        $match = new Bruteforce('aa', 0, 1, 'aa');
        $this->assertSame(26, $match->getCardinality());

        $match = new Bruteforce('!', 0, 0, '!');
        $this->assertSame(33, $match->getCardinality());

        $match = new Bruteforce('Ab', 0, 1, 'Ab');
        $this->assertSame(52, $match->getCardinality());
    }
}
