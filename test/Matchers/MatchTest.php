<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matchers\DateMatch;
use ZxcvbnPhp\Matchers\BaseMatch;
use ZxcvbnPhp\Matchers\MatchInterface;

class MatchTest extends TestCase
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
            [206, 202, 72867865],
            [3, 5, 0],
            [29847, 2, 445406781],
        ];
    }

    /**
     * @dataProvider binomialDataProvider
     * @param int $n
     * @param int $k
     * @param int $expected
     */
    public function testBinomialCoefficient(int $n, int $k, int $expected)
    {
        $this->assertSame($expected, BaseMatch::binom($n, $k), "binom returns expected result");
        $this->assertSame($expected, BaseMatch::binomPolyfill($n, $k), "binomPolyfill returns expected result");
    }

    public function testBinomialMirrorIdentity()
    {
        $n = 49;
        $k = 12;

        $this->assertSame(
            BaseMatch::binom($n, $k),
            BaseMatch::binom($n, $n - $k),
            "mirror identity"
        );
    }

    public function testBinomialPascalsTriangleIdentity()
    {
        $n = 49;
        $k = 12;

        $this->assertSame(
            BaseMatch::binom($n, $k),
            BaseMatch::binom($n - 1, $k - 1) + BaseMatch::binom($n - 1, $k),
            "pascal's triangle identity"
        );
    }

    /**
     * @param float $guesses
     * @return MockObject|DateMatch
     */
    private function getMatchMock(float $guesses)
    {
        $stub = $this->getMockBuilder(DateMatch::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getGuesses'])
            ->getMock();
        $stub->method('getGuesses')->willReturn($guesses);

        return $stub;
    }

    /**
     * @return float[][]
     */
    public function log10Provider(): array
    {
        return [
            [1.0, 0.0],
            [10.0, 1.0],
            [100.0, 2.0],
            [500.0, log10(500)],
        ];
    }

    /**
     * @dataProvider log10Provider
     * @param float $n
     * @param float $expected
     */
    public function testGuessesLog10(float $n, float $expected): void
    {
        $stub = $this->getMatchMock($n);
        $this->assertSame($expected, $stub->getGuessesLog10(), "log10 guesses");
    }
}
