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

    $timeStart = microtime(TRUE);
    if (strlen($password) === 0) {
      $timeStop = microtime(TRUE) - $timeStart;
      return self::result($password, 0, array(), 0, 0, array('calc_time' => $timeStop));
    }

    // Get matches for $password
    $matches = Matcher::getMatches($password);

    // Calcuate minimum entropy and best match sequence.
    list($entropy, $bestMatches) = Searcher::getMinimumEntropyMatchSequence($password, $matches);

    // Calculate score and crack time.
    $crackTime = Scorer::crackTime($entropy);
    $score = Scorer::score($crackTime);

    $timeStop = microtime(TRUE) - $timeStart;
    return self::result($password, $entropy, $bestMatches, $crackTime, $score, array('calc_time' => $timeStop));
  }

  /**
   * Result array.
   *
   * @param string $password
   * @param float $entropy
   * @param array $matches
   * @param float $crackTime
   * @param int $score
   * @param array $params
   *
   * @return array
   */
  protected static function result($password, $entropy, $matches, $crackTime, $score, $params = array()) {
    $r = array(
      'password'       => $password,
      'entropy'        => $entropy,
      'match_sequence' => $matches,
      'crack_time'     => $crackTime,
      'score'          => $score
    );
    return array_merge($r, $params);
  }

}