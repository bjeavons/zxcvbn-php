<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Scorer;
use ZxcvbnPhp\Test\Matchers\MockMatch;

/**
 * @covers \ZxcvbnPhp\Scorer
 */
class ScorerTest extends TestCase
{
    public const PASSWORD = '0123456789';

    /**
     * @var Scorer
     */
    private $scorer;

    public function setUp(): void
    {
        $this->scorer = new Scorer();
    }

    public function testStrictAssertions()
    {
        $this->assertNotSame(1, 1.0);
    }

    public function testBlankPassword()
    {
        $result = $this->scorer->getMostGuessableMatchSequence('', []);
        $this->assertSame(1.0, $result['guesses']);
        $this->assertEmpty($result['sequence']);
    }

    public function testEmptyMatchSequence()
    {
        $result = $this->scorer->getMostGuessableMatchSequence(self::PASSWORD, []);
        $this->assertSame(1, count($result['sequence']), "result.sequence.length == 1");
        $this->assertSame(10000000001.0, $result['guesses'], "result.guesses == 10000000001");

        $match = $result['sequence'][0];
        $this->assertSame('bruteforce', $match->pattern, "match.pattern == 'bruteforce'");
        $this->assertSame(self::PASSWORD, $match->token, "match.token == " . self::PASSWORD);
        $this->assertSame([0, 9], [$match->begin, $match->end], "[i, j] == [0, 9]");
    }

    public function testMatchAndBruteforceWithPrefix()
    {
        $match = new MockMatch(0, 5, 1);

        $result = $this->scorer->getMostGuessableMatchSequence(self::PASSWORD, [$match], true);
        $this->assertSame(2, count($result['sequence']), "result.sequence.length == 2");
        $this->assertSame($match, $result['sequence'][0], "first match is the provided match object");

        $match1 = $result['sequence'][1];

        $this->assertSame('bruteforce', $match1->pattern, "second match is bruteforce");
        $this->assertSame([6, 9], [$match1->begin, $match1->end], "second match covers full suffix after first match");
    }

    public function testMatchAndBruteforceWithSuffix()
    {
        $match = new MockMatch(3, 9, 1);

        $result = $this->scorer->getMostGuessableMatchSequence(self::PASSWORD, [$match], true);
        $this->assertSame(2, count($result['sequence']), "result.sequence.length == 2");
        $this->assertSame($match, $result['sequence'][1], "second match is the provided match object");

        $match0 = $result['sequence'][0];

        $this->assertSame('bruteforce', $match0->pattern, "first match is bruteforce");
        $this->assertSame([0, 2], [$match0->begin, $match0->end], "first match covers full prefix before second match");
    }

    public function testMatchAndBruteforceWithInfix()
    {
        $match = new MockMatch(1, 8, 1);

        $result = $this->scorer->getMostGuessableMatchSequence(self::PASSWORD, [$match], true);
        $this->assertSame(3, count($result['sequence']), "result.sequence.length == 3");

        $match0 = $result['sequence'][0];
        $match2 = $result['sequence'][2];

        $this->assertSame($match, $result['sequence'][1], "middle match is the provided match object");
        $this->assertSame('bruteforce', $match0->pattern, "first match is bruteforce");
        $this->assertSame('bruteforce', $match2->pattern, "third match is bruteforce");
        $this->assertSame([0, 0], [$match0->begin, $match0->end], "first match covers full prefix before second match");
        $this->assertSame([9, 9], [$match2->begin, $match2->end], "third match covers full suffix after second match");
    }

    public function testBasicGuesses()
    {
        $matches = [
            new MockMatch(0, 9, 1),
            new MockMatch(0, 9, 2),
        ];

        $result = $this->scorer->getMostGuessableMatchSequence(self::PASSWORD, $matches, true);
        $this->assertSame(1, count($result['sequence']), "result.sequence.length == 1");
        $this->assertSame($matches[0], $result['sequence'][0], "result.sequence[0] == m0");
    }

    public function testChoosesLowerGuessesMatchesForSameSpan()
    {
        $matches = [
            new MockMatch(0, 9, 1),
            new MockMatch(0, 9, 2),
        ];

        $result = $this->scorer->getMostGuessableMatchSequence(self::PASSWORD, $matches, true);
        $this->assertSame(1, count($result['sequence']), "result.sequence.length == 1");
        $this->assertSame($matches[0], $result['sequence'][0], "result.sequence[0] == m0");
    }

    public function testChoosesLowerGuessesMatchesForSameSpanReversedOrder()
    {
        $matches = [
            new MockMatch(0, 9, 2),
            new MockMatch(0, 9, 1),
        ];

        $result = $this->scorer->getMostGuessableMatchSequence(self::PASSWORD, $matches, true);
        $this->assertSame(1, count($result['sequence']), "result.sequence.length == 1");
        $this->assertSame($matches[1], $result['sequence'][0], "result.sequence[0] == m1");
    }

    public function testChoosesSupersetMatchWhenApplicable()
    {
        $matches = [
            new MockMatch(0, 9, 3),
            new MockMatch(0, 3, 2),
            new MockMatch(4, 9, 1),
        ];

        $result = $this->scorer->getMostGuessableMatchSequence(self::PASSWORD, $matches, true);
        $this->assertSame(3.0, $result['guesses'], "total guesses == 3");
        $this->assertSame([$matches[0]], $result['sequence'], "sequence is [m0]");
    }

    public function testChoosesSubsetMatchesWhenApplicable()
    {
        $matches = [
            new MockMatch(0, 9, 5),
            new MockMatch(0, 3, 2),
            new MockMatch(4, 9, 1),
        ];

        $result = $this->scorer->getMostGuessableMatchSequence(self::PASSWORD, $matches, true);
        $this->assertSame(4.0, $result['guesses'], "total guesses == 4");
        $this->assertSame([$matches[1], $matches[2]], $result['sequence'], "sequence is [m1, m2]");
    }
}
