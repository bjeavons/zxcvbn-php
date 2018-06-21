<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\L33tMatch;

class MockL33tMatch extends L33tMatch
{
    protected static function getRankedDictionaries()
    {
        return [
            'words' => [
                'aac' => 1,
                'password' => 3,
                'paassword' => 4,
                'asdf0' => 5,
            ],
            'words2' => [
                'cgo' => 1,
            ]
        ];
    }

    protected static function getL33tTable()
    {
        return [
            'a' => ['4', '@'],
            'c' => ['(', '{', '[', '<'],
            'g' => ['6', '9'],
            'o' => ['0'],
        ];
    }
}
