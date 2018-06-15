<?php

namespace ZxcvbnPhp;

use ZxcvbnPhp\Matchers\Match;
use ZxcvbnPhp\Matchers\MatchInterface;

class Matcher
{

    /**
     * Get matches for a password.
     *
     * @see zxcvbn/src/matching.coffee::omnimatch
     *
     * @param string $password
     *   Password string to match.
     * @param array $userInputs
     *   Array of values related to the user (optional).
     * @code
     *   array('Alice Smith')
     * @endcode
     * @return Match[]
     *   Array of Match objects.
     */
    public function getMatches($password, array $userInputs = array())
    {
        $matches = array();
        foreach ($this->getMatchers() as $matcher) {
            $matched = $matcher::match($password, $userInputs);
            if (is_array($matched) && !empty($matched)) {
                $matches = array_merge($matches, $matched);
            }
        }
        return $matches;
    }

    /**
     * @param Match $a
     * @param Match $b
     */
    public static function sortMatches($a, $b)
    {
        if ($a->begin != $b->begin) {
            return $a->begin - $b->begin;
        } else {
            return $a->end - $b->end;
        }
    }

    /**
     * Load available Match objects to match against a password.
     *
     * @return array
     *   Array of classes implementing MatchInterface
     */
    protected function getMatchers()
    {
        // @todo change to dynamic
        return array(
            'ZxcvbnPhp\Matchers\DateMatch',
            'ZxcvbnPhp\Matchers\L33tMatch',
            'ZxcvbnPhp\Matchers\RepeatMatch',
            'ZxcvbnPhp\Matchers\SequenceMatch',
            'ZxcvbnPhp\Matchers\SpatialMatch',
            'ZxcvbnPhp\Matchers\YearMatch',
            'ZxcvbnPhp\Matchers\DictionaryMatch',
            'ZxcvbnPhp\Matchers\ReverseDictionaryMatch',
        );
    }
}
