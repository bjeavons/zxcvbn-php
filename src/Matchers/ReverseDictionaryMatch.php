<?php

namespace ZxcvbnPhp\Matchers;

class ReverseDictionaryMatch extends DictionaryMatch
{
    public $reversed = true;

    /**
     * Match occurences of reversed dictionary words in password.
     *
     * @inheritdoc
     */
    public static function match($password, array $userInputs = array(), $rankedDictionaries = null)
    {
        $matches = parent::match(strrev($password), $userInputs, $rankedDictionaries);
        /** @var ReverseDictionaryMatch $match */
        foreach ($matches as $match) {
            $match->reversed = true;
            $tempBegin = $match->begin;

            $match->token = strrev($match->token);
            $match->password = strrev($match->password);
            $match->begin = strlen($password) - 1 - $match->end;
            $match->end = strlen($password) - 1 - $tempBegin;
        }

        usort($matches, ['ZxcvbnPhp\Matcher', 'sortMatches']);
        return $matches;
    }
}
