<?php

namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\Scorer;

class ScorerTest extends \PHPUnit_Framework_TestCase
{

  public function testCrackTime() {
    $this->assertEquals(0.0128, Scorer::crackTime(8), 'Correct crack time for entropy of 8');
  }

  public function testScore() {
    $this->assertEquals(0, Scorer::score(0), '0 seconds to crack is a score of 0');
  }
}