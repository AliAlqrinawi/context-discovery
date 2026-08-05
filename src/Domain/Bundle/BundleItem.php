<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

use ContextDiscovery\Domain\Assertion\AssertionKind;
use InvalidArgumentException;

/**
 * One item handed to the reviewer.
 *
 * Every item is self-justifying: it carries the **reason** it was included (which reference or
 * assumption it resolves) and the **lever** that produced it. This is non-negotiable — the reason
 * field is what makes the bundle measurable, and measurability is the reason Phase 1 is being
 * built at all. An item without a reason is either waste or an untraceable guess; both are
 * defects, not warnings (P5, R4).
 *
 * The lever is enforced by the type system. The reason is enforced below.
 */
final class BundleItem
{
    /**
     * @param string $payload For a fetched item, the minimal source slice — ideally one method or
     *                        class member, never a whole file (ADR-A005). For a flagged item, the
     *                        assumption sentence and nothing else.
     * @param int    $tokens  Estimated, not counted. See ADR-A007.
     *
     * @throws InvalidArgumentException if the reason is empty.
     */
    public function __construct(
        public readonly Lever $lever,
        public readonly string $reason,
        public readonly AssertionKind $assertionKind,
        public readonly Provenance $provenance,
        public readonly string $payload,
        public readonly int $tokens,
    ) {
        if (trim($reason) === '') {
            throw new InvalidArgumentException(
                'A bundle item requires a non-empty reason: the reference or assumption it '
                . 'resolves (P5). An item without one does not enter the bundle.'
            );
        }
    }
}
