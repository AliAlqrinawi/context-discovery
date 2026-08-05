<?php

declare(strict_types=1);

namespace ContextDiscovery\Ports;

use ContextDiscovery\Domain\Source\CallSite;

/**
 * A bounded grep, never a graph.
 *
 * Reverse-caller is the recurring, high-severity move and the sharpest A-vs-C differential — and
 * it is also the expensive one. A crude grep is what the evidence earned; a maintained call graph
 * is not (R3, ADR-A006, ADR-002).
 *
 * Implementations run in-process: shelling out to grep or ripgrep would make output depend on the
 * host's installed tools and locale, against P8.
 */
interface CallSiteSearch
{
    /**
     * @param int $max Hard bound per changed signature. Exceeding it is recorded as a flag, never
     *                 a silent truncation (P10).
     *
     * @return list<CallSite> In filesUnder() order, then by line — required by P8, so the bounded
     *                        subset is identical on every machine.
     */
    public function callSites(string $methodName, string $scopePrefix, int $max): array;
}
