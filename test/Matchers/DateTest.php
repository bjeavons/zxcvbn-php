<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use ZxcvbnPhp\Matchers\DateMatch;

class DateTest extends AbstractMatchTest
{
    public static function separatorProvider(): Iterator
    {
        yield [''];
        yield [' '];
        yield ['-'];
        yield ['/'];
        yield ['\\'];
        yield ['_'];
        yield ['.'];
    }

    /**
     * @param string $sep
     */
    #[DataProvider('separatorProvider')]
    public function testSeparators($sep): void
    {
        $password = "13{$sep}2{$sep}1921";

        $this->checkMatches(
            "matches dates that use '$sep' as a separator",
            DateMatch::match($password),
            'date',
            [$password],
            [[0, strlen($password) - 1]],
            [
                'separator' => [$sep],
                'year' => [1921],
                'month' => [2],
                'day' => [13],
            ]
        );
    }

    public function testDateOrders(): void
    {
        [$d, $m, $y] = [8, 8, 88];
        $orders = ['mdy', 'dmy', 'ymd', 'ydm'];
        foreach ($orders as $order) {
            $password = str_replace(
                ['y', 'm', 'd'],
                [(string) $y, (string) $m, (string) $d],
                $order
            );
            $this->checkMatches(
                "matches dates with $order format",
                DateMatch::match($password),
                'date',
                [ $password ],
                [[ 0, strlen($password) - 1 ]],
                [
                    'separator' => [''],
                    'year'      => [1988],
                    'month'     => [8],
                    'day'       => [8],
                ]
            );
        }
    }

    public function testMatchesClosestToReferenceYear(): void
    {
        $password = '111504';
        $this->checkMatches(
            "matches the date with year closest to REFERENCE_YEAR when ambiguous",
            DateMatch::match($password),
            'date',
            [ $password ],
            [[ 0, strlen($password) - 1 ]],
            [
                'separator' => [''],
                'year'      => [2004], // picks '04' -> 2004 as year, not '1504'
                'month'     => [11],
                'day'       => [15],
            ]
        );
    }

    public static function normalDateProvider(): Iterator
    {
        yield [1,  1,  1999];
        yield [11, 8,  2000];
        yield [9,  12, 2005];
        yield [22, 11, 1551];
    }

    /**
     * @param int $day
     * @param int $month
     * @param int $year
     */
    #[DataProvider('normalDateProvider')]
    public function testNormalDatesWithoutSeparator($day, $month, $year): void
    {
        $password = "{$year}{$month}{$day}";
        $this->checkMatches(
            "matches $password without a separator",
            DateMatch::match($password),
            'date',
            [$password],
            [[0, strlen($password) - 1]],
            [
                'separator' => [''],
                'year' => [$year],
            ]
        );
    }

    /**
     * @param int $day
     * @param int $month
     * @param int $year
     */
    #[DataProvider('normalDateProvider')]
    public function testNormalDatesWithSeparator($day, $month, $year): void
    {
        $password = "{$year}.{$month}.{$day}";
        $this->checkMatches(
            "matches $password with a separator",
            DateMatch::match($password),
            'date',
            [$password],
            [[0, strlen($password) - 1]],
            [
                'separator' => ['.'],
                'year' => [$year],
            ]
        );
    }

    public function testMatchesZeroPaddedDates(): void
    {
        $password = "02/02/02";
        $this->checkMatches(
            "matches zero-padded dates",
            DateMatch::match($password),
            'date',
            [ $password ],
            [[ 0, strlen($password) - 1 ]],
            [
                'separator' => ['/'],
                'year'      => [2002],
                'month'     => [2],
                'day'       => [2],
            ]
        );
    }

    public function testFullDateMatched(): void
    {
        $password = "2018-01-20";
        $this->checkMatches(
            "matches full date and not just year",
            DateMatch::match($password),
            'date',
            [ $password ],
            [[ 0, strlen($password) - 1 ]],
            [
                'separator' => ['-'],
                'year'      => [2018],
                'month'     => [1],
                'day'       => [20],
            ]
        );
    }

    public function testMatchesEmbeddedDates(): void
    {
        $prefixes = ['a', 'ab'];
        $suffixes = ['!'];
        $pattern = '1/1/91';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as [$password, $i, $j]) {
            $this->checkMatches(
                "matches embedded dates",
                DateMatch::match($password),
                'date',
                [$pattern],
                [[$i, $j]],
                [
                    'year'  => [1991],
                    'month' => [1],
                    'day'   => [1]
                ]
            );
        }
    }

    public function testMatchesOverlappingDates(): void
    {
        $password = "12/20/1991.12.20";
        $this->checkMatches(
            "matches overlapping dates",
            DateMatch::match($password),
            'date',
            [ '12/20/1991', '1991.12.20' ],
            [[ 0, 9 ], [ 6, 15 ]],
            [
                'separator' => ['/', '.'],
                'year'      => [1991, 1991],
                'month'     => [12, 12],
                'day'       => [20, 20],
            ]
        );
    }

    public function testMatchesDatesPadded(): void
    {
        $password = "912/20/919";
        $this->checkMatches(
            "matches dates padded by non-ambiguous digits",
            DateMatch::match($password),
            'date',
            [ '12/20/91' ],
            [[ 1, 8 ]],
            [
                'separator' => ['/'],
                'year'      => [1991],
                'month'     => [12],
                'day'       => [20],
            ]
        );
    }

    public function testReferenceYearImplementation(): void
    {
        $this->assertSame((int)date('Y'), DateMatch::getReferenceYear(), "reference year implementation");
    }

    public function testNonDateThatLooksLikeDate(): void
    {
        $this->assertEmpty(DateMatch::match('30-31-00'), "no match on invalid date");
    }

    public function testGuessDistanceFromReferenceYear(): void
    {
        $token = '1123';
        $match = new DateMatch($token, 0, strlen($token) - 1, $token, [
            'separator' => '',
            'year' => 1923,
            'month' => 1,
            'day' => 1
        ]);

        $expected = 365.0 * abs(DateMatch::getReferenceYear() - $match->year);
        $this->assertSame(
            $expected,
            $match->getGuesses(),
            "guesses for $token is 365 * distance_from_ref_year"
        );
    }

    public function testGuessMinYearSpace(): void
    {
        $token = '112010';
        $match = new DateMatch($token, 0, strlen($token) - 1, $token, [
            'separator' => '',
            'year' => 2010,
            'month' => 1,
            'day' => 1
        ]);

        $expected = 7300.0; // 365 * DateMatch::MIN_YEAR_SPACE;
        $this->assertSame($expected, $match->getGuesses(), "recent years assume MIN_YEAR_SPACE");
    }

    public function testGuessWithSeparator(): void
    {
        $token = '1/1/2010';
        $match = new DateMatch($token, 0, strlen($token) - 1, $token, [
            'separator' => '/',
            'year' => 2010,
            'month' => 1,
            'day' => 1
        ]);

        $expected = 29200.0; // 365 * DateMatch::MIN_YEAR_SPACE * 4;
        $this->assertSame($expected, $match->getGuesses(), "extra guesses are added for separators");
    }

    public function testFeedback(): void
    {
        $token = '26/01/1990';
        $match = new DateMatch($token, 0, strlen($token) - 1, $token, [
            'separator' => '/',
            'year' => 1990,
            'month' => 1,
            'day' => 26,
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertSame(
            'Dates are often easy to guess',
            $feedback['warning'],
            "date match gives correct warning"
        );
        $this->assertContains(
            'Avoid dates and years that are associated with you',
            $feedback['suggestions'],
            "date match gives correct suggestion"
        );
    }
}
