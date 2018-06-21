<?php

namespace ZxcvbnPhp\Matchers;

class ReverseDictionaryMatch extends DictionaryMatch
{
    /** @var bool Whether or not the matched word was reversed in the token. */
    public $reversed = true;

    /**
     * Match occurences of reversed dictionary words in password.
     *
     * @param $password
     * @param array $userInputs
     * @param array $rankedDictionaries
     * @return ReverseDictionaryMatch[]
     */
    public static function match($password, array $userInputs = [], $rankedDictionaries = [])
    {
        /** @var ReverseDictionaryMatch[] $matches */
        $matches = parent::match(strrev($password), $userInputs, $rankedDictionaries);
        foreach ($matches as $match) {
            $tempBegin = $match->begin;

            // Change the token, password and [begin, end] values to match the original password
            $match->token = strrev($match->token);
            $match->password = strrev($match->password);
            $match->begin = strlen($password) - 1 - $match->end;
            $match->end = strlen($password) - 1 - $tempBegin;
        }

        usort($matches, ['ZxcvbnPhp\Matcher', 'sortMatches']);
        return $matches;
    }
}
