<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test;

use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matchers\Bruteforce;
use ZxcvbnPhp\Matchers\DictionaryMatch;
use ZxcvbnPhp\Matchers\MatchInterface;
use ZxcvbnPhp\Zxcvbn;

class ZxcvbnTest extends TestCase
{
    private Zxcvbn $zxcvbn;

    public function setUp(): void
    {
        $this->zxcvbn = new Zxcvbn();
    }

    public function testMinimumGuessesForMultipleMatches(): void
    {
        /** @var MatchInterface[] $matches */
        $matches = $this->zxcvbn->passwordStrength('rockyou')['sequence'];

        // zxcvbn will return two matches: 'rock' (rank 359) and 'you' (rank 1).
        // If tested alone, the word 'you' would return only 1 guess, but because it's part of a larger password,
        // it should return the minimum number of guesses, which is 50 for a multi-character token.
        $this->assertEqualsWithDelta(50.0, $matches[1]->getGuesses(), PHP_FLOAT_EPSILON);
    }

    public static function typeDataProvider(): Iterator
    {
        yield ['password', 'string'];
        yield ['guesses', 'numeric'];
        yield ['guesses_log10', 'numeric'];
        yield ['sequence', 'array'];
        yield ['crack_times_seconds', 'array'];
        yield ['crack_times_display', 'array'];
        yield ['feedback', 'array'];
        yield ['calc_time', 'numeric'];
    }

    /**
     * @throws \Exception
     */
    #[DataProvider('typeDataProvider')]
    public function testZxcvbnReturnTypes(string $key, string $type): void
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

    public static function sanityCheckDataProvider(): Iterator
    {
        yield ['password', 0, ['dictionary',], 'less than a second', 3];
        yield ['65432', 0, ['sequence',], 'less than a second', 101];
        yield ['sdfgsdfg', 1, ['repeat',], 'less than a second', 2595];
        yield ['fortitude', 1, ['dictionary',], '1 second', 11308];
        yield ['dfjkym', 1, ['bruteforce',], '2 minutes', 1000001];
        yield ['fortitude22', 2, ['dictionary', 'repeat',], '2 minutes', 1140700];
        yield ['absoluteadnap', 2, ['dictionary', 'dictionary',], '25 minutes', 15187504];
        yield ['knifeandspoon', 3, ['dictionary', 'dictionary', 'dictionary'], '1 day', 1108057600];
        yield ['h1dden_26191', 3, ['dictionary', 'bruteforce', 'date'], '4 days', 3081378400];
        yield ['4rfv1236yhn!', 4, ['spatial', 'sequence', 'bruteforce'], '1 month', 38980000000];
        yield ['BVidSNqe3oXVyE1996', 4, ['bruteforce', 'regex',], 'centuries', 10000000000010000];
    }

    /**
     * Some basic sanity checks. All of the underlying functionality is tested in more details in their specific
     * classes, but this is just to check that it's all tied together correctly at the end.
     * @param string $password
     * @param int $score
     * @param string[] $patterns
     * @param string $slowHashingDisplay
     * @param float $guesses
     */
    #[DataProvider('sanityCheckDataProvider')]
    public function testZxcvbnSanityCheck(string $password, int $score, array $patterns, string $slowHashingDisplay, float $guesses): void
    {
        $result = $this->zxcvbn->passwordStrength($password);

        $this->assertSame($password, $result['password'], "zxcvbn result has correct password");
        $this->assertSame($score, $result['score'], "zxcvbn result has correct score");
        $this->assertSame(
            $slowHashingDisplay,
            $result['crack_times_display']['offline_slow_hashing_1e4_per_second'],
            "zxcvbn result has correct display time for offline slow hashing"
        );
        $this->assertEqualsWithDelta($guesses, $result['guesses'], 1.0, "zxcvbn result has correct guesses");

        $actualPatterns = array_map(fn($match) => $match->pattern, $result['sequence']);
        $this->assertSame($patterns, $actualPatterns, "zxcvbn result has correct patterns");
    }

    /**
     * There's a similar test in DictionaryTest for this as well, but this specific test is for ensuring that the
     * user input gets passed from the Zxcvbn class all the way through to the DictionaryMatch function.
     */
    public function testUserDefinedWords(): void
    {
        $result = $this->zxcvbn->passwordStrength('_wQbgL491', ['PJnD', 'WQBG', 'ZhwZ']);

        $this->assertInstanceOf(DictionaryMatch::class, $result['sequence'][1], "user input match is correct class");
        $this->assertSame('wQbg', $result['sequence'][1]->token, "user input match has correct token");
    }

    public function testMultibyteUserDefinedWords(): void
    {
        $result = $this->zxcvbn->passwordStrength('المفاتيح', ['العربية', 'المفاتيح', 'لوحة']);

        $this->assertInstanceOf(DictionaryMatch::class, $result['sequence'][0], "user input match is correct class");
        $this->assertSame('المفاتيح', $result['sequence'][0]->token, "user input match has correct token");
    }

    public function testAddMatcherWillThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore-next-line
        $this->zxcvbn->addMatcher('invalid className');
    }

    public function testAddMatcherWillReturnSelf(): void
    {
        $result = $this->zxcvbn->addMatcher(Bruteforce::class);

        $this->assertSame($this->zxcvbn, $result);
    }
}
