<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Matcher;
use ZxcvbnPhp\Math\Binomial;

/**
 * Class L33tMatch extends DictionaryMatch to translate l33t into dictionary words for matching.
 *
 * @package ZxcvbnPhp\Matchers
 */
class L33tMatch extends DictionaryMatch
{
    /** @var array<string, string> An array of substitutions made to get from the token to the dictionary word. */
    public array $sub = [];

    /** @var string A user-readable string that shows which substitutions were detected. */
    public string $subDisplay;

    /** @var bool Whether or not the token contained l33t substitutions. */
    public bool $l33t = true;

    /**
     * @param array{'sub'?: array<mixed>, 'sub_display'?: string} $params
     */
    public function __construct(string $password, int $begin, int $end, string $token, array $params = [])
    {
        parent::__construct($password, $begin, $end, $token, $params);

        $this->sub = $params['sub'] ?? [];
        $this->subDisplay = $params['sub_display'] ?? '';
    }

    /**
     * Match occurrences of l33t words in password to dictionary words.
     *
     * @param array<mixed> $userInputs
     * @param array<string, mixed> $rankedDictionaries
     *
     * @return array<L33tMatch>
     */
    public static function match(string $password, array $userInputs = [], array $rankedDictionaries = []): array
    {
        // Translate l33t password and dictionary match the translated password.
        $maps = array_filter(static::getL33tSubstitutions(static::getL33tSubtable($password)));
        if ($maps === []) {
            return [];
        }

        $matches = [];
        if ($rankedDictionaries === []) {
            $rankedDictionaries = static::getRankedDictionaries();
        }

        foreach ($maps as $map) {
            $translatedWord = static::translate($password, $map);

            /** @var array<L33tMatch> $results */
            $results = parent::match($translatedWord, $userInputs, $rankedDictionaries);
            foreach ($results as $match) {
                $token = mb_substr($password, $match->begin, $match->end - $match->begin + 1);

                # only return the matches that contain an actual substitution
                if (mb_strtolower($token) === $match->matchedWord) {
                    continue;
                }

                # filter single-character l33t matches to reduce noise.
                # otherwise '1' matches 'i', '4' matches 'a', both very common English words
                # with low dictionary rank.
                if (mb_strlen($token) === 1) {
                    continue;
                }

                $display = [];
                foreach ($map as $i => $t) {
                    if (mb_strpos($token, (string) $i) !== false) {
                        $match->sub[$i] = $t;
                        $display[] = "{$i} -> {$t}";
                    }
                }
                $match->token = $token;
                $match->subDisplay = implode(', ', $display);

                $matches[] = $match;
            }
        }

        Matcher::usortStable($matches, Matcher::compareMatches(...));
        return $matches;
    }

    /**
     * @return array{'warning': string, "suggestions": array<string>}
     */
    public function getFeedback(bool $isSoleMatch): array
    {
        $feedback = parent::getFeedback($isSoleMatch);

        $feedback['suggestions'][] = "Predictable substitutions like '@' instead of 'a' don't help very much";

        return $feedback;
    }

    /**
     * @param array<string> $map
     */
    protected static function translate(string $string, array $map): string
    {
        return str_replace(array_keys($map), array_values($map), $string);
    }

    /**
     * @return array<string, array<string>>
     */
    protected static function getL33tTable(): array
    {
        return [
            'a' => ['4', '@'],
            'b' => ['8'],
            'c' => ['(', '{', '[', '<'],
            'e' => ['3'],
            'g' => ['6', '9'],
            'i' => ['1', '!', '|'],
            'l' => ['1', '|', '7'],
            'o' => ['0'],
            's' => ['$', '5'],
            't' => ['+', '7'],
            'x' => ['%'],
            'z' => ['2'],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    protected static function getL33tSubtable(string $password): array
    {
        $subTable = [];

        // The preg_split call below is a multibyte compatible version of str_split
        $splitItems = preg_split('//u', $password, -1, PREG_SPLIT_NO_EMPTY);

        if ($splitItems !== false) {
            $passwordChars = array_unique($splitItems);

            $table = static::getL33tTable();
            foreach ($table as $letter => $substitutions) {
                foreach ($substitutions as $sub) {
                    if (in_array($sub, $passwordChars)) {
                        $subTable[$letter][] = $sub;
                    }
                }
            }
        }

        return $subTable;
    }

    /**
     * @param array<string, array<string>> $subtable
     *
     * @return array<int, mixed>
     */
    protected static function getL33tSubstitutions(array $subtable): array
    {
        $keys = array_keys($subtable);
        $substitutions = self::substitutionTableHelper($subtable, $keys, [[]]);

        // Converts the substitution arrays from [ [a, b], [c, d] ] to [ a => b, c => d ]
        $substitutions = array_map(static fn (array $subArray): array => array_combine(array_column($subArray, 0), array_column($subArray, 1)), $substitutions);

        return $substitutions;
    }

    /**
     * @param array<string, array<string>> $table
     * @param array<int, string> $keys
     * @param array<int, mixed> $subs
     *
     * @return array<int, mixed>
     */
    protected static function substitutionTableHelper(array $table, array $keys, array $subs): array
    {
        if ($keys === []) {
            return $subs;
        }

        $firstKey = array_shift($keys);
        $otherKeys = $keys;
        $nextSubs = [];

        foreach ($table[$firstKey] as $l33tCharacter) {
            foreach ($subs as $sub) {
                $dupL33tIndex = false;
                foreach ($sub as $index => $char) {
                    if ($char[0] === $l33tCharacter) {
                        $dupL33tIndex = $index;
                        break;
                    }
                }

                if ($dupL33tIndex === false) {
                    $subExtension = $sub;
                    $subExtension[] = [$l33tCharacter, $firstKey];
                    $nextSubs[] = $subExtension;
                } else {
                    $subAlternative = $sub;
                    array_splice($subAlternative, $dupL33tIndex, 1);
                    $subAlternative[] = [$l33tCharacter, $firstKey];
                    $nextSubs[] = $sub;
                    $nextSubs[] = $subAlternative;
                }
            }
        }

        $nextSubs = array_unique($nextSubs, SORT_REGULAR);
        return self::substitutionTableHelper($table, $otherKeys, $nextSubs);
    }

    protected function getRawGuesses(): float
    {
        return parent::getRawGuesses() * $this->getL33tVariations();
    }

    protected function getL33tVariations(): float
    {
        $variations = 1;

        foreach ($this->sub as $substitution => $letter) {
            $characters = preg_split('//u', mb_strtolower((string) $this->token), -1, PREG_SPLIT_NO_EMPTY);

            if ($characters !== false) {
                $subbed = count(array_filter($characters, static fn ($character) => (string) $character === (string) $substitution));
                $unsubbed = count(array_filter($characters, static fn ($character) => (string) $character === (string) $letter));

                if ($subbed === 0 || $unsubbed === 0) {
                    // for this sub, password is either fully subbed (444) or fully unsubbed (aaa)
                    // treat that as doubling the space (attacker needs to try fully subbed chars in addition to
                    // unsubbed.)
                    $variations *= 2;
                } else {
                    $possibilities = 0;
                    $min = min($subbed, $unsubbed);
                    for ($i = 1; $i <= $min; $i++) {
                        $possibilities += Binomial::binom($subbed + $unsubbed, $i);
                    }
                    $variations *= $possibilities;
                }
            }
        }
        return $variations;
    }
}
