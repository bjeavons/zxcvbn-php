<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\DictionaryMatch;

class DictionaryTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $password = 'password';
        $matches = DictionaryMatch::match($password);
        var_export($matches);
        $this->assertEquals(10, count($matches), "Dictionary match found '$password'");

  }

    public function testEntropy()
    {
        $password = 'password';
        $matches = DictionaryMatch::match($password);
        //$this->assertEquals(0, $matches[0]->getEntropy());
    }
}