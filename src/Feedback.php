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
        // For now, just return upstream's default feedback
        return array(
            'warning' => '',
            'suggestions' => array(
                "Use a few words, avoid common phrases",
                "No need for symbols, digits, or uppercase letters"
            )
        );
    }
}