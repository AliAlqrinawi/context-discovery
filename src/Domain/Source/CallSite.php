<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Source;

/**
 * One line, somewhere under the caller scope, that names a changed method.
 *
 * The product of a bounded grep, never of a call graph (ADR-A006). Call sites may include false
 * matches — a same-named method on another class. That imprecision is known, documented, and
 * recorded per item so a scored run can measure it. It is not filtered: filtering would be
 * judgement, and the engine judges nothing (P6).
 */
final class CallSite
{
    public function __construct(
        public readonly string $path,
        public readonly int $line,
        public readonly string $lineText,
    ) {
    }
}
