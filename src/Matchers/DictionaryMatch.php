<?php

namespace ZxcvbnPhp\Matchers;

class DictionaryMatch extends Match
{
    /**
     * @var
     */
    public $dictionaryName;

    /**
     * @var
     */
    public $rank;

    /**
     * @var
     */
    public $matchedWord;

    /**
     * @param $password
     * @param $begin
     * @param $end
     * @param $token
     * @param array $params
     */
    public function __construct($password, $begin, $end, $token, $params = [])
    {
        parent::__construct($password, $begin, $end, $token);
        $this->pattern = 'dictionary';
        if (!empty($params)) {
            $this->dictionaryName = isset($params['dictionary_name']) ? $params['dictionary_name'] : null;
            $this->matchedWord = isset($params['matched_word']) ? $params['matched_word'] : null;
            $this->rank = isset($params['rank']) ? $params['rank'] : null;
        }
    }

    /**
     * Match occurences of dictionary words in password.
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
        $matches = [];
        $dicts = static::getRankedDictionaries();
        if (!empty($userInputs)) {
            $dicts['user_inputs'] = [];
            foreach ($userInputs as $rank => $input) {
                $input_lower = strtolower($input);
                $dicts['user_inputs'][$input_lower] = $rank;
            }
        }
        foreach ($dicts as $name => $dict) {
            $results = static::dictionaryMatch($password, $dict);
            foreach ($results as $result) {
                $result['dictionary_name'] = $name;
                $matches[] = new static($password, $result['begin'], $result['end'], $result['token'], $result);
            }
        }

        return $matches;
    }

    /**
     * @return float
     */
    public function getEntropy()
    {
        return $this->log($this->rank) + $this->uppercaseEntropy();
    }

    /**
     * @return float
     */
    protected function uppercaseEntropy()
    {
        $token = $this->token;
        // Return if token is all lowercase.
        if ($token === strtolower($token)) {
            return 0;
        }

        $startUpper = '/^[A-Z][^A-Z]+$/';
        $endUpper = '/^[^A-Z]+[A-Z]$/';
        $allUpper = '/^[A-Z]+$/';
        // a capitalized word is the most common capitalization scheme, so it only doubles the search space
        // (uncapitalized + capitalized): 1 extra bit of entropy. allcaps and end-capitalized are common enough to
        // underestimate as 1 extra bit to be safe.
        foreach ([$startUpper, $endUpper, $allUpper] as $regex) {
            if (preg_match($regex, $token)) {
                return 1;
            }
        }

        // Otherwise calculate the number of ways to capitalize U+L uppercase+lowercase letters with U uppercase letters or
        // less. Or, if there's more uppercase than lower (for e.g. PASSwORD), the number of ways to lowercase U+L letters
        // with L lowercase letters or less.
        $uLen = 0;
        $lLen = 0;

        foreach (str_split($token) as $x) {
            $ord = ord($x);

            if ($this->isUpper($ord)) {
                ++$uLen;
            }
            if ($this->isLower($ord)) {
                ++$lLen;
            }
        }

        $possibilities = 0;
        foreach (range(0, min($uLen, $lLen) + 1) as $i) {
            $possibilities += $this->binom($uLen + $lLen, $i);
        }

        return $this->log($possibilities);
    }

    /**
     * Match password in a dictionary.
     *
     * @param string $password
     * @param array  $dict
     *
     * @return array
     */
    protected static function dictionaryMatch($password, $dict)
    {
        $result = [];
        $length = strlen($password);

        $pw_lower = strtolower($password);

        foreach (range(0, $length - 1) as $i) {
            foreach (range($i, $length - 1) as $j) {
                $word = substr($pw_lower, $i, $j - $i + 1);

                if (isset($dict[$word])) {
                    $result[] = [
                        'begin' => $i,
                        'end' => $j,
                        'token' => substr($password, $i, $j - $i + 1),
                        'matched_word' => $word,
                        'rank' => $dict[$word],
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Load ranked frequency dictionaries.
     *
     * @return array
     */
    protected static function getRankedDictionaries()
    {
        $data = file_get_contents(__DIR__.'/ranked_frequency_lists.json');

        return json_decode($data, true);
    }
}
