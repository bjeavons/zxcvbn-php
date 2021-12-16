<?php

declare(strict_types=1);

namespace ZxcvbnPhp;

/**
 * Feedback - gives some user guidance based on the strength
 * of a password
 *
 * @see zxcvbn/src/time_estimates.coffee
 */
class TimeEstimator
{
    /**
     * @param int|float $guesses
     * @return array
     */
    public function estimateAttackTimes(float $guesses): array
    {
        $crack_times_seconds = [
            'online_throttling_100_per_hour' => $guesses / (100 / 3600),
            'online_no_throttling_10_per_second' => $guesses / 10,
            'offline_slow_hashing_1e4_per_second' => $guesses / 1e4,
            'offline_fast_hashing_1e10_per_second' => $guesses / 1e10
        ];

        $crack_times_display = array_map(
            [ $this, 'displayTime' ],
            $crack_times_seconds
        );

        return [
            'crack_times_seconds' => $crack_times_seconds,
            'crack_times_display' => $crack_times_display,
            'score'               => $this->guessesToScore($guesses)
        ];
    }

    protected function guessesToScore(float $guesses): int
    {
        $DELTA = 5;

        if ($guesses < 1e3 + $DELTA) {
            # risky password: "too guessable"
            return 0;
        }

        if ($guesses < 1e6 + $DELTA) {
            # modest protection from throttled online attacks: "very guessable"
            return 1;
        }

        if ($guesses < 1e8 + $DELTA) {
            # modest protection from unthrottled online attacks: "somewhat guessable"
            return 2;
        }

        if ($guesses < 1e10 + $DELTA) {
            # modest protection from offline attacks: "safely unguessable"
            # assuming a salted, slow hash function like bcrypt, scrypt, PBKDF2, argon, etc
            return 3;
        }

        # strong protection from offline attacks under same scenario: "very unguessable"
        return 4;
    }

    protected function displayTime(float $seconds): string
    {
        $minute = 60;
        $hour = $minute * 60;
        $day = $hour * 24;
        $month = $day * 31;
        $year = $month * 12;
        $century = $year * 100;

        if ($seconds < 1) {
            return dgettext("ZxcvbnPhp", 'less than a second');
        }

        if ($seconds < $minute) {
            $base = intval(round($seconds));
            return sprintf(dngettext("ZxcvbnPhp", "%d second", "%d seconds", $base), $base);
        }

        if ($seconds < $hour) {
            $base = intval(round($seconds / $minute));
            return sprintf(dngettext("ZxcvbnPhp", "%d minute", "%d minutes", $base), $base);
        }

        if ($seconds < $day) {
            $base = intval(round($seconds / $hour));
            return sprintf(dngettext("ZxcvbnPhp", "%d hour", "%d hours", $base), $base);
        }

        if ($seconds < $month) {
            $base = intval(round($seconds / $day));
            return sprintf(dngettext("ZxcvbnPhp", "%d day", "%d days", $base), $base);
        }

        if ($seconds < $year) {
            $base = intval(round($seconds / $month));
            return sprintf(dngettext("ZxcvbnPhp", "%d month", "%d months", $base), $base);
        }

        if ($seconds < $century) {
            $base = intval(round($seconds / $year));
            return sprintf(dngettext("ZxcvbnPhp", "%d year", "%d years", $base), $base);
        }

        return dgettext("ZxcvbnPhp", "centuries");
    }
}
