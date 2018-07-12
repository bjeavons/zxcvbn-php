<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\DateMatch;
use ZxcvbnPhp\Matchers\YearMatch;

class YearTest extends AbstractMatchTest
{
    public function testNoMatchForNonYear()
    {
        $password = 'password';
        $this->assertEmpty(YearMatch::match($password));
    }

    public function recentYearProvider()
    {
        return [
            ['1922'],
            ['2001'],
            ['2017']
        ];
    }

    /**
     * @dataProvider recentYearProvider
     * @param $password
     */
    public function testRecentYears($password)
    {
        $this->checkMatches(
            "matches recent year",
            YearMatch::match($password),
            'year',
            [$password],
            [[0, strlen($password) - 1]],
            []
        );
    }

    public function nonRecentYearProvider()
    {
        return [
            ['1420'],
            ['1899'],
            ['2020']
        ];
    }

    /**
     * @dataProvider nonRecentYearProvider
     * @param $password
     */
    public function testNonRecentYears($password)
    {
        $matches = YearMatch::match($password);
        $this->assertEmpty($matches, "does not match non-recent year");
    }

    public function testYearSurroundedByWords()
    {
        $prefixes = ['car', 'dog'];
        $suffixes = ['car', 'dog'];
        $pattern = '1900';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as list($password, $i, $j)) {
            $this->checkMatches(
                "identifies years surrounded by words",
                YearMatch::match($password),
                'year',
                [$pattern],
                [[$i, $j]],
                []
            );
        }

        $password = 'password1900';
        $matches = YearMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame("1900", $matches[0]->token, "Token incorrect");
    }

    public function testYearWithinOtherNumbers()
    {
        $password = '419004';
        $this->checkMatches(
            "matches year within other numbers",
            YearMatch::match($password),
            'year',
            ['1900'],
            [[1, 4]],
            []
        );
    }

    public function testGuessesPast()
    {
        $token = '1972';
        $match = new YearMatch($token, 0, 3, $token);

        $this->assertEquals(
            DateMatch::getReferenceYear() - (int)$token,
            $match->getGuesses(),
            "guesses of |year - REFERENCE_YEAR| for past year matches"
        );
    }

    public function testGuessesFuture()
    {
        $token = '2050';
        $match = new YearMatch($token, 0, 3, $token);

        $this->assertEquals(
            (int)$token - DateMatch::getReferenceYear(),
            $match->getGuesses(),
            "guesses of |year - REFERENCE_YEAR| for future year matches"
        );
    }

    public function testGuessesUnderMinimumYearSpace()
    {
        $token = '2005';
        $match = new YearMatch($token, 0, 3, $token);

        $this->assertEquals(
            20, // DateMatch::MIN_YEAR_SPACE
            $match->getGuesses(),
            "guesses of MIN_YEAR_SPACE for a year close to REFERENCE_YEAR"
        );
    }
}
