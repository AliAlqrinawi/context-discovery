<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Framework;

/**
 * What a framework, rather than the application, says about a member the diff names.
 *
 * **Not a port, deliberately.** Freeze review O1/O2 fixed the rule: a pure text transformation
 * with one implementation is a plain class, not a port — that is why `UnifiedDiffParser` sits in
 * `Discovery\Parsing` and `TokenEstimate` in `Assembly`. This performs no I/O either: it is handed
 * text that `NamedReferenceResolver` has already read. So it lives beside the code that uses it,
 * `Ports` stays at five, and the frozen port count is untouched.
 *
 * The interface still earns its place, for one reason that is not speculative flexibility: it is
 * the seam that keeps every other class in `Discovery` free of framework symbols.
 * `NamedReferenceResolver` depends on this name and never on a framework's; the implementation is
 * chosen in `Cli\Wiring`, so a project on another framework changes one line.
 *
 * **The signature is the depth bound.** Both methods receive *one file's text* and nothing else —
 * no path, no `ClassLocator`, no `SourceRepository`. An implementation therefore *cannot* open a
 * second file: [ADR-A010]'s one-file rule is enforced by the contract rather than by discipline.
 * `extends`, `use <Trait>` and `@mixin` are unreachable from here by construction.
 *
 * Two questions, deliberately separate, because their answers have different consequences:
 *
 * - `declarationOf()` — the framework supplies this member. The contract is defined and citable, so
 *   nothing is unverified: the reference yields **no bundle item** and one diagnostic. Its source
 *   is never fetched (Experiment 2: a reviewer who knows the framework needs none of it, and
 *   pulling it is a precision failure).
 * - `sameFileMemberFor()` — a framework *naming convention* says another member **of this same
 *   file** is what the reference resolves to. That member is application code, so it is fetched
 *   exactly like any other project-local slice. The framework supplies the rule; the project
 *   supplies the source.
 *
 * Both are consulted only after the class's own file has been asked directly and had no answer, so
 * a project member always wins over a framework claim.
 */
interface FrameworkKnowledge
{
    /**
     * Where the framework declares `$member` on the class this text defines, or `null` when it
     * declares no such member.
     *
     * @param string $classFileText the full text of the one file the `ClassLocator` placed
     */
    public function declarationOf(string $member, string $classFileText): ?FrameworkDeclaration;

    /**
     * The name of a member **declared in this same text** that a framework naming convention says
     * satisfies a reference to `$member`, or `null` when no convention applies.
     *
     * @param string $classFileText the full text of the one file the `ClassLocator` placed
     */
    public function sameFileMemberFor(string $member, string $classFileText): ?string;

    /**
     * The *cardinality class* a framework finisher returns — or null when the name is not one.
     *
     * `get()` yields many, `first()` yields one-or-null, `count()` yields a scalar. Two names in
     * different classes mean a return contract changed even though no signature did.
     *
     * Null for every name the table does not list, which is the conservative answer: an unknown
     * finisher produces no assertion rather than a guess (ADR-A003).
     */
    public function returnCardinalityOf(string $member): ?string;
}
