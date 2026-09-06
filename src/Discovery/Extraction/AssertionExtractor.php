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
 *
 * Two things are gated here rather than inside each extractor, because both are facts about the
 * *file* and every extractor would otherwise have to re-derive them (ADR-A018):
 *
 * - a file that is not PHP is not read as PHP. `menu:\n  label: Menu` tokenises into identifiers
 *   like any other text, and `: Menu` satisfies the return-type rule, so a YAML file was producing
 *   assertions about classes named `Menu`;
 * - a file the diff **created** yields no own-file assertions. Every line of it is an added line, so
 *   its `use` block, its enclosing member and its siblings are already in front of the reviewer, and
 *   fetching them hands the diff back as context (ADR-A005). Its *external* references are extracted
 *   exactly as before — a new file may certainly pull context, just not its own.
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
            if (!$file->isPhp()) {
                continue;
            }

            $text = $source->text($file->path);

            if ($text === null) {
                continue;
            }

            foreach ($file->regions as $region) {
                foreach ($this->extractors as $extractor) {
                    if ($file->isNew && $extractor instanceof OwnFileAssertionExtractor) {
                        continue;
                    }

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
