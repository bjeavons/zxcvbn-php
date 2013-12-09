<?php

namespace ZxcvbnPhp\Matchers;

interface MatchInterface
{

  public static function match($password);

  public function getEntropy();
}