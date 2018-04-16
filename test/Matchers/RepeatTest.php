<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\RepeatMatch;

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
    
    public function testSingleCharacterRepeats()
    {
        $prefixes = ['@', 'y4@'];
        $suffixes = ['u', 'u%7'];
        $pattern = '&&&&&';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as $variant) {
            list($password, $i, $j) = $variant;

            $this->checkMatches(
                "matches embedded repeat patterns",
                RepeatMatch::match($password),
                'repeat',
                [$pattern],
                [[$i, $j]],
                [
                    'repeatedChar' => ['&']
                ]
            );
        }

        foreach (array(3, 12) as $length) {
            foreach (array('a', 'Z', '4', '&') as $chr) {
                $pattern = str_repeat($chr, $length);

                $this->checkMatches(
                    "matches repeats with base character '$chr'",
                    RepeatMatch::match($pattern),
                    'repeat',
                    [$pattern],
                    [[0, strlen($pattern) - 1]],
                    [
                        'repeatedChar' => [$chr]
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
                'repeatedChar' => ['B', '1', 'a', '@']
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
                'repeatedChar' => ['B', '1', 'a', '@']
            ]
        );
    }
}
