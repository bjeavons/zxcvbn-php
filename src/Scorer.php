<?php

namespace ZxcvbnPhp;

/**
 * scorer - takes a list of potential matches, ranks and evaluates them,
 * and figures out how many guesses it would take to crack the password
 *
 * @see zxcvbn/src/scoring.coffee
 */
class Scorer implements ScorerInterface
{

    # ------------------------------------------------------------------------------
    # search --- most guessable match sequence -------------------------------------
    # ------------------------------------------------------------------------------
    #
    # takes a sequence of overlapping matches, returns the non-overlapping sequence with
    # minimum guesses. the following is a O(l_max * (n + m)) dynamic programming algorithm
    # for a length-n password with m candidate matches. l_max is the maximum optimal
    # sequence length spanning each prefix of the password. In practice it rarely exceeds 5 and the
    # search terminates rapidly.
    #
    # the optimal "minimum guesses" sequence is here defined to be the sequence that
    # minimizes the following function:
    #
    #    g = l! * Product(m.guesses for m in sequence) + D^(l - 1)
    #
    # where l is the length of the sequence.
    #
    # the factorial term is the number of ways to order l patterns.
    #
    # the D^(l-1) term is another length penalty, roughly capturing the idea that an
    # attacker will try lower-length sequences first before trying length-l sequences.
    #
    # for example, consider a sequence that is date-repeat-dictionary.
    #  - an attacker would need to try other date-repeat-dictionary combinations,
    #    hence the product term.
    #  - an attacker would need to try repeat-date-dictionary, dictionary-repeat-date,
    #    ..., hence the factorial term.
    #  - an attacker would also likely try length-1 (dictionary) and length-2 (dictionary-date)
    #    sequences before length-3. assuming at minimum D guesses per pattern type,
    #    D^(l-1) approximates Sum(D^i for i in [1..l-1]
    #
    # ------------------------------------------------------------------------------
    public function mostGuessableMatchSequence($password, $matches)
    {
        return array();
    }
}