<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Diff;

/**
 * A class member touched by the diff, with its old and new signature.
 *
 * The old signature comes from the hunk's removed lines. A rename is treated as two members and
 * is not tracked (01-architecture.md §3.3).
 *
 * A member the diff adds has no old signature; a member the diff removes has no new signature.
 */
final class ChangedMember
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $oldSignature,
        public readonly ?string $newSignature,
    ) {
    }
}
