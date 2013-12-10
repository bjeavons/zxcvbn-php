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
     * Match occurences of dictionary words in password.
     *
     * @copydoc Match::match()
     */
    public static function match($password)
    {
        $matches = array();
        $dicts = self::getRankedDictionaries();
        foreach ($dicts as $name => $dict) {
            $results = self::dictionaryMatch($password, $dict);
            foreach ($results as $result) {
                $result['dictionary_name'] = $name;
                $matches[] = new self($password, $result['begin'], $result['end'], $result['token'], $result);
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
    public function __construct($password, $begin, $end, $token, $params = array())
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
     * @return float
     */
    public function getEntropy()
    {

    }

    /**
     * @param string $password
     * @param array $dict
     */
    protected static function dictionaryMatch($password, $dict)
    {
        $result = array();
        $length = strlen($password);

        $pw_lower = strtolower($password);

        foreach (range(0, $length) as $i) {
            foreach (range($i, $length ) as $j) {
                $word = substr($pw_lower, $i, $j + 1);

                if (isset( $dict[$word])) {
                    $result[] = array(
                        'begin' => $i,
                        'end' => $j,
                        'token' => substr($password, $i, $j + 1),
                        'matched_word' => $word,
                        'rank' => $dict[$word],
                    );
                }
            }
        }

        return $result;
    }

    protected static function getRankedDictionaries()
    {
        $data = file_get_contents(dirname(__FILE__) . '/ranked_frequency_lists.json');
        return json_decode($data, $assoc = TRUE);
    }
}