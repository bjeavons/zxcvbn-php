<?php

namespace ZxcvbnPhp\Matchers;

class DictionaryMatch extends Match
{

    public $pattern = 'dictionary';

    /** @var string The name of the dictionary that the token was found in. */
    public $dictionaryName;

    /** @var int The rank of the token in the dictionary. */
    public $rank;

    /** @var string The word that was matched from the dictionary. */
    public $matchedWord;

    /** @var bool Whether or not the matched word was reversed in the token. */
    public $reversed = false;

    /** @var bool Whether or not the token contained l33t substitutions. */
    public $l33t = false;

    /**
     * Match occurences of dictionary words in password.
     *
     * @param string $password
     * @param array $userInputs
     * @param array $rankedDictionaries
     * @return DictionaryMatch[]
     */
    public static function match($password, array $userInputs = [], $rankedDictionaries = [])
    {
        $matches = [];
        if ($rankedDictionaries) {
            $dicts = $rankedDictionaries;
        } else {
            $dicts = static::getRankedDictionaries();
        }

        if (!empty($userInputs)) {
            $dicts['user_inputs'] = [];
            foreach ($userInputs as $rank => $input) {
                $input_lower = strtolower($input);
                $dicts['user_inputs'][$input_lower] = $rank + 1; // rank starts at 1, not 0
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
     * @param string $password
     * @param int $begin
     * @param int $end
     * @param string $token
     * @param array $params An array with keys: [dictionary_name, matched_word, rank].
     */
    public function __construct($password, $begin, $end, $token, array $params = [])
    {
        parent::__construct($password, $begin, $end, $token);
        if (!empty($params)) {
            $this->dictionaryName = isset($params['dictionary_name']) ? $params['dictionary_name'] : null;
            $this->matchedWord = isset($params['matched_word']) ? $params['matched_word'] : null;
            $this->rank = isset($params['rank']) ? $params['rank'] : null;
        }
    }

    public function getFeedback($isSoleMatch)
    {
        $startUpper = '/^[A-Z][^A-Z]+$/';
        $allUpper = '/^[A-Z]+$/';

        $feedback = [
            'warning' => $this->getFeedbackWarning($isSoleMatch),
            'suggestions' => []
        ];

        if (preg_match($startUpper, $this->token)) {
            $feedback['suggestions'][] = "Capitalization doesn't help very much";
        } elseif (preg_match($allUpper, $this->token) && strtolower($this->token) != $this->token) {
            $feedback['suggestions'][] = "All-uppercase is almost as easy to guess as all-lowercase";
        }

        return $feedback;
    }

    public function getFeedbackWarning($isSoleMatch)
    {
        switch ($this->dictionaryName) {
            case 'passwords':
                if ($isSoleMatch /*and not match.l33t and not match.reversed */) { // This will be handled better in PHP because l33t and reverse will be subclasses
                    if ($this->rank <= 10) {
                        return 'This is a top-10 common password';
                    } elseif ($this->rank <= 10) {
                        return 'This is a top-100 common password';
                    } else {
                        return 'This is a very common password';
                    }
                } elseif ($this->guesses_log10 <= 4) { // guesses_log10 isn't a concept yet in PHP-land
                    return 'This is similar to a commonly used password';
                }
                break;
            case 'english_wikipedia':
                if ($isSoleMatch) {
                    return 'A word by itself is easy to guess';
                }
                break;
            case 'surnames':
            case 'male_names':
            case 'female_names':
                if ($isSoleMatch) {
                    return 'Names and surnames by themselves are easy to guess';
                } else {
                    return 'Common names and surnames are easy to guess';
                }
                break;
        }

        return '';
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
                $uLen += 1;
            }
            if ($this->isLower($ord)) {
                $lLen += 1;
            }
        }

        $possibilities = 0;
        foreach (range(0, min($uLen, $lLen) + 1) as $i) {
            $possibilities += $this->binom($uLen + $lLen, $i);
        }

        return $this->log($possibilities);
    }

    /**
     * Attempts to find the provided password (as well as all possible substrings) in a dictionary.
     *
     * @param string $password
     * @param array $dict
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
        $json = file_get_contents(dirname(__FILE__) . '/frequency_lists.json');
        $data = json_decode($json, true);

        $rankedLists = [];
        foreach ($data as $name => $words) {
            $rankedLists[$name] = array_combine($words, range(1, count($words)));
        }

        return $rankedLists;
    }
}
