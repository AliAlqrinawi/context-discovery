<?php

declare(strict_types=1);

namespace ContextDiscovery\Assembly;

/**
 * A naive, documented token estimate — not a real tokenizer (ADR-A007).
 *
 * P7 asks for a budget with visible drops; it asks nothing about token-count fidelity. The
 * hypothesis's cost criterion is comparative and coarse — "dramatically below B", "well under
 * half of B's tokens" — a scale at which a fixed-ratio estimate is adequate. A model-specific
 * BPE library would add a runtime dependency and tie a model-agnostic bundle to one vocabulary;
 * a remote tokenizer would put a network call inside a deterministic, offline tool.
 *
 * The number this produces is an **estimate** and is labelled as one wherever it is written.
 *
 * ---
 *
 * **AA4 — architectural assumption, not evidence.**
 *
 * The characters-per-token ratio below is an *architectural assumption* recorded in
 * `evidence-gaps.md` §5. It is **not measured**, and no experiment in the research repository
 * produces it. It is adequate for a coarse comparative claim and nothing more. The architecture
 * fixes that there is one constant, in one place, labelled an estimate; it does not present the
 * number as proven.
 *
 * Revisit trigger, per `evidence-gaps.md` §4: a *measured* discrepancy that changes a budget
 * decision. Not scheduled, and not to be tuned on impression.
 */
final class TokenEstimate
{
    /**
     * AA4. See the class docblock: assumed, not measured.
     */
    private const CHARACTERS_PER_TOKEN = 4;

    /**
     * Bytes, not multibyte characters: strlen() is always available where PHP is, and the ratio
     * is a coarse estimate either way.
     */
    public function of(string $text): int
    {
        return (int) ceil(strlen($text) / self::CHARACTERS_PER_TOKEN);
    }
}
