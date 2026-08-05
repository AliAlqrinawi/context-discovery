<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Assertion;

use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Source\SourceSlice;

/**
 * An assertion plus the lever that handled it and what that produced — the single value handed
 * from resolution to assembly.
 *
 * Fetched carries slices and no statement; flagged carries a statement and no slices. The two
 * named constructors are the only way to build one, so the pairing cannot be got wrong.
 */
final class ResolvedAssertion
{
    /**
     * @param list<SourceSlice> $slices
     */
    private function __construct(
        public readonly Assertion $assertion,
        public readonly Lever $lever,
        public readonly array $slices,
        public readonly ?string $statement,
    ) {
    }

    /**
     * @param list<SourceSlice> $slices
     */
    public static function fetched(Assertion $assertion, array $slices): self
    {
        return new self($assertion, Lever::Fetched, $slices, null);
    }

    /**
     * A resolver that returns nothing routes here rather than dropping the concern: the engine
     * fails toward flagging, never toward silence (P10).
     */
    public static function flagged(Assertion $assertion, string $statement): self
    {
        return new self($assertion, Lever::Flagged, [], $statement);
    }
}
