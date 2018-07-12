<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\ReverseDictionaryMatch;

class RerverseDictionaryTest extends AbstractMatchTest
{
    protected static $testDicts = [
        'd1' => [
            '123' => 1,
            '321' => 2,
            '456' => 3,
            '654' => 4,
        ],
    ];

    public function testReversedDictionaryWordWithCustomDictionary()
    {
        $password = '0123456789';

        $this->checkMatches(
            "matches against reversed words in custom dictionary",
            ReverseDictionaryMatch::match($password, [], self::$testDicts),
            'dictionary',
            ['123', '456'],
            [[1, 3], [4, 6]],
            [
                'matchedWord' => ['321', '654'],
                'reversed' => [true, true],
                'rank' => [2, 4],
                'dictionaryName' => ['d1', 'd1'],
            ]
        );
    }

    public function testGuessesReversed()
    {
        $match = new ReverseDictionaryMatch('aaa', 0, 2, 'aaa', ['rank' => 32]);
        $expected = 32 * 2;     // rank * reversed
        $this->assertEquals($expected, $match->getGuesses(), "guesses are doubled when word is reversed");
    }
}
