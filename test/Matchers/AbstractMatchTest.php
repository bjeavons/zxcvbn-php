<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matchers\BaseMatch;

abstract class AbstractMatchTest extends TestCase
{
    /**
     * takes a pattern and list of prefixes/suffixes
     * returns a bunch of variants of that pattern embedded
     * with each possible prefix/suffix combination, including no prefix/suffix
     *
     * @see test-matching.coffee
     *
     * @param  array<int, string>  $prefixes
     * @param  array<int, string>  $suffixes
     *
     * @return array<int, mixed> a list of triplets [variant, i, j] where [i,j] is the start/end of the pattern, inclusive
     */
    protected function generatePasswords(string $pattern, array $prefixes, array $suffixes): array
    {
        $output = [];

        if (! in_array('', $prefixes)) {
            array_unshift($prefixes, '');
        }
        if (! in_array('', $suffixes)) {
            array_unshift($suffixes, '');
        }

        foreach ($prefixes as $prefix) {
            foreach ($suffixes as $suffix) {
                $i = strlen((string) $prefix);
                $j = strlen((string) $prefix) + strlen($pattern) - 1;

                $output[] = [
                    $prefix . $pattern . $suffix,
                    $i,
                    $j,
                ];
            }
        }

        return $output;
    }

    /**
     * @param  string       $prefix       This is prepended to the message of any checks that are run
     * @param  array<int, BaseMatch> $matches
     * @param  array<int, string>|string $patternNames array of pattern names, or a single pattern which will be repeated
     * @param  array<int, string> $patterns
     * @param  array<int, mixed>    $ijs
     * @param  array<string, mixed> $props
     */
    protected function checkMatches(
        string $prefix,
        array $matches,
        array|string $patternNames,
        array $patterns,
        array $ijs,
        array $props
    ): void {
        if (is_string($patternNames)) {
            # shortcut: if checking for a list of the same type of patterns,
            # allow passing a string 'pat' instead of array ['pat', 'pat', ...]
            $patternNames = array_fill(0, count($patterns), $patternNames);
        }

        $this->assertCount(
            count($patterns),
            $matches,
            $prefix . ': matches.length == ' . count($patterns)
        );

        foreach ($patterns as $k => $pattern) {
            $match = $matches[$k];
            $patternName = $patternNames[$k];
            $pattern = $patterns[$k];
            [$i, $j] = $ijs[$k];

            $this->assertSame(
                $patternName,
                $match->pattern,
                "{$prefix} matches[{$k}].pattern == '{$patternName}'"
            );
            $this->assertSame(
                [$i, $j],
                [$match->begin, $match->end],
                "{$prefix} matches[{$k}] should have [i, j] of [{$i}, {$j}]"
            );
            $this->assertSame(
                $pattern,
                $match->token,
                "{$prefix} matches[{$k}].token == '{$pattern}'"
            );

            foreach ($props as $propName => $propList) {
                $propMessage = var_export($propList[$k], true);
                // prop_msg = "'$prop_msg'" if typeof(prop_msg) == 'string'
                $this->assertSame(
                    $propList[$k],
                    $match->$propName,
                    "{$prefix} matches[{$k}].{$propName} == {$propMessage}"
                );
            }
        }
    }
}
