<?php

declare(strict_types=1);

namespace ContextDiscovery\Ports;

use ContextDiscovery\Domain\Source\SourceSlice;

/**
 * PHP file text to minimal member spans.
 *
 * One responsibility — where members and the `use` block begin and end — expressed as four
 * methods, all four consumed.
 *
 * A member that cannot be found returns null; the assertion is then flagged, not silently
 * skipped (P10, ADR-A004).
 */
interface MemberSlicer
{
    /**
     * The file's `use` import block. Reading it as a structure is what makes an *absence* — a
     * missing import — visible; an absence is not a reference and cannot be followed.
     */
    public function useBlock(string $fileText): ?SourceSlice;

    /**
     * The named member: signature and body, plus any attribute lines immediately preceding it,
     * since they are part of what a reviewer must see (ADR-A004).
     */
    public function member(string $fileText, string $memberName): ?SourceSlice;

    /**
     * @return list<string> Used to decide whether a reference is defined locally.
     */
    public function memberNames(string $fileText): array;

    public function enclosingMemberName(string $fileText, int $line): ?string;
}
