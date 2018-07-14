<?php

namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\Matchers\Match;
use ZxcvbnPhp\Zxcvbn;

class ZxcvbnTest extends \PHPUnit_Framework_TestCase
{
    /** @var Zxcvbn */
    private $zxcvbn;

    public function setUp()
    {
        $this->zxcvbn = new Zxcvbn();
    }

    public function testMinimumGuessesForMultipleMatches()
    {
        /** @var Match[] $matches */
        $matches = $this->zxcvbn->passwordStrength('rockyou')['sequence'];

        // zxcvbn will return two matches: 'rock' (rank 359) and 'you' (rank 1).
        // If tested alone, the word 'you' would return only 1 guess, but because it's part of a larger password,
        // it should return the minimum number of guesses, which is 50 for a multi-character token.
        $this->assertEquals(50, $matches[1]->getGuesses());
    }

    public function testZxcvbn()
    {
        $this->markTestSkipped('The scoring functionality has not yet been reimplemented.');

        $zxcvbn = new Zxcvbn();
        $result = $zxcvbn->passwordStrength("");
        $this->assertEquals(0, $result['entropy'], "Entropy incorrect");
        $this->assertEquals(0, $result['score'], "Score incorrect");

        $result = $zxcvbn->passwordStrength("password");
        $this->assertEquals(0, $result['entropy'], "Entropy incorrect");
        $this->assertEquals(0, $result['score'], "Score incorrect");

        $result = $zxcvbn->passwordStrength("jjjjj");
        $this->assertSame('repeat', $result['match_sequence'][0]->pattern, "Pattern incorrect");

        $password = 'abc213456de';
        $result = $zxcvbn->passwordStrength($password);
        $this->assertEquals(1, $result['score'], "Score incorrect");

        $password = '123abcdefgh334123abcdefgh334123abcdefgh334';
        $result = $zxcvbn->passwordStrength($password);
        $this->assertEquals(4, $result['score'], "Score incorrect");

        $password = '3m8dlD.3Y@example.c0m';
        $result = $zxcvbn->passwordStrength($password, [$password]);
        $this->assertEquals(0, $result['score'], "Score incorrect");
    }
}
