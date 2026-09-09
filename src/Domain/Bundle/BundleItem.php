<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

use InvalidArgumentException;

/**
 * One piece of evidence handed to the reviewer, belonging to exactly one assertion.
 *
 * **v2 moved the justification up.** An item used to carry its own `reason` and `assertionKind`;
 * both now live on the `BundleAssertion` this item points at, and the item carries the pointer.
 * That is a contract change, not a rename: twenty call sites for one changed member used to repeat
 * one sentence twenty times, and the thing being justified was the claim, not each line of
 * evidence for it (ADR-A024).
 *
 * P5 is preserved and strengthened rather than relaxed. Nothing may be shown to a reviewer without
 * a stated reason, so an item must name an assertion, and `Bundle` rejects one whose id resolves to
 * nothing. An orphan item is unjustified context — the exact defect P5 exists to forbid.
 *
 * The lever is enforced by the type system, as before.
 */
final class BundleItem
{
    /**
     * @param string $assertionId The `BundleAssertion` this item is evidence for. Required.
     * @param string $payload     For a fetched item, the minimal source slice — ideally one method
     *                            or class member, never a whole file (ADR-A005). For a flagged
     *                            item, the assumption sentence and nothing else.
     * @param int    $tokens      Estimated, not counted. See ADR-A007.
     *
     * @throws InvalidArgumentException if the assertion id is empty.
     */
    public function __construct(
        public readonly Lever $lever,
        public readonly string $assertionId,
        public readonly Provenance $provenance,
        public readonly string $payload,
        public readonly int $tokens,
    ) {
        if (trim($assertionId) === '') {
            throw new InvalidArgumentException(
                'A bundle item must name the assertion it is evidence for (P5). Context with no '
                . 'stated reason does not enter the bundle.'
            );
        }
    }
}
