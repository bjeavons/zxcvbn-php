<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\RepeatMatch;
use ZxcvbnPhp\Matchers\SequenceMatch;

class RepeatTest extends AbstractMatchTest
{
    public function testEmpty()
    {
        foreach(['', '#'] as $password) {
            $this->assertEmpty(
                RepeatMatch::match($password),
                "doesn't match length-".strlen($password)." repeat patterns"
            );
        }
    }
    
    public function testSingleCharacterEmbeddedRepeats()
    {
        $prefixes = ['@', 'y4@'];
        $suffixes = ['u', 'u%7'];
        $pattern = '&&&&&';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as list($password, $i, $j)) {
            $this->checkMatches(
                "matches embedded repeat patterns",
                RepeatMatch::match($password),
                'repeat',
                [$pattern],
                [[$i, $j]],
                [
                    'repeatedChar' => ['&'],
                    'repeatCount' => [5],
                ]
            );
        }
    }

    public function testSingleCharacterRepeats()
    {
        foreach ([3, 12] as $length) {
            foreach (['a', 'Z', '4', '&'] as $chr) {
                $pattern = str_repeat($chr, $length);

                $this->checkMatches(
                    "matches repeats with base character '$chr'",
                    RepeatMatch::match($pattern),
                    'repeat',
                    [$pattern],
                    [[0, strlen($pattern) - 1]],
                    [
                        'repeatedChar' => [$chr],
                        'repeatCount' => [$length]
                    ]
                );
            }
        }
    }

    public function testAdjacentRepeats()
    {
        $str = 'BBB1111aaaaa@@@@@@';
        $patterns = ['BBB','1111','aaaaa','@@@@@@'];
        $this->checkMatches(
            "matches multiple adjacent repeats",
            RepeatMatch::match($str),
            'repeat',
            $patterns,
            [[0, 2],[3, 6],[7, 11],[12, 17]],
            [
                'repeatedChar' => ['B', '1', 'a', '@'],
                'repeatCount' => [3, 4, 5, 6],
            ]
        );
    }

    public function testMultipleNonadjacentRepeeats()
    {
        $str = '2818BBBbzsdf1111@*&@!aaaaaEUDA@@@@@@1729';
        $patterns = ['BBB','1111','aaaaa','@@@@@@'];
        $this->checkMatches(
            'matches multiple repeats with non-repeats in-between',
            RepeatMatch::match($str),
            'repeat',
            $patterns,
            [[4, 6],[12, 15],[21, 25],[30, 35]],
            [
                'repeatedChar' => ['B', '1', 'a', '@'],
                'repeatCount' => [3, 4, 5, 6],
            ]
        );
    }

    public function testMultiCharacterRepeats()
    {
        $pattern = 'abab';
        $this->checkMatches(
            'matches multi-character repeat pattern',
            RepeatMatch::match($pattern),
            'repeat',
            [$pattern],
            [[0, strlen($pattern) - 1]],
            [
                'repeatedChar' => ['ab'],
                'repeatCount' => [2],
            ]
        );
    }

    public function testGreedyMultiCharacterRepeats()
    {
        $pattern = 'aabaab';
        $this->checkMatches(
            'matches aabaab as a repeat instead of the aa prefix',
            RepeatMatch::match($pattern),
            'repeat',
            [$pattern],
            [[0, strlen($pattern) - 1]],
            [
                'repeatedChar' => ['aab'],
                'repeatCount' => [2],
            ]
        );
    }

    public function testFrequentlyRepeatedMultiCharacterRepeats()
    {
        $pattern = 'abababab';
        $this->checkMatches(
            'identifies ab as repeat string, even though abab is also repeated',
            RepeatMatch::match($pattern),
            'repeat',
            [$pattern],
            [[0, strlen($pattern) - 1]],
            [
                'repeatedChar' => ['ab'],
                'repeatCount' => [4],
            ]
        );
    }

    public function testBaseGuesses()
    {
        $this->markTestSkipped('Base guesses have not yet been implemented.');

        $pattern = 'abcabc';
        $this->checkMatches(
            'calculates the correct number of guesses for the base token',
            RepeatMatch::match($pattern),
            'repeat',
            [$pattern],
            [[0, strlen($pattern) - 1]],
            [
                'repeatedChar' => ['abc'],
                'repeatCount' => [2],
                'baseGuesses' => [13]
            ]
        );
    }

    public function testBaseMatches()
    {
        $this->markTestSkipped('Base matches have not yet been implemented.');

        $pattern = 'abcabc';
        $match = RepeatMatch::match($pattern)[0];

        $baseMatches = $match->baseMatches;
        $this->assertEquals(1, count($baseMatches));
        $this->assertInstanceOf(SequenceMatch::class, $baseMatches[0]);
    }
}
