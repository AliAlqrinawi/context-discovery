<?php

declare(strict_types=1);

namespace ContextDiscovery\Adapters\Search;

use ContextDiscovery\Domain\Source\CallSite;
use ContextDiscovery\Ports\CallSiteSearch;
use ContextDiscovery\Ports\SourceRepository;

/**
 * A bounded, in-process grep — never a call graph, never a subprocess (ADR-A006).
 *
 * Reverse-caller is the recurring, high-severity move and the sharpest A-vs-C differential, and it
 * is also the expensive one. R3 says a crude grep suffices and that a real call graph is not yet
 * earned, so this scans text and stops.
 *
 * - **In-process.** Shelling out to `grep` or `ripgrep` would make output depend on the host's
 *   installed tools and locale, against P8, and would put an external requirement inside a
 *   zero-dependency tool.
 * - **Ordered.** Files are read in the lexicographic order `filesUnder()` returns and lines in
 *   file order, so the bounded subset is identical on every machine (P8).
 * - **Stateless.** No index, no cache, no persisted graph (P9). Each call rescans.
 *
 * **Known imprecision, deliberately unfiltered.** A match is any occurrence of the identifier
 * followed by `(`, so results include a same-named method on an unrelated class — and the changed
 * method's own declaration. Both are recorded rather than filtered: the scored run is meant to
 * *measure* this imprecision, and filtering it would be the semantic resolution ADR-A006 rejects
 * as optimisation before measurement. Identifier boundaries are respected, so `deactivate(` is not
 * a match for `activate` — that would be an occurrence of a different name, not of this one.
 */
final class ScopedGrepCallSiteSearch implements CallSiteSearch
{
    public function __construct(private readonly SourceRepository $source)
    {
    }

    /**
     * @return list<CallSite>
     */
    public function callSites(string $methodName, string $scopePrefix, int $max): array
    {
        if ($max < 1 || $methodName === '') {
            return [];
        }

        $pattern = '/\b' . preg_quote($methodName, '/') . '\s*\(/';
        $found = [];

        foreach ($this->source->filesUnder($scopePrefix, 'php') as $path) {
            $text = $this->source->text($path);

            if ($text === null) {
                continue;
            }

            foreach (explode("\n", $text) as $index => $line) {
                if (preg_match($pattern, $line) !== 1) {
                    continue;
                }

                $found[] = new CallSite($path, $index + 1, rtrim($line));

                if (count($found) >= $max) {
                    return $found;
                }
            }
        }

        return $found;
    }
}
