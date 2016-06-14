<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\DateMatch;

class DateTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = 'password';
        $matches = DateMatch::match($password);
        $this->assertEmpty($matches);

        $password = '121997';
        $matches = DateMatch::match($password);
        $this->assertCount(1, $matches);

        // YearMatch will match this.
        $password = '81997';
        $matches = DateMatch::match($password);
        $this->assertEmpty($matches);

        $password = '081997';
        $matches = DateMatch::match($password);
        $this->assertCount(1, $matches);

        $password = '15111997';
        $matches = DateMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame(15, $matches[0]->day, "Incorrect day");
        $this->assertSame(11, $matches[0]->month, "Incorrect month");
        $this->assertSame(1997, $matches[0]->year, "Incorrect year");

        $password = '19970404';
        $matches = DateMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame(04, $matches[0]->day, "Incorrect day");
        $this->assertSame(04, $matches[0]->month, "Incorrect month");
        $this->assertSame(1997, $matches[0]->year, "Incorrect year");

        // Test separators.
        $password = '04/04/1997';
        $matches = DateMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame(04, $matches[0]->day, "Incorrect day");
        $this->assertSame(04, $matches[0]->month, "Incorrect month");
        $this->assertSame(1997, $matches[0]->year, "Incorrect year");
        $this->assertSame('/', $matches[0]->separator, "Incorrect separator");

        $password = 'password1997-04-04';
        $matches = DateMatch::match($password);
        $this->assertCount(1, $matches);
        $this->assertSame(04, $matches[0]->day, "Incorrect day");
        $this->assertSame(04, $matches[0]->month, "Incorrect month");
        $this->assertSame(1997, $matches[0]->year, "Incorrect year");
        $this->assertSame('-', $matches[0]->separator, "Incorrect separator");

        $password = 'date11/11/2000-2001-04-04';
        $matches = DateMatch::match($password);
        $this->assertCount(2, $matches);
        $this->assertSame(11, $matches[0]->day, "Incorrect day");
        $this->assertSame(11, $matches[0]->month, "Incorrect month");
        $this->assertSame(2000, $matches[0]->year, "Incorrect year");
        $this->assertSame('-', $matches[1]->separator, "Incorrect separator");
    }

    public function testEntropy()
    {
        $password = '121997';
        $matches = DateMatch::match($password);
        $this->assertSame(log(119 * 12 * 31, 2), $matches[0]->getEntropy());
    }
}