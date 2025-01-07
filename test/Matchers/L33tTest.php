<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Matchers;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ZxcvbnPhp\Matchers\L33tMatch;
use ZxcvbnPhp\Matchers\BaseMatch;

class L33tTest extends AbstractMatchTest
{
    /**
     * @var array<string, mixed>
     */
    protected array $testTable = [
        'a' => ['4', '@'],
        'c' => ['(', '{', '[', '<'],
        'g' => ['6', '9'],
        'o' => ['0'],
    ];

    /**
     * Generally we only need to test the public interface of the matchers, but it can be useful
     * to occasionally test protected methods to ensure consistency with upstream.
     *
     * @param array<int, mixed> $args
     * @return array<string, mixed>
     */
    protected static function callProtectedMethod(string $name, array $args)
    {
        $class = new ReflectionClass(MockL33tMatch::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    public function testReducesL33tTable(): void
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
            $this->assertSame(
                $expected,
                static::callProtectedMethod('getL33tSubtable', [$pw]),
                "reduces l33t table to only the substitutions that a password might be employing"
            );
        }
    }

    public function testEnumeratesL33tSubstitutions(): void
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
            $this->assertSame(
                $case[1],
                static::callProtectedMethod('getL33tSubstitutions', [$case[0]]),
                "enumerates the different sets of l33t substitutions a password might be using"
            );
        }
    }

    public function testEmptyString(): void
    {
        $this->assertSame(
            [],
            L33tMatch::match(''),
            "doesn't match empty string"
        );
    }

    public function testPureDictionaryWords(): void
    {
        $this->assertSame(
            [],
            L33tMatch::match('password'),
            "doesn't match pure dictionary words"
        );
    }

    public function testPureDictionaryWordsWithL33tCharactersAfter(): void
    {
        $this->assertSame(
            [],
            L33tMatch::match('password4'),
            "doesn't match pure dictionary word with l33t characters after"
        );
    }

    public function testCapitalizedDictionaryWordsWithL33tCharactersAfter(): void
    {
        $this->assertSame(
            [],
            L33tMatch::match('Password4'),
            "doesn't match capitalized dictionary word with l33t characters after"
        );
    }

    /**
     * @return Iterator<int, mixed>
     */
    public static function commonCaseProvider(): Iterator
    {
        yield [
            'password'        => 'p4ssword',
            'pattern'         => 'p4ssword',
            'word'            => 'password',
            'dictionary_name' => 'words',
            'rank'            => 3,
            'ij'              => [0, 7],
            'sub'             => ['4' => 'a']
        ];
        yield [
            'password'        => 'p@ssw0rd',
            'pattern'         => 'p@ssw0rd',
            'word'            => 'password',
            'dictionary_name' => 'words',
            'rank'            => 3,
            'ij'              => [0, 7],
            'sub'             => ['@' => 'a', '0' => 'o']
        ];
        yield [
            'password'        => 'aSdfO{G0asDfO',
            'pattern'         => '{G0',
            'word'            => 'cgo',
            'dictionary_name' => 'words2',
            'rank'            => 1,
            'ij'              => [5, 7],
            'sub'             => ['{' => 'c', '0' => 'o']
        ];
    }

    /**
     * @param int[] $ij
     * @param string[] $substitutions
     */
    #[DataProvider('commonCaseProvider')]
    public function testCommonL33tSubstitutions(string $password, string $pattern, string $word, string $dictionary, int $rank, array $ij, array $substitutions): void
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

    public function testOverlappingL33tPatterns(): void
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

    public function testMultipleL33tSubstitutions(): void
    {
        $this->assertSame(
            [],
            MockL33tMatch::match('p4@ssword'),
            "doesn't match when multiple l33t substitutions are needed for the same letter"
        );
    }

    public function testSingleCharacterL33tWords(): void
    {
        $this->assertSame(
            [],
            L33tMatch::match('4 1 @'),
            "doesn't match single-character l33ted words"
        );
    }

    public function testSubstitutionSubsets(): void
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

        $this->assertSame(
            [],
            MockL33tMatch::match('4sdf0'),
            "doesn't match with subsets of possible l33t substitutions"
        );
    }

    /*
     * The character '1' can map to both 'i' and 'l' - there was previously a bug that prevented it from matching
     * against the latter
     */
    public function testSubstitutionOfCharacterL(): void
    {
        $this->checkMatches(
            "matches against overlapping l33t patterns",
            L33tMatch::match('marie1'),
            'dictionary',
            ['marie1', 'arie1'],
            [[0,5], [1,5]],
            [
                'l33t'           => [true, true],
                'sub'            => [['1' => 'l'], ['1' => 'l'],],
                'matchedWord'    => ['mariel', 'ariel'],
            ]
        );
    }

    public function testGuessesL33t(): void
    {
        $match = new L33tMatch('aaa@@@', 0, 5, 'aaa@@@', [
            'rank' => 32,
            'sub' => ['@' => 'a']
        ]);
        $expected = 32.0 * 41;    // rank * l33t variations
        $this->assertSame($expected, $match->getGuesses(), "guesses are doubled when word is reversed");
    }

    public function testGuessesL33tAndUppercased(): void
    {
        $match = new L33tMatch('AaA@@@', 0, 5, 'AaA@@@', [
            'rank' => 32,
            'sub' => ['@' => 'a']
        ]);
        $expected = 32.0 * 41 * 3;    // rank * l33t variations * uppercase variations
        $this->assertSame(
            $expected,
            $match->getGuesses(),
            "extra guesses are added for both capitalization and common l33t substitutions"
        );
    }

    /**
     * @return Iterator<int, mixed>
     */
    public static function variationsProvider(): Iterator
    {
        yield [ '',  1, [] ];
        yield [ 'a', 1, [] ];
        yield [ '4', 2, ['4' => 'a'] ];
        yield [ '4pple', 2, ['4' => 'a'] ];
        yield [ 'abcet', 1, [] ];
        yield [ '4bcet', 2, ['4' => 'a'] ];
        yield [ 'a8cet', 2, ['8' => 'b'] ];
        yield [ 'abce+', 2, ['+' => 't'] ];
        yield [ '48cet', 4, ['4' => 'a', '8' => 'b'] ];
        yield ['a4a4aa', /* binom(6, 2) */ 15 + /* binom(6, 1) */ 6, ['4' => 'a']];
        yield ['4a4a44', /* binom(6, 2) */ 15 + /* binom(6, 1) */ 6, ['4' => 'a']];
        yield ['a44att+', (/* binom(4, 2) */ 6 + /* binom(4, 1) */ 4) * /* binom(3, 1) */ 3, ['4' => 'a', '+' => 't']];
    }

    /**
     * @param string[] $substitutions
     */
    #[DataProvider('variationsProvider')]
    public function testGuessesL33tVariations(string $token, float $expectedGuesses, array $substitutions): void
    {
        $match = new L33tMatch($token, 0, strlen($token) - 1, $token, ['rank' => 1, 'sub' => $substitutions]);
        $this->assertSame(
            $expectedGuesses,
            $match->getGuesses(),
            "extra l33t guesses of $token is $expectedGuesses"
        );
    }

    /**
     * This test is not strictly needed as it's testing an internal detail, but it's included to match an upstream test.
     * @link https://github.com/dropbox/zxcvbn/blob/master/test/test-scoring.coffee#L357
     */
    public function testCapitalisationNotAffectingL33t(): void
    {
        $token = 'Aa44aA';
        $match = new L33tMatch($token, 0, strlen($token) - 1, $token, ['rank' => 1, 'sub' => ['4' => 'a']]);
        // binom(6, 2) + binom(6, 1)
        $expected = 15.0 + 6;

        $class = new ReflectionClass(L33tMatch::class);
        $method = $class->getMethod('getL33tVariations');
        $method->setAccessible(true);
        $actual = $method->invoke($match);

        $this->assertSame($expected, $actual, "capitalization doesn't affect extra l33t guesses calc");
    }

    public function testFeedback(): void
    {
        $token = 'univer5ity';
        $match = new L33tMatch($token, 0, strlen($token) - 1, $token, [
            'dictionary_name' => 'english_wikipedia',
            'rank' => 69,
            'sub' => ['5' => 's'],
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertSame(
            'A word by itself is easy to guess',
            $feedback['warning'],
            "l33t match didn't lose the original dictionary match warning"
        );
        $this->assertContains(
            'Predictable substitutions like \'@\' instead of \'a\' don\'t help very much',
            $feedback['suggestions'],
            "l33t match gives correct suggestion"
        );
    }

    public function testFeedbackTop100Password(): void
    {
        $token = 'hunt3r';
        $match = new L33tMatch($token, 0, strlen($token) - 1, $token, [
            'dictionary_name' => 'passwords',
            'rank' => 37,
            'sub' => ['3' => 'e'],
        ]);
        $feedback = $match->getFeedback(true);

        $this->assertSame(
            'This is similar to a commonly used password',
            $feedback['warning'],
            "l33t match doesn't give top-100 warning"
        );
    }
}
