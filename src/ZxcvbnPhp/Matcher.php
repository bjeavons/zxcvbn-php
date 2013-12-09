<?php

namespace ZxcvbnPhp;

class Matcher
{

    /**
     * Load available Match objects to match against a password.
     *
     * @return array
     */
    protected static function getMatchers()
    {
        // @todo change to dynamic
        return array(
            'ZxcvbnPhp\Matchers\DateMatch',
            'ZxcvbnPhp\Matchers\DigitMatch',
            'ZxcvbnPhp\Matchers\L33tMatch',
            'ZxcvbnPhp\Matchers\RepeatMatch',
            'ZxcvbnPhp\Matchers\SequenceMatch',
            'ZxcvbnPhp\Matchers\SpatialMatch',
            'ZxcvbnPhp\Matchers\YearMatch',
            //'ZxcvbnPhp\Matchers\DictionaryMatch',
        );
    }

    /**
     * Get matches for a password.
     *
     * @param string $password
     *   Password string to match.
     * @return array
     *   Array of Match objects.
     */
    public static function getMatches($password)
    {
        $matches = array();
        foreach (self::getMatchers() as $matcher) {
            if (method_exists($matcher, 'match')) {
                $matches = array_merge($matches, $matcher::match($password));
            }
        }
        return $matches;
    }
}