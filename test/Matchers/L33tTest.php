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
        return array(
            'a' => ['4', '@'],
            'c' => ['(', '{', '[', '<'],
            'g' => ['6', '9'],
            'o' => ['0'],
        );
    }
}

class L33tTest extends AbstractMatchTest
{
    protected $testTable = [
        'a' => ['4', '@'],
        'c' => ['(', '{', '[', '<'],
        'g' => ['6', '9'],
        'o' => ['0'],
    ];

    public function testReducesL33tTable()
    {
        $this->markTestSkipped('Not implemented');

        $cases = [
            ''      => [] ,
            'abcdefgo123578!#$&*)]}>' => [] ,
            'a'     => [] ,
            '4'     => [
                'a' => ['4']
            ],
            '4@'    => [
                'a' => ['4', '@']
            ],
            '4({60' => [
                'a' => ['4'],
                'c'   => ['(','{'],
                'g' => ['6'],
                'o' => ['0']
            ],
        ];

        foreach ($cases as $pw => $expected) {
            $this->assertEquals(
                $expected,
                L33tMatch::getRelevantL33tSubtable($pw, self::$testTable),
                "reduces l33t table to only the substitutions that a password might be employing"
            );
        }
    }

    public function testEmptyString()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match(''),
            "doesn't match empty string"
        );
    }

    public function testPureDictionaryWords()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match('password'),
            "doesn't match pure dictionary words"
        );
    }

    public function testCommonL33tSubstitutions()
    {
        $cases = [
            [
                'password'        => 'p4ssword',
                'pattern'         => 'p4ssword',
                'word'            => 'password',
                'dictionary_name' => 'words',
                'rank'            => 3,
                'ij'              => [0, 7],
                'sub'             => ['4' => 'a']
            ],
            [
                'password'        => 'p@ssw0rd',
                'pattern'         => 'p@ssw0rd',
                'word'            => 'password',
                'dictionary_name' => 'words',
                'rank'            => 3,
                'ij'              => [0, 7],
                'sub'             => ['@' => 'a', '0' => 'o']
            ],
            [
                'password'        => 'aSdfO{G0asDfO',
                'pattern'         => '{G0',
                'word'            => 'cgo',
                'dictionary_name' => 'words2',
                'rank'            => 1,
                'ij'              => [5, 7],
                'sub'             => ['{' => 'c', '0' => 'o']
            ],
        ];

        foreach ($cases as $case) {
            $this->checkMatches(
                "matches against common l33t substitutions",
                MockL33tMatch::match($case['password']),
                'dictionary',
                [ $case['pattern'] ],
                [ $case['ij'] ],
                [
                    'l33t'           => [ true ],
                    'sub'            => [ $case['sub'] ],
                    'matchedWord'    => [ $case['word'] ],
                    'rank'           => [ $case['rank'] ],
                    'dictionaryName' => [ $case['dictionary_name'] ]
                ]
            );
        }
    }
}