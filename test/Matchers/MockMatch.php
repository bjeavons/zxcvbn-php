<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\Match;

class MockMatch extends Match
{
    protected $guesses;

    public function __construct($begin, $end, $guesses)
    {
        parent::__construct('', $begin, $end, '');
        $this->guesses = $guesses;
    }

    /**
     * Get feedback to a user based on the match.
     * @param  bool $isSoleMatch
     *   Whether this is the only match in the password
     * @return array
     *   Associative array with warning (string) and suggestions (array of strings)
     */
    public function getFeedback($isSoleMatch)
    {
        return [];
    }

    /**
     * @return integer
     */
    public function getRawGuesses()
    {
        return $this->guesses;
    }
}
