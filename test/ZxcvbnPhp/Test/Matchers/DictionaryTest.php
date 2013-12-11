<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\DictionaryMatch;

class DictionaryTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = 'kdncpqw';
        $matches = DictionaryMatch::match($password);
        $this->assertTrue(empty($matches), "No dictionary matches for '$password'");

        $password = 'password';
        $matches = DictionaryMatch::match($password);
        // 11 matches for "password" in english and password dictionaries.
        $this->assertEquals(11, count($matches), "Dictionary match found '$password'");
        $this->assertEquals('pass', $matches[0]->token);
        $this->assertEquals('passwords', $matches[0]->dictionaryName);

        $password = '8dll20BEN3lld0';
        $matches = DictionaryMatch::match($password);
        $this->assertEquals(2, count($matches), "Dictionary match found '$password'");
  }

    public function testEntropy()
    {
        $password = 'password';
        $matches = DictionaryMatch::match($password);
        // Match 0 is "pass" with rank 35.
        $this->assertEquals(log(35, 2), $matches[0]->getEntropy());
    }
}