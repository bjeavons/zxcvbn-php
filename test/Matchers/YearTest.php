<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ZxcvbnPhp\Matchers\DateMatch;
use ZxcvbnPhp\Matchers\YearMatch;

#[CoversClass(YearMatch::class)]
class YearTest extends AbstractMatchTest
{
    public function testNoMatchForNonYear(): void
    {
        $password = 'password';
        $this->assertEmpty(YearMatch::match($password));
    }

    public static function recentYearProvider(): Iterator
    {
        yield ['1922'];
        yield ['2001'];
        yield ['2017'];
    }

    #[DataProvider('recentYearProvider')]
    public function testRecentYears(string $password): void
    {
        $this->checkMatches(
            'matches recent year',
            YearMatch::match($password),
            'regex',
            [$password],
            [[0, strlen($password) - 1]],
            []
        );
    }

    public static function nonRecentYearProvider(): Iterator
    {
        yield ['1420'];
        yield ['1899'];
        yield ['2345'];
    }

    #[DataProvider('nonRecentYearProvider')]
    public function testNonRecentYears(string $password): void
    {
        $matches = YearMatch::match($password);
        $this->assertEmpty($matches, 'does not match non-recent year');
    }

    public function testYearSurroundedByWords(): void
    {
        $prefixes = ['car', 'dog'];
        $suffixes = ['car', 'dog'];
        $pattern = '1900';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as [$password, $i, $j]) {
            $this->checkMatches(
                'identifies years surrounded by words',
                YearMatch::match($password),
                'regex',
                [$pattern],
                [[$i, $j]],
                []
            );
        }

        $password = 'password1900';
        $matches = YearMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame('1900', $matches[0]->token, 'Token incorrect');
    }

    public function testYearWithinOtherNumbers(): void
    {
        $password = '419004';
        $this->checkMatches(
            'matches year within other numbers',
            YearMatch::match($password),
            'regex',
            ['1900'],
            [[1, 4]],
            []
        );
    }

    public function testGuessesPast(): void
    {
        $token = '1972';
        $match = new YearMatch($token, 0, 3, $token);

        $this->assertSame(
            (float) (DateMatch::getReferenceYear() - (int) $token),
            $match->getGuesses(),
            'guesses of |year - REFERENCE_YEAR| for past year matches'
        );
    }

    public function testGuessesFuture(): void
    {
        $token = '2050';
        $match = new YearMatch($token, 0, 3, $token);

        $this->assertSame(
            (float) ((int) $token - DateMatch::getReferenceYear()),
            $match->getGuesses(),
            'guesses of |year - REFERENCE_YEAR| for future year matches'
        );
    }

    public function testGuessesUnderMinimumYearSpace(): void
    {
        $token = '2005';
        $match = new YearMatch($token, 0, 3, $token);

        $this->assertEqualsWithDelta(
            20.0,
            // DateMatch::MIN_YEAR_SPACE
            $match->getGuesses(),
            PHP_FLOAT_EPSILON,
            'guesses of MIN_YEAR_SPACE for a year close to REFERENCE_YEAR'
        );
    }

    public function testFeedback(): void
    {
        $token = '2010';
        $match = new YearMatch($token, 0, strlen($token) - 1, $token);
        $feedback = $match->getFeedback(true);

        $this->assertSame(
            'Recent years are easy to guess',
            $feedback['warning'],
            'year match gives correct warning'
        );
        $this->assertContains(
            'Avoid recent years',
            $feedback['suggestions'],
            'year match gives correct suggestion #1'
        );
        $this->assertContains(
            'Avoid years that are associated with you',
            $feedback['suggestions'],
            'year match gives correct suggestion #2'
        );
    }
}
