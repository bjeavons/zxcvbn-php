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
        // @todo
        return array(
            'ZxcvbnPhp\Matchers\Digit',
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
            $matches = array_merge($matches, $matcher::match($password));
        }
        return $matches;
    }
}