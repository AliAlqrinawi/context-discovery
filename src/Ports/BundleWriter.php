<?php

declare(strict_types=1);

namespace ContextDiscovery\Ports;

use ContextDiscovery\Domain\Bundle\Bundle;

/**
 * Bundle to serialised output.
 *
 * The one port justified by two real implementations selected at runtime: JSON is the stable
 * machine contract, Markdown is the artifact a human pastes alongside the diff. Markdown is a
 * projection of the same facts, never a different set.
 *
 * Diagnostics never appear in the output — they go to stderr, so the bundle on stdout is always
 * parseable and its token cost is the number that describes it.
 */
interface BundleWriter
{
    public function write(Bundle $bundle): string;
}
