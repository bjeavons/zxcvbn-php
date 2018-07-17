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

    public function testGuessesMultibyteCharacter()
    {
        $token = '🙂'; // smiley face emoji
        $match = new Bruteforce($token, 0, 1, $token);
        $this->assertEquals(11, $match->getGuesses(), "multibyte character treated as one character");
    }
}
