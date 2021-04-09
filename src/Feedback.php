<?php

namespace ZxcvbnPhp;

use ZxcvbnPhp\Matchers\MatchInterface;

/**
 * Feedback - gives some user guidance based on the strength
 * of a password
 *
 * @see zxcvbn/src/feedback.coffee
 */
class Feedback
{
    const FEEDBACK_CODE_EMPTY = 'empty';
    const FEEDBACK_CODE_COMMON = 'common';
    const FEEDBACK_CODE_COMMON_SIMILAR = 'common_similar';
    const FEEDBACK_CODE_COMMON_TOP_10 = 'common_top_10';
    const FEEDBACK_CODE_COMMON_TOP_100 = 'common_top_100';
    const FEEDBACK_CODE_GUESSABLE_DATES = 'guessable_dates';
    const FEEDBACK_CODE_GUESSABLE_NAME = 'guessable_name';
    const FEEDBACK_CODE_GUESSABLE_NAMES = 'guessable_names';
    const FEEDBACK_CODE_GUESSABLE_REPEATED_CHARACTER = 'guessable_repeated_character';
    const FEEDBACK_CODE_GUESSABLE_REPEATED_STRING = 'guessable_repeated_string';
    const FEEDBACK_CODE_GUESSABLE_SEQUENCE = 'guessable_sequence';
    const FEEDBACK_CODE_GUESSABLE_SPATIAL_ROW = 'guessable_spatial_row';
    const FEEDBACK_CODE_GUESSABLE_SPATIAL_PATTERN = 'guessable_spatial_pattern';
    const FEEDBACK_CODE_GUESSABLE_WORD = 'guessable_word';
    const FEEDBACK_CODE_GUESSABLE_YEARS = 'guessable_years';

    /**
     * @param int $score
     * @param MatchInterface[] $sequence
     * @return array
     */
    public function getFeedback($score, array $sequence)
    {
        // starting feedback
        if (count($sequence) === 0) {
            return [
                'code'        => static::FEEDBACK_CODE_EMPTY,
                'warning'     => '',
                'suggestions' => [
                    "Use a few words, avoid common phrases",
                    "No need for symbols, digits, or uppercase letters",
                ],
            ];
        }

        // no feedback if score is good or great.
        if ($score > 2) {
            return [
                'code'        => '',
                'warning'     => '',
                'suggestions' => [],
            ];
        }

        // tie feedback to the longest match for longer sequences
        $longestMatch = $sequence[0];
        foreach (array_slice($sequence, 1) as $match) {
            if (mb_strlen($match->token) > mb_strlen($longestMatch->token)) {
                $longestMatch = $match;
            }
        }

        $feedback = $longestMatch->getFeedback(count($sequence) === 1);
        $extraFeedback = 'Add another word or two. Uncommon words are better.';

        array_unshift($feedback['suggestions'], $extraFeedback);
        return $feedback;
    }
}
