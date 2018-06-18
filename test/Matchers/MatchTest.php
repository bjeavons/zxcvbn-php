<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\DateMatch;
use ZxcvbnPhp\Matchers\Match;
use ZxcvbnPhp\Matchers\MatchInterface;

class MatchTest extends \PHPUnit_Framework_TestCase
{
    public function binomialDataProvider()
    {
        return [
            [0, 0, 1],
            [1, 0, 1],
            [5, 0, 1],
            [0, 1, 0],
            [0, 5, 0],
            [2, 1, 2],
            [4, 2, 6],
            [33, 7, 4272048],
        ];
    }

    /**
     * @dataProvider binomialDataProvider
     * @param $n
     * @param $k
     * @param $expected
     */
    public function testBinomialCoefficient($n, $k, $expected)
    {
        $this->assertEquals($expected, Match::binom($n, $k), "binom returns expected result");
    }

    public function testBinomialMirrorIdentity()
    {
        $n = 49;
        $k = 12;

        $this->assertEquals(
            Match::binom($n, $k),
            Match::binom($n, $n - $k),
            "mirror identity"
        );
    }

    public function testBinomialPascalsTriangleIdentity()
    {
        $n = 49;
        $k = 12;

        $this->assertEquals(
            Match::binom($n, $k),
            Match::binom($n - 1, $k - 1) + Match::binom($n - 1, $k),
            "pascal's triangle identity"
        );
    }

    /**
     * @param int $guesses
     * @return \PHPUnit_Framework_MockObject_MockObject|Match
     */
    private function getMatchMock($guesses)
    {
        $stub = $this->createPartialMock(DateMatch::class, ['getGuesses']);
        $stub->method('getGuesses')->willReturn($guesses);

        return $stub;
    }

    public function log10Provider()
    {
        return [
            [1, 0],
            [10, 1],
            [100, 2],
            [500, log10(500)],
        ];
    }

    /**
     * @dataProvider log10Provider
     * @param $n
     * @param $expected
     */
    public function testGuessesLog10($n, $expected)
    {
        $stub = $this->getMatchMock($n);
        $this->assertEquals($expected, $stub->getGuessesLog10(), "log10 guesses");
    }
}
