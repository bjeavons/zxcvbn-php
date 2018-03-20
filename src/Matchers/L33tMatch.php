<?php

namespace ZxcvbnPhp\Matchers;

/**
 * Class L33tMatch extends DictionaryMatch to translate l33t into dictionary words for matching.
 */
class L33tMatch extends DictionaryMatch
{
    /**
     * @var
     */
    public $sub;

    /**
     * @var
     */
    public $subDisplay;

    /**
     * @var
     */
    public $l33t;

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

    /**
     * Match occurences of l33t words in password to dictionary words.
     *
     * @copydoc Match::match()
     *
     * @param       $password
     * @param array $userInputs
     *
     * @return array
     */
    public static function match($password, array $userInputs = [])
    {
        // Translate l33t password and dictionary match the translated password.
        $map = static::getSubstitutions($password);
        $indexSubs = array_filter($map);
        if (empty($indexSubs)) {
            return [];
        }
        $translatedWord = static::translate($password, $map);

        $matches = [];
        $dicts = static::getRankedDictionaries();
        foreach ($dicts as $name => $dict) {
            $results = static::dictionaryMatch($translatedWord, $dict);
            foreach ($results as $result) {
                // Set substituted elements.
                $result['sub'] = [];
                $result['sub_display'] = [];
                foreach ($indexSubs as $i => $t) {
                    $result['sub'][$password[$i]] = $t;
                    $result['sub_display'][] = "{$password[$i]} -> ${t}";
                }
                $result['sub_display'] = implode(', ', $result['sub_display']);
                $result['dictionary_name'] = $name;
                // Replace translated token with orignal password token.
                $token = substr($password, $result['begin'], $result['end'] - $result['begin'] + 1);
                $matches[] = new static($password, $result['begin'], $result['end'], $token, $result);
            }
        }

        return $matches;
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
                    ++$sLen;
                }
                if ($char === (string) $unsubbed) {
                    ++$uLen;
                }
            }
            foreach (range(0, min($uLen, $sLen)) as $i) {
                $possibilities += $this->binom($uLen + $sLen, $i);
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
     * @param array  $map
     *
     * @return string
     */
    protected static function translate($string, $map)
    {
        $out = '';
        foreach (range(0, strlen($string) - 1) as $i) {
            $out .= !empty($map[$i]) ? $map[$i] : $string[$i];
        }

        return $out;
    }

    /**
     * @param string $password
     *
     * @return array
     */
    protected static function getSubstitutions($password)
    {
        $map = [];

        $l33t = [
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
        // Simplified l33t table to reduce duplicates.
        $l33t = [
            'a' => ['4', '@'],
            'b' => ['8'],
            'c' => ['(', '{', '[', '<'],
            'e' => ['3'],
            'g' => ['6', '9'],
            'i' => ['1', '!'],
            'l' => ['|', '7'],
            'o' => ['0'],
            's' => ['$', '5'],
            't' => ['+', '7'],
            'x' => ['%'],
            'z' => ['2'],
        ];

        /*$chars = array_unique(str_split($password));
        foreach ($l33t as $letter => $subs) {
            $relevent_subs = array_intersect($subs, $chars);
            if (!empty($relevent_subs)) {
                $map[] = $relevent_subs;
            }
        }*/

        foreach (range(0, strlen($password) - 1) as $i) {
            $map[$i] = null;
            foreach ($l33t as $char => $subs) {
                if (in_array($password[$i], $subs, true)) {
                    $map[$i] = $char;
                }
            }
        }

        return $map;
    }
}
