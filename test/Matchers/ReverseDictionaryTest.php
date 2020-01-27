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

    public function testFeedback()
    {
        $token = 'ytisrevinu';
        $match = new ReverseDictionaryMatch($token, 0, strlen($token) - 1, $token, [
            'dictionary_name' => 'english_wikipedia',
            'rank' => 69,
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertEquals(
            'A word by itself is easy to guess',
            $feedback['warning'],
            "reverse dictionary match didn't lose the original dictionary match warning"
        );
        $this->assertContains(
            'Reversed words aren\'t much harder to guess',
            $feedback['suggestions'],
            "reverse dictionary match gives correct suggestion"
        );
    }

    public function testFeedbackTop100Password()
    {
        $token = 'retunh';
        $match = new ReverseDictionaryMatch($token, 0, strlen($token) - 1, $token, [
            'dictionary_name' => 'passwords',
            'rank' => 37,
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertEquals(
            'This is similar to a commonly used password',
            $feedback['warning'],
            "reverse dictionary match doesn't give top-100 warning"
        );
    }

    public function testFeedbackShortToken()
    {
        $token = 'eht';
        $match = new ReverseDictionaryMatch($token, 0, strlen($token) - 1, $token, [
            'dictionary_name' => 'english_wikipedia',
            'rank' => 1,
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertEquals(
            'A word by itself is easy to guess',
            $feedback['warning'],
            "reverse dictionary match still gives warning for short token"
        );
        $this->assertNotContains(
            'Reversed words aren\'t much harder to guess',
            $feedback['suggestions'],
            "reverse dictionary match doesn't give suggestion for short token"
        );
    }
}
