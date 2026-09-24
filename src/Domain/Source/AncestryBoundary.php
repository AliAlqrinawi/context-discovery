<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Source;

/**
 * The walk stopped without finding the member, and where (ADR-A028 §6, fail-closed).
 *
 * `reason` is one of a closed set the resolver states from what it observed - a dependency
 * boundary, an unplaceable name, an unreadable file, an ambiguous declaration, a cycle - never a
 * judgement. `walked` lists every project type opened. This is a settled negative: it is
 * recorded on stderr and produces no item (ADR-A028 §4).
 */
final class AncestryBoundary
{
    public const DEPENDENCY = 'continues into a dependency, which was not walked';

    public const UNPLACEABLE = 'names an ancestor the map cannot place';

    public const UNREADABLE = 'an ancestor file could not be read';

    public const AMBIGUOUS = 'declared in more than one ancestor; not disambiguated';

    public const CYCLE = 'the ancestry revisits a type';

    public const CONFLICT_BLOCK = 'a trait use with insteadof/as is not interpreted';

    /**
     * @param list<string> $walked
     */
    public function __construct(
        public readonly string $callingClass,
        public readonly string $member,
        public readonly string $reason,
        public readonly ?string $at,
        public readonly array $walked,
    ) {
    }
}
