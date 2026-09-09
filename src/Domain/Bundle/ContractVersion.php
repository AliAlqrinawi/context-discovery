<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

/**
 * The versions a bundle reports about the run that produced it.
 *
 * Declared, not discovered. The tool spawns no process (P9, X5), so a git tag is unreadable from
 * inside it, and `composer.json` carries no version field — a constant maintained by hand is the
 * only source that is both in-process and true.
 *
 * `POLICY` is a *guarded* constant: `PolicyVersionGuardTest` hashes the classes that decide lever,
 * band and premise, and fails when their content moves without this value moving. That makes the
 * bump mandatory rather than remembered — the discipline ADR-A024 requires after `KIND_ORDER` was
 * allowed to live in three places with nothing forcing agreement.
 *
 * The framework table's version is *not* here: it is derived from the table's own content by
 * `FrameworkKnowledge::tableVersion()`, because that one is pure data and can be hashed honestly.
 */
final class ContractVersion
{
    /**
     * The bundle schema. Bumped only by ADR-A024's compatibility policy — never for an additive
     * field, always for a removal, a rename, or a change of nesting.
     */
    public const BUNDLE = 2;

    /**
     * The engine that produced the bundle. Semantic; bumped with the release.
     */
    public const ENGINE = '0.2.0';

    /**
     * The decision rules: `LeverPolicy`, `ItemPriority`, `PremiseCatalogue`. Two runs sharing this
     * value banded, levered and flagged by the same rules, so a scored comparison across them is
     * comparing discovery rather than policy drift.
     */
    public const POLICY = '2';
}
