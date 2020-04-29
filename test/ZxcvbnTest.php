<?php

namespace ZxcvbnPhp\Test;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matchers\Bruteforce;
use ZxcvbnPhp\Matchers\DictionaryMatch;
use ZxcvbnPhp\Matchers\Match;
use ZxcvbnPhp\Zxcvbn;

class ZxcvbnTest extends TestCase
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

    public function typeDataProvider()
    {
        return [
            ['password', 'string'],
            ['guesses', 'numeric'],
            ['guesses_log10', 'numeric'],
            ['sequence', 'array'],
            ['crack_times_seconds', 'array'],
            ['crack_times_display', 'array'],
            ['feedback', 'array'],
            ['calc_time', 'numeric']
        ];
    }

    /**
     * @dataProvider typeDataProvider
     */
    public function testZxcvbnReturnTypes($key, $type)
    {
        $zxcvbn = new Zxcvbn();
        $result = $zxcvbn->passwordStrength('utmostfortitude2018');

        $this->assertArrayHasKey($key, $result, "zxcvbn result has key " . $key);

        if ($type === 'string') {
            $correct = is_string($result[$key]);
        } elseif ($type === 'numeric') {
            $correct = is_int($result[$key]) || is_float($result[$key]);
        } elseif ($type === 'array') {
            $correct = is_array($result[$key]);
        } else {
            throw new \Exception('Invalid test case');
        }

        $this->assertTrue($correct, "zxcvbn result value " . $key . " is type " . $type);
    }

    public function sanityCheckDataProvider()
    {
        return [
            ['password',           0, ['dictionary',                           ], 'less than a second', 3],
            ['65432',              0, ['sequence',                             ], 'less than a second', 101],
            ['sdfgsdfg',           1, ['repeat',                               ], 'less than a second', 2595.0000000276],
            ['fortitude',          1, ['dictionary',                           ], '1 second',           11308],
            ['dfjkym',             1, ['bruteforce',                           ], '2 minutes',          1000001],
            ['fortitude22',        2, ['dictionary', 'repeat',                 ], '2 minutes',          1140700],
            ['absoluteadnap',      2, ['dictionary', 'dictionary',             ], '25 minutes',         15187504],
            ['knifeandspoon',      3, ['dictionary', 'dictionary', 'dictionary'], '1 day',              1108057600],
            ['h1dden_26191',       3, ['dictionary', 'bruteforce', 'date'      ], '3 days',             2642940400],
            ['4rfv1236yhn!',       4, ['spatial',    'sequence',   'bruteforce'], '1 month',            38980000000.414],
            ['BVidSNqe3oXVyE1996', 4, ['bruteforce', 'regex',                  ], 'centuries',          10000000000010000],
        ];
    }

    /**
     * Some basic sanity checks. All of the underlying functionality is tested in more details in their specific
     * classes, but this is just to check that it's all tied together correctly at the end.
     * @dataProvider sanityCheckDataProvider
     * @param string   $password
     * @param int      $score
     * @param string[] $patterns
     * @param string   $slowHashingDisplay
     * @param float    $guesses
     */
    public function testZxcvbnSanityCheck($password, $score, $patterns, $slowHashingDisplay, $guesses)
    {
        $result = $this->zxcvbn->passwordStrength($password);

        $this->assertEquals($password, $result['password'], "zxcvbn result has correct password");
        $this->assertEquals($score, $result['score'], "zxcvbn result has correct score");
        $this->assertEquals(
            $slowHashingDisplay,
            $result['crack_times_display']['offline_slow_hashing_1e4_per_second'],
            "zxcvbn result has correct display time for offline slow hashing"
        );
        $this->assertEquals($guesses, $result['guesses'], "zxcvbn result has correct guesses");

        $actualPatterns = array_map(function ($match) {
            return $match->pattern;
        }, $result['sequence']);
        $this->assertEquals($patterns, $actualPatterns, "zxcvbn result has correct patterns");
    }

    /**
     * There's a similar test in DictionaryTest for this as well, but this specific test is for ensuring that the
     * user input gets passed from the Zxcvbn class all the way through to the DictionaryMatch function.
     */
    public function testUserDefinedWords()
    {
        $result = $this->zxcvbn->passwordStrength('_wQbgL491', ['PJnD', 'WQBG', 'ZhwZ']);

        $this->assertInstanceOf(DictionaryMatch::class, $result['sequence'][1], "user input match is correct class");
        $this->assertEquals('wQbg', $result['sequence'][1]->token, "user input match has correct token");
    }

    public function testMultibyteUserDefinedWords()
    {
        $result = $this->zxcvbn->passwordStrength('المفاتيح', ['العربية', 'المفاتيح', 'لوحة']);

        $this->assertInstanceOf(DictionaryMatch::class, $result['sequence'][0], "user input match is correct class");
        $this->assertEquals('المفاتيح', $result['sequence'][0]->token, "user input match has correct token");
    }

    public function testAddMatcherWillThrowException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->zxcvbn->addMatcher('invalid className');

        $this->expectNotToPerformAssertions();
    }

    public function testAddMatcherWillReturnSelf()
    {
        $result = $this->zxcvbn->addMatcher(Bruteforce::class);

        $this->assertSame($this->zxcvbn, $result);
    }
}
