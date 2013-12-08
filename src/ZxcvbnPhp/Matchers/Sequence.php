<?php

namespace ZxcvbnPhp\Matchers;

use ZxcvbnPhp\Matchers\Match;

class Sequence extends Matcher {

  protected function matchPattern($pattern) {
    return $pattern;
  }

  public function match($password) {
    $groups = $this->group($password);
    $result = array();
    $i = 0;
    foreach ( $groups as $i => $group )
    {
      $group = str_split($group);
      $char = $group[0];
      $length = count($group);

      if ( $length > 2 )
      {
        $j = $i + $length - 1;

        $pattern = array(
          'pattern' => 'repeat',
          'i' => $i,
          'j' => $j,
          'token' => substr( $password, $i, $j + 1 ),
          'repeated_char' => $char,
        );
        $result[] = $this->matchPattern($pattern);
      };

      $i += $length;
    };
    return $result;
  }

  public function entropy($match) {

  }

  /**
   * Group by characters
   *
   * @param $password
   * @return array
   */
  protected function group($password) {
    $grouped = array();

    $password_chars = str_split( $password );

    $prev_char = NULL;
    $index = NULL;

    foreach ( $password_chars as $char )
    {
      if ( $prev_char === $char )
      {
        $grouped[$index] .= $char;
      }
      else
      {
        $index = ( is_null( $index ) ? 0 : $index + 1 );

        $grouped[$index] = $char;
      };
    };

    return $grouped;
  }
}