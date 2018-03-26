<?php

namespace ZxcvbnPhp;

/**
 * Feedback - gives some user guidance based on the strength
 * of a password
 *
 * @see zxcvbn/src/time_estimates.coffee
 */
class TimeEstimator
{
    public function estimateAttackTimes($guesses)
    {
        $crack_times_seconds = array(
            'online_throttling_100_per_hour' => $guesses / (100 / 3600),
            'online_no_throttling_10_per_second' => $guesses / 10,
            'offline_slow_hashing_1e4_per_second' => $guesses / 1e4,
            'offline_fast_hashing_1e10_per_second' => $guesses / 1e10
        );

        $crack_times_display = [];
        foreach ($crack_times_seconds as $scenario => $seconds) {
            $crack_times_display[$scenario] = $this->displayTime($seconds);
        }

        return array(
            'crack_times_seconds' => $crack_times_seconds,
            'crack_times_display' => $crack_times_display,
            'score'               => $this->guessesToScore($guesses)
        );
    }

    protected function guessesToScore($guesses)
    {
        $DELTA = 5;

        if ($guesses < 1e3 + $DELTA) {
            # risky password: "too guessable"
            return 0;
        }
        else if ($guesses < 1e6 + $DELTA) {
            # modest protection from throttled online attacks: "very guessable"
            return 1;
        }
        else if ($guesses < 1e8 + $DELTA) {
            # modest protection from unthrottled online attacks: "somewhat guessable"
            return 2;
        }
        else if ($guesses < 1e10 + $DELTA) {
            # modest protection from offline attacks: "safely unguessable"
            # assuming a salted, slow hash function like bcrypt, scrypt, PBKDF2, argon, etc
            return 3;
        }
        else {
            # strong protection from offline attacks under same scenario: "very unguessable"
            return 4;
        }
    }

    protected function displayTime($seconds)
    {

    }
}