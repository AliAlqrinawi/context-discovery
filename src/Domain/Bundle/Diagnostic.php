<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

use InvalidArgumentException;

/**
 * A machine-readable copy of something the run reported on stderr.
 *
 * **A mirror, not a move.** stderr keeps emitting byte-identically: freeze review 05 made that
 * stream load-bearing — it is how a harness tells "searched, found none" from "never searched" —
 * and `baseline-v0.1.0.json` records the exact lines per scenario. Relocating them would have
 * broken both.
 *
 * **Diagnostics carry no tokens and are never counted.** Freeze review L2 rejected diagnostics in
 * the bundle because they would make the artifact larger than the `used_tokens` number describing
 * it. Excluding them from the accounting answers that objection exactly: `used_tokens` still sums
 * the items and nothing else (ADR-A024).
 *
 * `detail` is a flat map of scalars — the facts the stderr line renders as prose, so a consumer
 * reads values instead of parsing English.
 */
final class Diagnostic
{
    /**
     * @param string                     $assertionId The assertion this concerns, or '' when the
     *                                                diagnostic belongs to the run rather than to
     *                                                any one claim.
     * @param array<string, string|int>  $detail
     *
     * @throws InvalidArgumentException if the type is empty.
     */
    public function __construct(
        public readonly string $type,
        public readonly string $assertionId,
        public readonly array $detail = [],
    ) {
        if (trim($type) === '') {
            throw new InvalidArgumentException('A diagnostic requires a type.');
        }
    }
}
