<?php

namespace ZxcvbnPhp;

/**
 * Feedback - gives some user guidance based on the strength
 * of a password
 *
 * @see zxcvbn/src/feedback.coffee
 */
class Feedback
{
    public function getFeedback($score, $sequence)
    {
        // starting feedback
        if (count($sequence) == 0) {
            return array(
                'warning' => '',
                'suggestions' => array(
                    "Use a few words, avoid common phrases",
                    "No need for symbols, digits, or uppercase letters"
                )
            );
        }

        // no feedback if score is good or great.
        if ($score > 2) {
            return array(
                'warning' => '',
                'suggestions' => array()
            );
        }

        // tie feedback to the longest match for longer sequences
        $longestMatch = $sequence[0];
        foreach(array_slice($sequence, 1) as $match) {
            if (strlen($match->token) > strlen($longestMatch->token)) {
                $longestMatch = $match;
            }
        }

        $feedback = $longestMatch->getFeedback(count($sequence) == 1);
        $extraFeedback = 'Add another word or two. Uncommon words are better.';

        if ($feedback) {
            return array(
                'warning' => $feedback['warning'] ?: '', // this seems unnecessary...
                'suggestions' => array_merge(
                    array($extraFeedback),
                    $feedback['suggestions']
                )
            );
        }

        return array(
            'warning' => '',
            'suggestions' => array($extraFeedback)
        );
    }
}