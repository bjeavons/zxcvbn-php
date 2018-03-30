<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\RepeatMatch;

class RepeatTest extends AbstractMatchTest
{
    public function testEmpty()
    {
        foreach(['', '#'] as $password) {
            $this->assertEmpty(
                RepeatMatch::match($password),
                "doesn't match length-".strlen($password)." repeat patterns"
            );
        }
    }
    
    public function testSingleCharacterRepeats()
    {
        $prefixes = ['@', 'y4@'];
        $suffixes = ['u', 'u%7'];
        $pattern = '&&&&&';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as $variant) {
            list($password, $i, $j) = $variant;

            $this->checkMatches(
                "matches embedded repeat patterns",
                RepeatMatch::match($password),
                'repeat',
                [$pattern],
                [[$i, $j]],
                [
                    'repeatedChar' => ['&']
                ]
            );
        }
    }
}
