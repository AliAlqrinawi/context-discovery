<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Source;

/**
 * The minimal fetched payload: the smallest slice that settles an assertion.
 *
 * Ideally one method or class member — not a whole file. A whole-file payload would fail the
 * spec's precision criterion and would turn Experiment 2's correct near-empty bundle into a
 * precision failure (ADR-A004, ADR-A005).
 *
 * The member name is absent for a slice that is not a member — a `use` block, or a call-site line.
 */
final class SourceSlice
{
    public function __construct(
        public readonly string $path,
        public readonly ?string $member,
        public readonly int $firstLine,
        public readonly int $lastLine,
        public readonly string $text,
    ) {
    }
}
