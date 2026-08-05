<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Extraction;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;

/**
 * Implemented by the four extractors only.
 *
 * This exists to keep them uniform inside the pipeline — it is **not** an extension point. The
 * implementations are a closed set constructed explicitly in `Cli\Wiring`, with no discovery, no
 * registration and no configuration (ADR-A003).
 */
interface RegionAssertionExtractor
{
    /**
     * @return list<Assertion>
     */
    public function forRegion(ChangedFile $file, ChangedRegion $region, string $fileText): array;
}
