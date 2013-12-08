<?php

namespace ZxcvbnPhp;

class Zxcvbn {

  /**
   * Calculate password strength via non-overlapping minimum entropy patterns.
   *
   * @param string $password
   *   Password to measure.
   * @param array $userInputs
   *   Optional user inputs.
   *
   * @return array
   *   Strength result array with keys:
   *     password
   *     entropy
   *     match_sequence
   *     crack_time
   *     score
   */
  public static function passwordStrength($password, array $userInputs = array()) {

    if (strlen($password) === 0) {
      return self::result($password, 0, array(), 0, 0);
    }

    // Get matches for $password
    $matches = Matcher::getMatches($password);

    // Calcuate minimum entropy and best match sequence.
    list($entropy, $bestMatches) = Searcher::getMinimumEntropyMatchSequence($password, $matches);

    // Calculate score and crack time.
    $crackTime = Scorer::crackTime($entropy);
    $score = Scorer::score($crackTime);

    return self::result($password, $entropy, $bestMatches, $crackTime, $score);
  }

  /**
   * @param $password
   * @param $entropy
   * @param $matches
   * @param $crackTime
   * @param $score
   * @return array
   */
  protected static function result($password, $entropy, $matches, $crackTime, $score) {
    return array(
      'password'       => $password,
      'entropy'        => $entropy,
      'match_sequence' => $matches,
      'crack_time'     => $crackTime,
      'score'          => $score
    );
  }

}