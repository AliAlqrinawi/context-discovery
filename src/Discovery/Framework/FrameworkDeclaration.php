<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Framework;

/**
 * Where a framework declares a member, and the text that says so.
 *
 * A framework-known reference produces **no bundle item** — its whole output is one diagnostic line
 * (stage 5d, freeze review 05) — so this value never reaches `Domain` at all, and no bundle field,
 * assertion kind, premise or lever was added for it. It exists so the
 * citation is a value rather than a pre-formatted sentence: the port that finds the evidence reads
 * one file's text and does not know its path, and the resolver that knows the path does not know
 * what counts as evidence. Neither can compose the line alone, and neither has to.
 *
 * `line` is 1-based and relative to the file the evidence was read from, so a reviewer can open it.
 */
final class FrameworkDeclaration
{
    public function __construct(
        public readonly string $evidence,
        public readonly int $line,
    ) {
    }
}
