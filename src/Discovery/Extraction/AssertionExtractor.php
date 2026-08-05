<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Extraction;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Diff\Diff;
use ContextDiscovery\Ports\SourceRepository;

/**
 * Runs the extractors over every changed region in a fixed order and returns a deterministic,
 * de-duplicated list of assertions.
 *
 * The extractors are a closed set constructed explicitly in `Cli\Wiring` and handed here in
 * order. There is no registry, no discovery and no configuration (ADR-A003) — the order is
 * whatever Wiring passes, and it does not vary between runs (P8).
 *
 * A changed file whose current text cannot be read yields no assertions: a deleted file has no
 * current text, which is correct rather than missing. The pipeline reports unreadable paths on
 * stderr.
 */
final class AssertionExtractor
{
    /**
     * @param list<RegionAssertionExtractor> $extractors
     */
    public function __construct(private readonly array $extractors)
    {
    }

    /**
     * @return list<Assertion>
     */
    public function extract(Diff $diff, SourceRepository $source): array
    {
        $assertions = [];
        $seen = [];

        foreach ($diff->files as $file) {
            $text = $source->text($file->path);

            if ($text === null) {
                continue;
            }

            foreach ($file->regions as $region) {
                foreach ($this->extractors as $extractor) {
                    foreach ($extractor->forRegion($file, $region, $text) as $assertion) {
                        $key = sprintf(
                            '%s|%s|%s|%d',
                            $assertion->kind->value,
                            $assertion->subject,
                            $assertion->originPath,
                            $assertion->originRegion->firstLine,
                        );

                        if (isset($seen[$key])) {
                            continue;
                        }

                        $seen[$key] = true;
                        $assertions[] = $assertion;
                    }
                }
            }
        }

        return $assertions;
    }
}
