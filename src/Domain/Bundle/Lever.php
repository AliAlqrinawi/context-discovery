<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

/**
 * Which of the two resolution mechanisms handled an assertion.
 *
 * Two levers, not one, chosen by cost: cheap, named, depth-one references are fetched;
 * expensive (reverse-graph) or unknowable (runtime, data-state) ones are flagged
 * (P2, fetch-vs-flag.md). Neither is treated as equivalent to the other in value — whether a
 * flag captures most of a fetch's worth was never measured, and the tool must not assume it.
 *
 * Required on every bundle item (P5).
 */
enum Lever: string
{
    case Fetched = 'fetched';
    case Flagged = 'flagged';
}
