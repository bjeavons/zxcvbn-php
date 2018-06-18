<?php

namespace ZxcvbnPhp\Test\Matchers;

use ReflectionClass;

class L33tTest extends AbstractMatchTest
{
    protected $testTable = [
        'a' => ['4', '@'],
        'c' => ['(', '{', '[', '<'],
        'g' => ['6', '9'],
        'o' => ['0'],
    ];

    // Generally we only need to test the public interface of the matchers, but it can be useful
    // to occasionally test protected methods to ensure consistency with upstream.
    protected static function callProtectedMethod($name, $args) {
        $class = new ReflectionClass(MockL33tMatch::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    public function testReducesL33tTable()
    {
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
                static::callProtectedMethod('getL33tSubtable', [$pw]),
                "reduces l33t table to only the substitutions that a password might be employing"
            );
        }
    }

    public function testEnumeratesL33tSubstitutions()
    {
        $cases = [
            [
                [],
                [[]]
            ],
            [
                ['a' => ['@']],     // subtable
                [['@' => 'a']] ],   // expected result
            [
                ['a' => ['@', '4']],
                [['@' => 'a'], ['4' => 'a']] ],
            [
                ['a' => ['@', '4'], 'c' => ['(']],
                [['@' => 'a', '(' => 'c'], ['4' => 'a', '(' => 'c']]
            ]
        ];

        foreach ($cases as $case) {
            $this->assertEquals(
                $case[1],
                static::callProtectedMethod('getL33tSubstitutions', [$case[0]]),
                "enumerates the different sets of l33t substitutions a password might be using"
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

    public function testPureDictionaryWordsWithL33tCharactersAfter()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match('password4'),
            "doesn't match pure dictionary word with l33t characters after"
        );
    }

    public function commonCaseProvider()
    {
        return [
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
    }

    /**
     * @dataProvider commonCaseProvider
     * @param string $password
     * @param string $pattern
     * @param string $word
     * @param string $dictionary
     * @param int $rank
     * @param int[] $ij
     * @param array $substitutions
     */
    public function testCommonL33tSubstitutions($password, $pattern, $word, $dictionary, $rank, $ij, $substitutions)
    {
        $this->checkMatches(
            "matches against common l33t substitutions",
            MockL33tMatch::match($password),
            'dictionary',
            [$pattern],
            [$ij],
            [
                'l33t' => [true],
                'sub' => [$substitutions],
                'matchedWord' => [$word],
                'rank' => [$rank],
                'dictionaryName' => [$dictionary]
            ]
        );
    }

    public function testOverlappingL33tPatterns()
    {
        $this->checkMatches(
            "matches against overlapping l33t patterns",
            MockL33tMatch::match('@a(go{G0'),
            'dictionary',
            ['@a(', '(go', '{G0'],
            [[0,2], [2,4], [5,7]],
            [
                'l33t'           => [true, true, true],
                'sub'            => [
                                        ['@' => 'a', '(' => 'c'],
                                        ['(' => 'c'],
                                        ['{' => 'c', '0' => 'o']
                                    ],
                'matchedWord'    => ['aac', 'cgo', 'cgo'],
                'rank'           => [1, 1, 1],
                'dictionaryName' => ['words', 'words2', 'words2'],
            ]
        );
    }

    public function testMultipleL33tSubstitutions()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match('p4@ssword'),
            "doesn't match when multiple l33t substitutions are needed for the same letter"
        );
    }

    public function testSingleCharacterL33tWords()
    {
        $this->assertEquals(
            [],
            MockL33tMatch::match('4 1 @'),
            "doesn't match single-character l33ted words"
        );
    }

    public function testSubstitutionSubsets()
    {
        // From the coffeescript source:
        // 
        // # known issue: subsets of substitutions aren't tried.
        // # for long inputs, trying every subset of every possible substitution could quickly get large,
        // # but there might be a performant way to fix.
        // # (so in this example: {'4': a, '0': 'o'} is detected as a possible sub,
        // # but the subset {'4': 'a'} isn't tried, missing the match for asdf0.)
        // # TODO: consider partially fixing by trying all subsets of size 1 and maybe 2
        // msg = "doesn't match with subsets of possible l33t substitutions"
        // t.deepEqual lm('4sdf0'), [], msg

        $this->assertEquals(
            [],
            MockL33tMatch::match('4sdf0'),
            "doesn't match with subsets of possible l33t substitutions"
        );
    }
}
