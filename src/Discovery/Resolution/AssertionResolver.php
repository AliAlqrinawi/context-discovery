<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Resolution;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Source\SourceSlice;

/**
 * Implemented by the three resolvers only — OwnFile, NamedReference, Caller.
 *
 * Like `RegionAssertionExtractor`, this exists for uniform typing inside the pipeline and is not
 * an extension point. There is no `supports()` probe: dispatch is an explicit `match` on
 * `AssertionKind` in `Pipeline\DiscoverContext`, so every kind and its destination are visible in
 * one place (freeze review O3, ADR-A003).
 */
interface AssertionResolver
{
    /**
     * @return list<SourceSlice> An empty list means the caller must flag instead (P10).
     */
    public function resolve(Assertion $assertion): array;
}
