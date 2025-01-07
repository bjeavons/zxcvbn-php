<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matcher;
use ZxcvbnPhp\Matchers\Bruteforce;
use ZxcvbnPhp\Matchers\DictionaryMatch;
use ZxcvbnPhp\Matchers\RepeatMatch;

#[CoversClass(Matcher::class)]
class MatcherTest extends TestCase
{
    public function testGetMatches(): void
    {
        $matcher = new Matcher();
        $matches = $matcher->getMatches('jjj');
        $this->assertInstanceOf(RepeatMatch::class, $matches[0]);
        $this->assertSame('repeat', $matches[0]->pattern, 'Pattern incorrect');
        $this->assertCount(1, $matches);

        $matches = $matcher->getMatches('jjjjj');
        $this->assertInstanceOf(RepeatMatch::class, $matches[0]);
        $this->assertSame('repeat', $matches[0]->pattern, 'Pattern incorrect');
    }

    public function testEmptyString(): void
    {
        $matcher = new Matcher();
        $this->assertEmpty($matcher->getMatches(''), "doesn't match ''");
    }

    public function testMultiplePatterns(): void
    {
        $matcher = new Matcher();
        $password = 'r0sebudmaelstrom11/20/91aaaa';

        $expectedMatches = [
            ['dictionary', [ 0,  6]],
            ['dictionary', [ 7, 15]],
            ['date',       [16, 23]],
            ['repeat',     [24, 27]]
        ];

        $matches = $matcher->getMatches($password);
        foreach ($matches as $match) {
            $search = array_search([$match->pattern, [$match->begin, $match->end]], $expectedMatches);
            if ($search !== false) {
                unset($expectedMatches[$search]);
            }
        }

        $this->assertEmpty($expectedMatches, "matches multiple patterns");
    }

    /**
     * There's a similar test in DictionaryTest for this as well, but this specific test is for ensuring that the
     * user input gets passed from the Matcher class through to DictionaryMatch function.
     */
    public function testUserDefinedWords(): void
    {
        $matcher = new Matcher();
        $matches = $matcher->getMatches('_wQbgL491', ['PJnD', 'WQBG', 'ZhwZ']);

        $this->assertInstanceOf(DictionaryMatch::class, $matches[0], "user input match is correct class");
        $this->assertSame('wQbg', $matches[0]->token, "user input match has correct token");
    }

    public function testAddMatcherWillThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $matcher = new Matcher();
        // @phpstan-ignore-next-line
        $matcher->addMatcher('invalid className');
    }

    public function testAddMatcherWillReturnSelf(): void
    {
        $matcher = new Matcher();
        $result = $matcher->addMatcher(Bruteforce::class);

        $this->assertSame($matcher, $result);
    }
}
