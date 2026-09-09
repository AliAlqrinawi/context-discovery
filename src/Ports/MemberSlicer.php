<?php

declare(strict_types=1);

namespace ContextDiscovery\Ports;

use ContextDiscovery\Domain\Source\SourceSlice;

/**
 * PHP file text to minimal member spans.
 *
 * One responsibility — where members and the `use` block begin and end — expressed as five
 * methods, all five consumed.
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

    /**
     * The member whose span contains this line, closures included — "which member am I reading?".
     */
    public function enclosingMemberName(string $fileText, int $line): ?string;

    /**
     * The member whose **own body** contains this line — null when the line sits inside a function
     * scope declared within that member: a closure passed to `map()`, a `DB::transaction()`
     * callback, a method of an anonymous class.
     *
     * A different question from `enclosingMemberName()`, and both are needed. Fetching a member as
     * context wants the member a line is *inside*; attributing a statement *to* a member wants the
     * member the statement belongs to. A `return` in a `map()` callback is inside `outer()` but is
     * not `outer()`'s return, and only this method can say so.
     *
     * Null when the scope cannot be established, so an ambiguous file claims nothing (P10).
     */
    public function memberOwningLine(string $fileText, int $line): ?string;
}
