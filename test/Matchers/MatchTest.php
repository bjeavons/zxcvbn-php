<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matchers\DateMatch;

class MatchTest extends TestCase
{
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
