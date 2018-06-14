<?php

namespace ZxcvbnPhp\Matchers;

class RepeatMatch extends Match
{
    const GREEDY_MATCH = '/(.+)\1+/';
    const LAZY_MATCH = '/(.+?)\1+/';
    const ANCHORED_LAZY_MATCH = '/^(.+?)\1+$/';

    public $baseMatches;
    public $baseGuesses;
    public $repeatCount;

    /**
     * @var
     */
    public $repeatedChar;

    /**
     * Match 3 or more repeated characters.
     *
     * @return RepeatMatch[]
     */
    public static function match($password, array $userInputs = array())
    {
        $matches = array();
        $lastIndex = 0;

        while ($lastIndex < strlen($password)) {
            $greedyMatches = self::findAll($password, self::GREEDY_MATCH, $lastIndex);
            $lazyMatches = self::findAll($password, self::LAZY_MATCH, $lastIndex);

            if (empty($greedyMatches)) {
                break;
            }

            if (strlen($greedyMatches[0][0]['token']) > strlen($lazyMatches[0][0]['token'])) {
                $match = $greedyMatches[0];
                preg_match(self::ANCHORED_LAZY_MATCH, $match[0]['token'], $anchoredMatch);
                $repeatedChar = $anchoredMatch[1];
            } else {
                $match = $lazyMatches[0];
                $repeatedChar = $match[1]['token'];
            }

            // @TODO: most_guessable_match_sequence not yet implemented. See Scorer::mostGuessableMatchSequence

            //  const base_analysis = scoring.most_guessable_match_sequence(
            //          base_token,
            //          this.omnimatch(base_token)
            //      );
            //  const base_matches = base_analysis.sequence;
            //  const base_guesses = base_analysis.guesses;

            $repeatCount = strlen($match[0]['token']) / strlen($repeatedChar);

            $matches[] = new static(
                $password,
                $match[0]['begin'],
                $match[0]['end'],
                $match[0]['token'],
                $repeatedChar,
                null,
                array(),
                $repeatCount
            );

            $lastIndex = $match[0]['end'] + 1;
        }

        return $matches;
    }

    public function getFeedback($isSoleMatch)
    {
        $warning = strlen($this->repeatedChar) == 1 
            ? 'Repeats like "aaa" are easy to guess'
            : 'Repeats like "abcabcabc" are only slightly harder to guess than "abc"';

        return array(
            'warning' => $warning,
            'suggestions' => array(
                'Avoid repeated words and characters'
            )
        );
    }

    /**
     * @param $password
     * @param $begin
     * @param $end
     * @param $token
     * @param $repeatedChar
     * @param $baseGuesses
     * @param $baseMatches
     * @param $repeatCount
     */
    public function __construct($password, $begin, $end, $token, $repeatedChar, $baseGuesses, $baseMatches, $repeatCount)
    {
        parent::__construct($password, $begin, $end, $token);
        $this->pattern = 'repeat';
        $this->repeatedChar = $repeatedChar;
        $this->baseGuesses = $baseGuesses;
        $this->baseMatches = $baseMatches;
        $this->repeatCount = $repeatCount;
    }

    /**
     * @return float
     */
    public function getEntropy()
    {
        if (is_null($this->entropy)) {
           $this->entropy = $this->log($this->getCardinality() * strlen($this->token));
        }
        return $this->entropy;
    }
}
