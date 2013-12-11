<?php

namespace ZxcvbnPhp;

use ZxcvbnPhp\Matchers\MatchInterface;

class Matcher
{

    /**
     * Get matches for a password.
     *
     * @param string $password
     *   Password string to match.
     * @return array
     *   Array of Match objects.
     */
    public function getMatches($password)
    {
        $matches = array();
        foreach ($this->getMatchers() as $matcher) {
            if ($matcher instanceof MatchInterface) {
                $matched = $matcher::match($password);
                if (is_array($matched) && !empty($matched)) {
                    $matches = array_merge($matches, $matched);
                }
            }
        }
        return $matches;
    }

    /**
     * Load available Match objects to match against a password.
     *
     * @return array
     */
    protected function getMatchers()
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
            'ZxcvbnPhp\Matchers\DictionaryMatch',
        );
    }
}