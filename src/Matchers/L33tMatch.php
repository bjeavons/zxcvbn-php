<?php

namespace ZxcvbnPhp\Matchers;

/**
 * Class L33tMatch extends DictionaryMatch to translate l33t into dictionary words for matching.
 * @package ZxcvbnPhp\Matchers
 */
class L33tMatch extends DictionaryMatch
{

    /**
     * @var array
     */
    public $sub = [];

    /**
     * @var string
     */
    public $subDisplay;

    /**
     * @var boolean
     */
    public $l33t = true;

    /**
     * Match occurences of l33t words in password to dictionary words.
     *
     * @copydoc Match::match()
     */
    public static function match($password, array $userInputs = [], $rankedDictionaries = null)
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
                if (strtolower($token) === $match->token) {
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
     * @param $password
     * @param $begin
     * @param $end
     * @param $token
     * @param array $params
     */
    public function __construct($password, $begin, $end, $token, $params = [])
    {
        parent::__construct($password, $begin, $end, $token, $params);
        $this->l33t = true;
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
     * @return float
     */
    public function getEntropy()
    {
        return parent::getEntropy() + $this->l33tEntropy();
    }

    /**
     * @return float
     */
    protected function l33tEntropy()
    {
        $possibilities = 0;
        foreach ($this->sub as $subbed => $unsubbed) {
            $sLen = 0;
            $uLen = 0;
            // Count occurences of substituted and unsubstituted characters in the token.
            foreach (str_split($this->token) as $char) {
                if ($char === (string) $subbed) {
                    $sLen++;
                }
                if ($char === (string) $unsubbed) {
                    $uLen++;
                }
            }
            foreach (range(0, min($uLen, $sLen)) as $i) {
                $possibilities += $this->binom($uLen + $sLen,  $i);
            }
        }

        // corner: return 1 bit for single-letter subs, like 4pple -> apple, instead of 0.
        if ($possibilities <= 1) {
            return 1;
        }
        return $this->log($possibilities);
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

    protected static function getL33tSubtable($password){
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
}
