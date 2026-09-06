<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Resolution;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Source\SourceSlice;
use ContextDiscovery\Discovery\Framework\FrameworkKnowledge;
use ContextDiscovery\Ports\ClassLocator;
use ContextDiscovery\Ports\MemberSlicer;
use ContextDiscovery\Ports\SourceRepository;

/**
 * Locates the class through the PSR-4 map and slices what the region actually named.
 *
 * **Depth one, structurally.** This reads the located file to slice a member and does nothing
 * else with it: it never reads that file's `use` block, never resolves its references, and hands
 * nothing back to extraction (P3, P4, X2). There is no worklist here that could hold a pending
 * reference, which is why depth two is unreachable rather than merely unconfigured.
 *
 * Two outcomes, per 01-architecture.md §3.3:
 *
 * - the region named a member (`Fqcn::member`) — slice that member, and only it;
 * - the region named the class alone — slice its members, the model or enum *surface* Experiment 1
 *   and Experiment 4 ask for.
 *
 * Anything unresolvable returns nothing: no PSR-4 entry, an unreadable file, or a member the
 * slicer cannot find. The pipeline then flags it as `unresolved-reference`, so a contract that
 * could not be checked is stated rather than dropped (P10).
 *
 * One fallback sits beside that, never in place of it. When the member is unaccounted for and the
 * class is the project's own, `unresolvedMemberSurface()` offers the class surface instead of
 * nothing — ADR-A020 — and the flag stays where it is.
 */
final class NamedReferenceResolver implements AssertionResolver
{
    public function __construct(
        private readonly ClassLocator $locator,
        private readonly SourceRepository $source,
        private readonly MemberSlicer $slicer,
        private readonly FrameworkKnowledge $framework,
    ) {
    }

    /**
     * True for exactly one case: the framework declares the member.
     *
     * Otherwise false, for the reason freeze review 05 gave — a named reference appears in the
     * diff, so its source should exist, and a missing PSR-4 entry, an unreadable path or an
     * undeclared member are all **lookup failures** that flag as `unresolved-reference` (P10).
     *
     * A framework-declared member is not such a failure. The run asked "is this contract defined,
     * and where?" and answered it with a citation, so nothing is unverified and ADR-A009's rule —
     * *a premise exists only where an unverified premise exists* — forbids stating one. It is a
     * successful **negative** in the same sense as a caller search that completes with zero call
     * sites: no bundle item, one diagnostic (stage 5d). The framework's own source is never
     * fetched; Experiment 2 counts that as a precision failure.
     */
    public function lookupRan(Assertion $assertion): bool
    {
        return $this->frameworkDeclarationFor($assertion) !== null
            || $this->dependencyPathFor($assertion) !== null
            || $this->dependencyMemberDeclarationFor($assertion) !== null;
    }

    /**
     * Where an installed dependency provides a **bare class** the region names — or null.
     *
     * Answered from the path alone; the file is never opened, and nothing from it can therefore
     * reach the bundle. A class the project's own source declares returns null and keeps the
     * surface fetch Experiment 1 requires, so project-local behaviour is untouched (ADR-A012).
     *
     * Bare classes only. A `Fqcn::member` reference asks about one member, which is a different
     * question with a different answer — see `frameworkDeclarationFor()` — and widening this to
     * members is out of M4's scope.
     */
    public function dependencyMemberDeclarationFor(Assertion $assertion): ?string
    {
        if ($assertion->kind !== AssertionKind::NamedReference) {
            return null;
        }

        [$class, $member] = self::split($assertion->subject);

        if ($member === null) {
            return null; // a bare class is `dependencyPathFor()`'s question.
        }

        $path = $this->locator->pathFor($class);

        if ($path === null || $this->locator->isProjectSource($path)) {
            return null;
        }

        $text = $this->source->text($path);
        $slice = $text === null ? null : $this->slicer->member($text, $member);

        // Positive evidence only. Ownership alone would swallow `Str::slugg()` — a typo against a
        // real dependency — and report it as provided, which is the silent omission P10 forbids and
        // M0 records as risk R1. No declaration, no verdict: the flag stands.
        return $slice === null ? null : sprintf('%s:%d', $path, $slice->firstLine);
    }

    /**
     * Where an installed dependency provides a **bare class** the region names — or null.
     *
     * Answered from the path alone; the file is never opened for this question (ADR-A012).
     */
    public function dependencyPathFor(Assertion $assertion): ?string
    {
        if ($assertion->kind !== AssertionKind::NamedReference) {
            return null;
        }

        [$class, $member] = self::split($assertion->subject);

        if ($member !== null) {
            return null;
        }

        $path = $this->locator->pathFor($class);

        return $path === null || $this->locator->isProjectSource($path) ? null : $path;
    }

    /**
     * Where the framework declares this reference's member — `path:line (evidence)` — or null.
     *
     * Asked twice per assertion, once by `lookupRan()` and once by the pipeline when it writes the
     * diagnostic. That mirrors `CallerResolver` and `OwnFileResolver`, which also re-read to answer
     * `lookupRan()`; the alternative is state on a resolver that is otherwise a pure function of
     * its assertion.
     *
     * A member the class's own file declares is never framework-known: the project's own source is
     * asked first and always wins.
     */
    public function frameworkDeclarationFor(Assertion $assertion): ?string
    {
        if ($assertion->kind !== AssertionKind::NamedReference) {
            return null;
        }

        [$class, $member] = self::split($assertion->subject);

        if ($member === null) {
            return null; // a bare class name asks for a surface, not for a member contract.
        }

        $path = $this->locator->pathFor($class);
        $text = $path === null ? null : $this->source->text($path);

        if ($path === null || $text === null || $this->slicer->member($text, $member) !== null) {
            return null;
        }

        $declaration = $this->framework->declarationOf($member, $text);

        return $declaration === null
            ? null
            : sprintf('%s:%d (%s)', $path, $declaration->line, $declaration->evidence);
    }

    /**
     * The class's surface, when a `Fqcn::member` reference names a member nothing can account for.
     *
     * **ADR-A020, M13's boundary B4.** Real Laravel code reaches a model through
     * `Setting::updateOrCreate(...)`, never through `new Setting`, so the surface Experiment 1
     * earned never fired on the shape the corpus actually contains (M7, gap G1). This is that
     * conformance gap closed at resolution time — the frozen form table already lists a class name
     * in a **static-call** position as a named reference (01-architecture.md §3.3).
     *
     * Three conditions, each of which M13 measured a false positive for removing:
     *
     * - the map **places** the class — otherwise `MissingGateway::resolve` gains a second outcome
     *   for one unknown reference;
     * - the path is **project source** — otherwise a dependency's source enters the bundle, which
     *   is the whole of ADR-A012;
     * - **nothing accounts for the member** in that class's own file: not a declaration, not a
     *   framework naming convention, not a framework declaration. Otherwise `Registry::create` —
     *   an ordinary project class that happens to declare a method Eloquent also has — gains a
     *   surface where the declared member already *is* the minimal slice.
     *
     * No name is special-cased. `Registry::create` is excluded because its `create()` resolves,
     * never because `create` is Eloquent's; a class is included because the member did not
     * resolve, never because it extends `Model` — reading the parent would breach ADR-A010, and
     * ADR-A016 showed the one-file evidence cannot support that question anyway.
     *
     * Still one file. The locator places the class, that file is read, and nothing else is opened.
     *
     * @return list<SourceSlice>
     */
    public function unresolvedMemberSurface(Assertion $assertion): array
    {
        if ($assertion->kind !== AssertionKind::NamedReference) {
            return [];
        }

        [$class, $member] = self::split($assertion->subject);

        if ($member === null) {
            return []; // a bare class already takes the surface path; this is the fallback for members.
        }

        $path = $this->locator->pathFor($class);

        if ($path === null || !$this->locator->isProjectSource($path)) {
            return [];
        }

        $text = $this->source->text($path);

        if ($text === null) {
            return [];
        }

        // Never overrides a member that resolved. Asked in full here rather than assumed from the
        // caller, so the boundary holds wherever this is called from.
        if ($this->member($path, $text, $member) !== [] || $this->framework->declarationOf($member, $text) !== null) {
            return [];
        }

        return $this->surface($path, $text);
    }

    /**
     * @return list<SourceSlice>
     */
    public function resolve(Assertion $assertion): array
    {
        if ($assertion->kind !== AssertionKind::NamedReference) {
            return [];
        }

        [$class, $member] = self::split($assertion->subject);

        $path = $this->locator->pathFor($class);

        if ($path === null) {
            return [];
        }

        if (!$this->locator->isProjectSource($path)) {
            // The class is an installed dependency's, not this project's, and the fetch-collaborator
            // move is scoped to application code: `context-types.md` type 2 is *"Named collaborator
            // code (application code, depth one)"* and covers "a class, model, enum, or method".
            // Nothing is fetched here — not a class surface (ADR-A012), not a single declared
            // method (ADR-A013). The reference is settled on the diagnostic path instead, and only
            // when the member is really there; a member that is not stays a flag (P10).
            //
            // Decided **before** the read, deliberately: the path alone is enough, so no dependency
            // text is ever loaded on the path that produces payload.
            return [];
        }

        $text = $this->source->text($path);

        if ($text === null) {
            return [];
        }

        return $member === null
            ? $this->surface($path, $text)
            : $this->member($path, $text, $member);
    }

    /**
     * A subject is `Fqcn` or `Fqcn::member`. A fully-qualified name never contains `::`, so the
     * split is unambiguous. `LeverPolicy` reads the same encoding.
     *
     * @return array{0: string, 1: string|null}
     */
    public static function split(string $subject): array
    {
        $separator = strrpos($subject, '::');

        return $separator === false
            ? [$subject, null]
            : [substr($subject, 0, $separator), substr($subject, $separator + 2)];
    }

    /**
     * @return list<SourceSlice>
     */
    private function member(string $path, string $text, string $member): array
    {
        $slice = $this->slicer->member($text, $member);

        if ($slice !== null) {
            return [$this->at($path, $slice)];
        }

        // The class's own file had no member of that name. A framework naming convention may say
        // another member **of this same text** is what the reference resolves to — Laravel's
        // `scope<Name>`, for instance. That member is application code, so it is fetched like any
        // other project-local slice: the framework supplies the rule, the project the source.
        // One file, no traversal (ADR-A010) — the port is handed text and cannot open another.
        $conventional = $this->framework->sameFileMemberFor($member, $text);
        $slice = $conventional === null ? null : $this->slicer->member($text, $conventional);

        return $slice === null ? [] : [$this->at($path, $slice)];
    }

    /**
     * The model or enum surface: the class's members, each as its own slice with its own
     * provenance. Not the whole file — the namespace, the `use` block and the class declaration
     * stay out (ADR-A005).
     *
     * @return list<SourceSlice>
     */
    private function surface(string $path, string $text): array
    {
        $slices = [];

        foreach ($this->slicer->memberNames($text) as $name) {
            $slice = $this->slicer->member($text, $name);

            if ($slice !== null) {
                $slices[] = $this->at($path, $slice);
            }
        }

        return $slices;
    }

    private function at(string $path, SourceSlice $slice): SourceSlice
    {
        return new SourceSlice($path, $slice->member, $slice->firstLine, $slice->lastLine, $slice->text);
    }
}
