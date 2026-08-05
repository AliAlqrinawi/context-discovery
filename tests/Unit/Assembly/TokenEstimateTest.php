<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Assembly;

use ContextDiscovery\Assembly\TokenEstimate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The ratio these tests pin is AA4 — an architectural assumption, not a measurement. They exist
 * so the constant cannot drift silently, not to claim the number is right.
 */
#[CoversClass(TokenEstimate::class)]
final class TokenEstimateTest extends TestCase
{
    private TokenEstimate $estimate;

    protected function setUp(): void
    {
        $this->estimate = new TokenEstimate();
    }

    #[DataProvider('textsAndEstimates')]
    public function testFourCharactersAreEstimatedAsOneToken(string $text, int $expected): void
    {
        self::assertSame($expected, $this->estimate->of($text));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function textsAndEstimates(): iterable
    {
        yield 'empty text costs nothing' => ['', 0];
        yield 'one character rounds up' => ['a', 1];
        yield 'exactly one token' => ['abcd', 1];
        yield 'a partial token rounds up' => ['abcde', 2];
        yield 'two tokens' => ['abcdefgh', 2];
        yield 'a hundred characters' => [str_repeat('a', 100), 25];
        yield 'newlines are characters too' => ["ab\ncd", 2];
    }

    public function testTheEstimateNeverRoundsAwayARemainder(): void
    {
        // Rounding down would let a bundle report less than it costs.
        for ($length = 1; $length <= 40; $length++) {
            self::assertSame(
                (int) ceil($length / 4),
                $this->estimate->of(str_repeat('x', $length)),
                sprintf('length %d', $length)
            );
        }
    }

    public function testEstimatingTheSameTextTwiceGivesTheSameNumber(): void
    {
        $text = "public function forItem(PlaidItem \$item)\n{\n    return static::query();\n}";

        self::assertSame($this->estimate->of($text), $this->estimate->of($text));
    }
}
