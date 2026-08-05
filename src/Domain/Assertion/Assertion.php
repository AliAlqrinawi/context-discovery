<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Assertion;

use ContextDiscovery\Domain\Diff\ChangedRegion;

/**
 * Something the diff asserts but cannot prove.
 *
 * The core domain concept and the only currency of the pipeline's middle stages: the engine's
 * job is assertion-resolution, not file-finding (P1, ADR-002). There is deliberately no
 * "related file" type anywhere in this codebase.
 *
 * An assertion references its diff origin as a path plus a region — never a ChangedFile object
 * graph (02-project-structure.md §3).
 */
final class Assertion
{
    /**
     * @param string $subject The thing asserted about: a class name, a member name, a symbol, or
     *                        a premise identifier from the closed catalogue.
     * @param string $claim   Human-readable statement of what is being assumed. Becomes the
     *                        bundle item's `reason`, which is required on every item (P5).
     */
    public function __construct(
        public readonly AssertionKind $kind,
        public readonly string $subject,
        public readonly string $originPath,
        public readonly ChangedRegion $originRegion,
        public readonly string $claim,
    ) {
    }
}
