<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\Bruteforce;

class BruteforceTest extends AbstractMatchTest
{
    public function testMatch(): void
    {
        $password = 'uH2nvQbugW';

        $this->checkMatches(
            "matches entire string",
            Bruteforce::match($password),
            'bruteforce',
            [$password],
            [[0, 9]],
            []
        );
    }

    public function testMultibyteMatch(): void
    {
        $password = '中华人民共和国';

        $this->checkMatches(
            "matches entire string with multibyte characters",
            Bruteforce::match($password),
            'bruteforce',
            [$password],
            [[0, 6]], // should be 0, 6 and not 0, 20
            []
        );
    }

    public function testGuessesMax(): void
    {
        $token = str_repeat('a', 1000);
        $match = new Bruteforce($token, 0, 999, $token);
        $this->assertNotSame(INF, $match->getGuesses(), "long string doesn't return infinite guesses");
    }

    public function testGuessesMultibyteCharacter(): void
    {
        $token = '🙂'; // smiley face emoji
        $match = new Bruteforce($token, 0, 1, $token);
        $this->assertEqualsWithDelta(11.0, $match->getGuesses(), PHP_FLOAT_EPSILON, "multibyte character treated as one character");
    }
}
