<?php

namespace ZxcvbnPhp;

class Scorer
{

  # ------------------------------------------------------------------------------
# threat model -- stolen hash catastrophe scenario -----------------------------
# ------------------------------------------------------------------------------
#
# assumes:
# * passwords are stored as salted hashes, different random salt per user.
#   (making rainbow attacks infeasable.)
# * hashes and salts were stolen. attacker is guessing passwords at max rate.
# * attacker has several CPUs at their disposal.
# ------------------------------------------------------------------------------

  # for a hash function like bcrypt/scrypt/PBKDF2, 10ms per guess is a safe lower bound.
  # (usually a guess would take longer -- this assumes fast hardware and a small work factor.)
  # adjust for your site accordingly if you use another hash function, possibly by
  # several orders of magnitude!
    const SINGLE_GUESS = 0.010;
    const NUM_ATTACKERS = 100; // number of cores guessing in parallel.

    /**
    * Return average time to crack based on entropy.
    *
    * @param $entropy
    * @return float
    */
    public static function crackTime($entropy)
    {
        return (0.5 * pow(2, $entropy)) * (Scorer::SINGLE_GUESS / Scorer::NUM_ATTACKERS);
    }

    /**
     * @param $seconds
     * @return int
     */
    public static function score($seconds)
    {
        if ($seconds < pow(10, 2)) {
            return 0;
        }
        if ($seconds < pow(10, 4)) {
            return 1;
        }
        if ($seconds < pow(10, 6)) {
            return 2;
        }
        if ($seconds < pow(10, 8)) {
            return 3;
        }
        return 4;
    }

}