<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\DateMatch;

class DateTest extends AbstractMatchTest
{
    public function separatorProvider()
    {
        return [
            [''],
            [' '],
            ['-'],
            ['/'],
            ['\\'],
            ['_'],
            ['.'],
        ];
    }

    /**
     * @dataProvider separatorProvider
     * @param string $sep
     */
    public function testSeparators($sep)
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

    public function testDateOrders()
    {
        list($d, $m, $y) = [8, 8, 88];
        $orders = ['mdy', 'dmy', 'ymd', 'ydm'];
        foreach ($orders as $order) {
            $password = str_replace(
                ['y', 'm', 'd'],
                [$y, $m, $d],
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

    public function testMatchesClosestToReferenceYear()
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

    public function normalDateProvider()
    {
        return [
            [1,  1,  1999],
            [11, 8,  2000],
            [9,  12, 2005],
            [22, 11, 1551]
        ];
    }

    /**
     * @dataProvider normalDateProvider
     * @param int $day
     * @param int $month
     * @param int $year
     */
    public function testNormalDatesWithoutSeparator($day, $month, $year)
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
     * @dataProvider normalDateProvider
     * @param int $day
     * @param int $month
     * @param int $year
     */
    public function testNormalDatesWithSeparator($day, $month, $year)
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

    public function testMatchesZeroPaddedDates()
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

    public function testFullDateMatched()
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

    public function testMatchesEmbeddedDates()
    {
        $prefixes = ['a', 'ab'];
        $suffixes = ['!'];
        $pattern = '1/1/91';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as list($password, $i, $j)) {
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

    public function testMatchesOverlappingDates()
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

    public function testMatchesDatesPadded()
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

    public function testReferenceYearImplementation()
    {
        $this->assertEquals(date('Y'), DateMatch::getReferenceYear(), "reference year implementation");
    }

    public function testNonDateThatLooksLikeDate()
    {
        $this->assertEmpty(DateMatch::match('30-31-00'), "no match on invalid date");
    }

    public function testGuessDistanceFromReferenceYear()
    {
        $token = '1123';
        $match = new DateMatch($token, 0, strlen($token) - 1, $token, [
            'separator' => '',
            'year' => 1923,
            'month' => 1,
            'day' => 1
        ]);

        $expected = 365 * abs(DateMatch::getReferenceYear() - $match->year);
        $this->assertEquals(
            $expected,
            $match->getGuesses(),
            "guesses for $token is 365 * distance_from_ref_year"
        );
    }

    public function testGuessMinYearSpace()
    {
        $token = '112010';
        $match = new DateMatch($token, 0, strlen($token) - 1, $token, [
            'separator' => '',
            'year' => 2010,
            'month' => 1,
            'day' => 1
        ]);

        $expected = 7300; // 365 * DateMatch::MIN_YEAR_SPACE;
        $this->assertEquals($expected, $match->getGuesses(), "recent years assume MIN_YEAR_SPACE");
    }

    public function testGuessWithSeparator()
    {
        $token = '1/1/2010';
        $match = new DateMatch($token, 0, strlen($token) - 1, $token, [
            'separator' => '/',
            'year' => 2010,
            'month' => 1,
            'day' => 1
        ]);

        $expected = 29200; // 365 * DateMatch::MIN_YEAR_SPACE * 4;
        $this->assertEquals($expected, $match->getGuesses(), "extra guesses are added for separators");
    }

    public function testFeedback()
    {
        $token = '26/01/1990';
        $match = new DateMatch($token, 0, strlen($token) - 1, $token, [
            'separator' => '/',
            'year' => 1990,
            'month' => 1,
            'day' => 26,
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertEquals(
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
