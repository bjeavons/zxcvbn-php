<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\YearMatch;

class YearTest extends AbstractMatchTest
{
    public function recentYearProvider()
    {
        return [
            ['1922'],
            ['2001'],
            ['2017']
        ];
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

    /**
     * @dataProvider nonRecentYearProvider
     * @param $password
     */
    public function testNonRecentYears($password)
    {
        $matches = YearMatch::match($password);
        $this->assertEmpty($matches, "does not match non-recent year");
    }

    public function testMatch()
    {
        $password = 'password';
        $matches = YearMatch::match($password);
        $this->assertEmpty($matches);

        $password = '1900';
        $matches = YearMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame($password, $matches[0]->token, "Token incorrect");
        $this->assertSame($password, $matches[0]->password, "Password incorrect");

        $password = 'password1900';
        $matches = YearMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame("1900", $matches[0]->token, "Token incorrect");
    }

    public function testEntropy()
    {
        $password = '1900';
        $matches = YearMatch::match($password);
        $this->assertEquals(log(119, 2), $matches[0]->getEntropy());
    }
}
