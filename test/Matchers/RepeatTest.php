<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ZxcvbnPhp\Matcher;
use ZxcvbnPhp\Matchers\Bruteforce;
use ZxcvbnPhp\Matchers\RepeatMatch;
use ZxcvbnPhp\Matchers\SequenceMatch;
use ZxcvbnPhp\Scorer;

#[CoversClass(RepeatMatch::class)]
class RepeatTest extends AbstractMatchTest
{
    public function testEmpty(): void
    {
        foreach (['', '#'] as $password) {
            $this->assertEmpty(
                RepeatMatch::match($password),
                "doesn't match length-" . strlen($password) . " repeat patterns"
            );
        }
    }

    public function testSingleCharacterEmbeddedRepeats(): void
    {
        $prefixes = ['@', 'y4@'];
        $suffixes = ['u', 'u%7'];
        $pattern = '&&&&&';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as [$password, $i, $j]) {
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

    public function testSingleCharacterRepeats(): void
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

    public function testAdjacentRepeats(): void
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

    public function testMultipleNonadjacentRepeeats(): void
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

    public function testMultiCharacterRepeats(): void
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

    public function testGreedyMultiCharacterRepeats(): void
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

    public function testFrequentlyRepeatedMultiCharacterRepeats(): void
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

    public function testBaseGuesses(): void
    {
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

    public function testMultibyteRepeat(): void
    {
        $pattern = '🙂🙂🙂';

        $this->checkMatches(
            'detects repeated multibyte characters',
            RepeatMatch::match($pattern),
            'repeat',
            [$pattern],
            [[0, 2]],
            [
                'repeatedChar' => ['🙂'],
                'repeatCount' => [3]
            ]
        );
    }

    public function testRepeatAfterMultibyteCharacters(): void
    {
        $pattern = 'niÃ±abella';

        $this->checkMatches(
            'detects repeat with correct offset after multibyte characters',
            RepeatMatch::match($pattern),
            'repeat',
            ['ll'],
            [[7, 8]],
            [
                'repeatedChar' => ['l'],
                'repeatCount' => [2]
            ]
        );
    }

    public function testBaseMatches(): void
    {
        $pattern = 'abcabc';
        $match = RepeatMatch::match($pattern)[0];

        $baseMatches = $match->baseMatches;
        $this->assertCount(1, $baseMatches);
        $this->assertInstanceOf(SequenceMatch::class, $baseMatches[0]);
    }

    public function testBaseMatchesRecursive(): void
    {
        $pattern = 'mqmqmqltltltmqmqmqltltlt';
        $match = RepeatMatch::match($pattern)[0];
        $this->assertSame('mqmqmqltltlt', $match->repeatedChar);

        $baseMatches = $match->baseMatches;
        $this->assertInstanceOf(RepeatMatch::class, $baseMatches[0]);
        $this->assertSame('mq', $baseMatches[0]->repeatedChar);

        $this->assertInstanceOf(RepeatMatch::class, $baseMatches[1]);
        $this->assertSame('lt', $baseMatches[1]->repeatedChar);
    }

    public function testDuplicateRepeatsInPassword(): void
    {
        $pattern = 'scoobydoo';
        $this->checkMatches(
            'duplicate repeats in the password are identified correctly',
            RepeatMatch::match($pattern),
            'repeat',
            ['oo', 'oo'],
            [[2, 3], [7, 8]],
            [
                'repeatedChar' => ['o', 'o'],
                'repeatCount' => [2, 2]
            ]
        );
    }

    public static function guessesProvider(): Iterator
    {
        yield [ 'aa',   'a',  2,  24];
        yield [ '999',  '9',  3,  36];
        yield [ '$$$$', '$',  4,  48];
        yield [ 'abab', 'ab', 2,  18];
        yield [ 'batterystaplebatterystaplebatterystaple', 'batterystaple', 3,  85277994];
    }

    /**
     * @param string $token
     * @param string $repeatedChar
     * @param int    $repeatCount
     * @param float  $expectedGuesses
     */
    #[DataProvider('guessesProvider')]
    public function testGuesses(string $token, string $repeatedChar, int $repeatCount, float $expectedGuesses): void
    {
        $scorer = new Scorer();
        $matcher = new Matcher();
        $baseAnalysis = $scorer->getMostGuessableMatchSequence($repeatedChar, $matcher->getMatches($repeatedChar));
        $baseGuesses = (int) $baseAnalysis['guesses'];

        $match = new RepeatMatch($token, 0, strlen($token) - 1, $token, [
            'repeated_char' => $repeatedChar,
            'repeat_count' => $repeatCount,
            'base_guesses' => $baseGuesses,
        ]);

        $this->assertSame($expectedGuesses, $match->getGuesses(), "the repeat pattern {$token} has guesses of {$expectedGuesses}");
    }

    public function testFeedbackSingleCharacterRepeat(): void
    {
        $token = 'bbbbbb';
        $match = new RepeatMatch($token, 0, strlen($token) - 1, $token, [
            'repeated_char' => 'b',
            'repeat_count' => 6,
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertSame(
            'Repeats like "aaa" are easy to guess',
            $feedback['warning'],
            "one repeated character gives correct warning"
        );
        $this->assertContains(
            'Avoid repeated words and characters',
            $feedback['suggestions'],
            "one repeated character gives correct suggestion"
        );
    }

    public function testFeedbackMultipleCharacterRepeat(): void
    {
        $token = 'bababa';
        $match = new RepeatMatch($token, 0, strlen($token) - 1, $token, [
            'repeated_char' => 'ba',
            'repeat_count' => 3,
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertSame(
            'Repeats like "abcabcabc" are only slightly harder to guess than "abc"',
            $feedback['warning'],
            "multiple repeated characters gives correct warning"
        );
        $this->assertContains(
            'Avoid repeated words and characters',
            $feedback['suggestions'],
            "multiple repeated characters gives correct suggestion"
        );
    }
}
