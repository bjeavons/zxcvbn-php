<?php

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Matcher;

class YearMatch extends BaseMatch
{

    public const NUM_YEARS = 119;

    public $pattern = 'regex';
    public $regexName = 'recent_year';

    /**
     * Match occurences of years in a password
     *
     * @param string $password
     * @param array $userInputs
     * @return YearMatch[]
     */
    public static function match($password, array $userInputs = [])
    {
        $matches = [];
        $groups = static::findAll($password, "/(19\d\d|200\d|201\d)/u");
        foreach ($groups as $captures) {
            $matches[] = new static($password, $captures[1]['begin'], $captures[1]['end'], $captures[1]['token']);
        }
        Matcher::usortStable($matches, [Matcher::class, 'compareMatches']);
        return $matches;
    }

    public function getFeedback($isSoleMatch)
    {
        return [
            'warning' => "Recent years are easy to guess",
            'suggestions' => [
                'Avoid recent years',
                'Avoid years that are associated with you',
            ]
        ];
    }

    protected function getRawGuesses()
    {
        $yearSpace = abs((int)$this->token - DateMatch::getReferenceYear());
        return max($yearSpace, DateMatch::MIN_YEAR_SPACE);
    }
}
