<?php

namespace ZxcvbnPhp\Test;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matcher;
use ZxcvbnPhp\Matchers\DictionaryMatch;

/**
 * @covers \ZxcvbnPhp\Matcher
 */
class MatcherTest extends TestCase
{
    public function testGetMatches()
    {
        $matcher = new Matcher();
        $matches = $matcher->getMatches('jjj');
        $this->assertSame('repeat', $matches[0]->pattern, 'Pattern incorrect');
        $this->assertCount(1, $matches);

        $matches = $matcher->getMatches('jjjjj');
        $this->assertSame('repeat', $matches[0]->pattern, 'Pattern incorrect');
    }

    public function testEmptyString()
    {
        $matcher = new Matcher();
        $this->assertEmpty($matcher->getMatches(''), "doesn't match ''");
    }

    public function testMultiplePatterns()
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
    public function testUserDefinedWords()
    {
        $matcher = new Matcher();
        $matches = $matcher->getMatches('_wQbgL491', ['PJnD', 'WQBG', 'ZhwZ']);

        $this->assertInstanceOf(DictionaryMatch::class, $matches[0], "user input match is correct class");
        $this->assertEquals('wQbg', $matches[0]->token, "user input match has correct token");
    }
}
