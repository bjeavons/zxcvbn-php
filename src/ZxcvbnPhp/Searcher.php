<?php

namespace ZxcvbnPhp;

use ZxcvbnPhp\Matchers\Bruteforce;

class Searcher {

  /**
   * Find best match sequence and minimum entropy for a password and its matches.
   *
   * @param string $password
   *   Password.
   * @param array $matches
   *   Array of Match objects on the password.
   *
   * @return array
   *   Minimum entropy float and array of non-overlapping best Match objects.
   */
  public static function getMinimumEntropyMatchSequence($password, $matches) {

    $passwordLength = strlen($password);
    $entropyStack = array();
    // for the optimal sequence of matches up to k, holds the final match (match.end == k).
    // null means the sequence ends without a brute-force character.
    $backpointers = array();

    $bruteforceMatch = new Bruteforce($password, 0, $passwordLength, $password);
    $charEntropy = log($bruteforceMatch->getCardinality(), 2);

    foreach (range(0, $passwordLength - 1) as $k ) {
      // starting scenario to try and beat: adding a brute-force character to the minimum entropy sequence at k-1.
      $entropyStack[$k] = self::prevValue($entropyStack, $k) + $charEntropy;
      $backpointers[$k] = NULL;

      foreach ($matches as $match) {
        if (!isset($match->begin) || $match->end != $k ) {
          continue;
        }

        // See if entropy up to current + entropy of this match is less than the current minimum at k.
        $candidateEntropy = self::prevValue($entropyStack, $k) + $match->getEntropy();
        if ($candidateEntropy < $entropyStack[$k]) {
          $entropyStack[$k] = $candidateEntropy;
          $backpointers[$k] = $match;
        }
      }
    }

    // Walk backwards and decode the best sequence
    $matchSequence = array();
    $k = $passwordLength - 1;
    while ($k >= 0) {
      $match = $backpointers[$k];

      if ($match) {
        $matchSequence[] = $match;

        $k = $match->begin - 1;
      }
      else {
        $k -= 1;
      }
    }
    $matchSequence = array_reverse($matchSequence);

    $s = 0;
    $matchSequenceCopy = array();
    // Handle subtrings that weren't matched as bruteforce match.
    foreach ($matchSequence as $match) {
      if ($match->begin - $s > 0) {
        $matchSequenceCopy[] = self::makeBruteforceMatch($password, $s, $match->begin - 1);
      };

      $s = $match->end + 1;
      $matchSequenceCopy[] = $match;
    }

    if ($s < $passwordLength) {
      $matchSequenceCopy[] = self::makeBruteforceMatch($password, $s, $passwordLength - 1);
    }

    $matchSequence = $matchSequenceCopy;
    $minEntropy = $entropyStack[$passwordLength - 1];

    return array($minEntropy, $matchSequence);
  }

  /**
   * Helper function gets previous value in an array if set otherwise 0.
   *
   * @param array $array
   *   Array to search.
   * @param $index
   *   Index to get previous value from.
   *
   * @return mixed
   */
  protected static function prevValue($array, $index) {
    $index = $index - 1;
    return ($index < 0 || $index >= count($array)) ? 0 : $array[$index];
  }

  /**
   * Make a bruteforce match object for substring of password.
   *
   * @param string $password
   * @param int $begin
   * @param int $end
   *
   * @return Bruteforce match
   */
  protected static function makeBruteforceMatch($password, $begin, $end) {
    $match = new Bruteforce($password, $begin, $end, substr($password, $begin, $end + 1));
    // Set entropy in match.
    $match->getEntropy();
    return $match;
  }

}