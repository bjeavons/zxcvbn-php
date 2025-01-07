<?php

declare(strict_types=1);

namespace ZxcvbnPhp\Test\Math;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Math\Binomial;
use ZxcvbnPhp\Math\BinomialProvider;

class BinomialTest extends TestCase
{
    public static function binomialDataProvider(): Iterator
    {
        yield [     0,    0,           1.0 ];
        yield [     1,    0,           1.0 ];
        yield [     5,    0,           1.0 ];
        yield [     0,    1,           0.0 ];
        yield [     0,    5,           0.0 ];
        yield [     2,    1,           2.0 ];
        yield [     4,    2,           6.0 ];
        yield [    33,    7,     4272048.0 ];
        yield [   206,  202,    72867865.0 ];
        yield [     3,    5,           0.0 ];
        yield [ 29847,    2,   445406781.0 ];
        yield [    49,   12, 92263734836.0 ];
    }

    public function testHasProvider(): void
    {
        $this->assertNotEmpty(Binomial::getUsableProviderClasses());
    }

    public function testChosenProviderMatchesExpected(): void
    {
        $providerClasses = Binomial::getUsableProviderClasses();
        $provider = reset($providerClasses);
        $this->assertNotFalse($provider);

        $this->assertInstanceOf($provider, Binomial::getProvider());
    }

    #[DataProvider('binomialDataProvider')]
    public function testBinomialCoefficient(int $n, int $k, float $expected): void
    {
        foreach (Binomial::getUsableProviderClasses() as $providerClass) {
            $provider = new $providerClass();
            $this->assertInstanceOf(BinomialProvider::class, $provider);

            $value = $provider->binom($n, $k);
            $this->assertSame($expected, $value, "{$providerClass} returns expected result for ({$n}, {$k})");

            if ($k <= $n) {  // Behavior is undefined for $k > n; don't test that
                $flippedValue = $provider->binom($n, $n - $k);
                $this->assertSame($value, $flippedValue, "{$providerClass} is symmetrical");
            }
        }
    }
}
