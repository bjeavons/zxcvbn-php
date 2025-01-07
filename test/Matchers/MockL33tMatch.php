<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\L33tMatch;

class MockL33tMatch extends L33tMatch
{
    /**
     * @return array<string, mixed>
     */
    protected static function getRankedDictionaries(): array
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
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getL33tTable(): array
    {
        return [
            'a' => ['4', '@'],
            'c' => ['(', '{', '[', '<'],
            'g' => ['6', '9'],
            'o' => ['0'],
        ];
    }
}
