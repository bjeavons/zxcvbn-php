<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ZxcvbnPhp\Matchers\SequenceMatch;

#[CoversClass(SequenceMatch::class)]
class SequenceTest extends AbstractMatchTest
{
    /**
     * @return Iterator<int, mixed>
     */
    public static function shortPasswordProvider(): Iterator
    {
        yield [''];
        yield ['a'];
        yield ['1'];
    }

    #[DataProvider('shortPasswordProvider')]
    public function testShortPassword(string $password): void
    {
        $matches = SequenceMatch::match($password);
        $this->assertEmpty($matches, "doesn't match length-" . strlen((string) $password) . " sequences");
    }

    public function testNonSequence(): void
    {
        $password = 'password';
        $matches = SequenceMatch::match($password);
        $this->assertEmpty($matches, "doesn't match password that's not a sequence");
    }

    public function testOverlappingPatterns(): void
    {
        $password = 'abcbabc';

        $this->checkMatches(
            "matches overlapping patterns",
            SequenceMatch::match($password),
            'sequence',
            ['abc', 'cba', 'abc'],
            [[0, 2], [2, 4], [4, 6]],
            [
                'ascending' => [true, false, true],
            ]
        );
    }

    public function testEmbeddedSequencePatterns(): void
    {
        $prefixes = ['!', '22'];
        $suffixes = ['!', '22'];
        $pattern = 'jihg';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as [$password, $i, $j]) {
            $this->checkMatches(
                "matches embedded sequence patterns",
                SequenceMatch::match($password),
                'sequence',
                [$pattern],
                [[$i, $j]],
                [
                    'sequenceName'  => ['lower'],
                    'ascending' => [false],
                ]
            );
        }
    }

    /**
     * @return Iterator<int, mixed>
     */
    public static function sequenceProvider(): Iterator
    {
        yield ['ABC',   'upper',  true];
        yield ['CBA',   'upper',  false];
        yield ['PQR',   'upper',  true];
        yield ['RQP',   'upper',  false];
        yield ['XYZ',   'upper',  true];
        yield ['ZYX',   'upper',  false];
        yield ['abcd',  'lower',  true];
        yield ['dcba',  'lower',  false];
        yield ['jihg',  'lower',  false];
        yield ['wxyz',  'lower',  true];
        yield ['zxvt',  'lower',  false];
        yield ['0369',  'digits', true];
        yield ['97531', 'digits', false];
    }

    #[DataProvider('sequenceProvider')]
    public function testSequenceInformation(string $password, string $name, bool $ascending): void
    {
        $this->checkMatches(
            "matches " . $password . " as a " . $name . " sequence",
            SequenceMatch::match($password),
            'sequence',
            [$password],
            [[0, strlen($password) - 1]],
            [
                'sequenceName' => [$name],
                'ascending' => [$ascending],
            ]
        );
    }

    public function testMultipleMatches(): void
    {
        $password = 'pass123wordZYX';
        $this->checkMatches(
            "matches password with multiple sequences",
            SequenceMatch::match($password),
            'sequence',
            ['123', 'ZYX'],
            [[4, 6], [11, 13]],
            [
                'sequenceName' => ['digits', 'upper'],
                'ascending' => [true, false],
            ]
        );
    }

    public function testMultibytePassword(): void
    {
        $pattern = 'muÃeca';

        $this->checkMatches(
            'detects sequence in a multibyte password',
            SequenceMatch::match($pattern),
            'sequence',
            ['eca'],
            [[3, 5]],
            [
                'sequenceName' => ['lower'],
                'ascending' => [false],
            ]
        );
    }

    public function testMultibyteSequence(): void
    {
        $pattern = 'αβγδεζ';

        $this->checkMatches(
            'detects sequence consisting of multibyte characters',
            SequenceMatch::match($pattern),
            'sequence',
            [$pattern],
            [[0, 5]],
            [
                'sequenceName' => ['unicode'],
                'ascending' => [true],
            ]
        );
    }

    /**
     * @return Iterator<int, mixed>
     */
    public static function guessProvider(): Iterator
    {
        yield ['ab',   true,  4 * 2];
        // obvious start * len-2
        yield ['XYZ',  true,  26 * 3];
        // base26 * len-3
        yield ['4567', true,  10 * 4];
        // base10 * len-4
        yield ['7654', false, 10 * 4 * 2];
        // base10 * len-4 * descending
        yield ['ZYX',  false, 4 * 3 * 2];
    }

    #[DataProvider('guessProvider')]
    public function testGuesses(string $token, bool $ascending, float $expectedGuesses): void
    {
        $match = new SequenceMatch($token, 0, strlen($token) - 1, $token, ['ascending' => $ascending]);
        $this->assertSame(
            $expectedGuesses,
            $match->getGuesses(),
            "the sequence pattern '$token' has guesses of $expectedGuesses"
        );
    }

    public function testFeedback(): void
    {
        $token = 'rstuvw';
        $match = new SequenceMatch($token, 0, strlen($token) - 1, $token, ['ascending' => true]);
        $feedback = $match->getFeedback(true);

        $this->assertSame(
            'Sequences like abc or 6543 are easy to guess',
            $feedback['warning'],
            "sequence gives correct warning"
        );
        $this->assertSame(
            ['Avoid sequences'],
            $feedback['suggestions'],
            "sequence gives correct suggestion"
        );
    }
}
