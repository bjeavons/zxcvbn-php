<?php

namespace ZxcvbnPhp\Matchers;

/**
 * Class L33tMatch extends DictionaryMatch to translate l33t into dictionary words for matching.
 * @package ZxcvbnPhp\Matchers
 */
class L33tMatch extends DictionaryMatch
{

    /** @var array An array of substitutions made to get from the token to the dictionary word. */
    public $sub = [];

    /** @var string A user-readable string that shows which substitutions were detected. */
    public $subDisplay;

    /** @var bool Whether or not the token contained l33t substitutions. */
    public $l33t = true;

    /**
     * Match occurences of l33t words in password to dictionary words.
     *
     * @param string $password
     * @param array $userInputs
     * @param array $rankedDictionaries
     * @return L33tMatch[]
     */
    public static function match($password, array $userInputs = [], $rankedDictionaries = [])
    {
        // Translate l33t password and dictionary match the translated password.
        $maps = array_filter(static::getL33tSubstitutions(static::getL33tSubtable($password)));
        if (empty($maps)) {
            return [];
        }

        $matches = [];
        if (!$rankedDictionaries) {
            $rankedDictionaries = static::getRankedDictionaries();
        }

        foreach ($maps as $map) {
            $translatedWord = static::translate($password, $map);

            /** @var L33tMatch[] $results */
            $results = parent::match($translatedWord, $userInputs, $rankedDictionaries);
            foreach ($results as $match) {
                $token = substr($password, $match->begin, $match->end - $match->begin + 1);

                # only return the matches that contain an actual substitution
                if (strtolower($token) === $match->matchedWord) {
                    continue;
                }

                # filter single-character l33t matches to reduce noise.
                # otherwise '1' matches 'i', '4' matches 'a', both very common English words
                # with low dictionary rank.
                if (strlen($token) === 1) {
                    continue;
                }

                $display = [];
                foreach ($map as $i => $t) {
                    if (strpos($token, (string)$i) !== false) {
                        $match->sub[$i] = $t;
                        $display[] = "$i -> $t";
                    }
                }
                $match->token = $token;
                $match->subDisplay = implode(', ', $display);

                $matches[] = $match;
            }
        }

        return $matches;
    }

    /**
     * @param string $password
     * @param int $begin
     * @param int $end
     * @param string $token
     * @param array $params An array with keys: [sub, sub_display].
     */
    public function __construct($password, $begin, $end, $token, $params = [])
    {
        parent::__construct($password, $begin, $end, $token, $params);
        if (!empty($params)) {
            $this->sub = isset($params['sub']) ? $params['sub'] : null;
            $this->subDisplay = isset($params['sub_display']) ? $params['sub_display'] : null;
        }
    }

    public function getFeedback($isSoleMatch)
    {
        $feedback = parent::getFeedback($isSoleMatch);

        $feedback['suggestions'][] = "Predictable substitutions like '@' instead of 'a' don't help very much";

        return $feedback;
    }

    /**
     * @param string $string
     * @param array $map
     * @return string
     */
    protected static function translate($string, $map)
    {
        return str_replace(array_keys($map), array_values($map), $string);
    }

    protected static function getL33tTable()
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

    protected static function getL33tSubtable($password)
    {
        $passwordChars = array_unique(str_split($password));

        $subTable = [];

        $table = static::getL33tTable();
        foreach ($table as $letter => $substitutions) {
            foreach ($substitutions as $sub) {
                if (in_array($sub, $passwordChars)) {
                    $subTable[$letter][] = $sub;
                }
            }
        }

        return $subTable;
    }

    protected static function getL33tSubstitutions($subtable)
    {
        $result = [[]];
        foreach ($subtable as $letter => $substitutions) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($substitutions as $substitutedCharacter) {
                    $tmp[] = $result_item + [$substitutedCharacter => $letter];
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    public function getGuesses()
    {
        return parent::getGuesses() * $this->getL33tVariations();
    }

    protected function getL33tVariations()
    {
        $variations = 1;

        foreach ($this->sub as $substitution => $letter) {
            $characters = str_split(strtolower($this->token));

            $subbed = count(array_filter($characters, function ($character) use ($substitution) {
                return (string)$character === (string)$substitution;
            }));
            $unsubbed = count(array_filter($characters, function ($character) use ($letter) {
                return (string)$character === (string)$letter;
            }));

            if ($subbed === 0 || $unsubbed === 0) {
                // for this sub, password is either fully subbed (444) or fully unsubbed (aaa)
                // treat that as doubling the space (attacker needs to try fully subbed chars in addition to
                // unsubbed.)
                $variations *= 2;
            } else {
                $possibilities = 0;
                for ($i = 1; $i <= min($subbed, $unsubbed); $i++) {
                    $possibilities += static::binom($subbed + $unsubbed, $i);
                }
                $variations *= $possibilities;
            }
        }
        return $variations;
    }
}
