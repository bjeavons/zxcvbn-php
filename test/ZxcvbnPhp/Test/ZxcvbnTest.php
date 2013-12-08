<?php

namespace ZxcvbnPhp\Test;

use ZxcvbnPhp\Zxcvbn;

class ZxcvbnTest extends \PHPUnit_Framework_TestCase
{

  public function testZxcvbn() {
    $result = Zxcvbn::passwordStrength("");
    $this->assertEquals(0, $result['entropy'], "Empty password has entropy of 0");
    $this->assertEquals(0, $result['score'], "Empty password has score of 0");
  }
}