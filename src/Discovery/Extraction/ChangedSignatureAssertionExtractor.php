<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Extraction;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedMember;
use ContextDiscovery\Domain\Diff\ChangedRegion;

/**
 * Emits `ChangedSignature` when a member's arity or parameter shape changed.
 *
 * This is the finding type a diff-only review structurally cannot reach: the danger lives entirely
 * in callers off-screen. Experiment 4's `reactivate` went from `(PlaidItem, array)` to
 * `(PlaidItem, string, ?string, ?string)`; the one caller in the diff was updated, and any other
 * would ship a runtime `TypeError`.
 *
 * Only the parameter list decides. A body-only change, a rename, an added member, a removed member
 * and a return-type-only change all produce nothing:
 *
 * - a **rename** is two members and is not tracked (the parser sees one with no new signature and
 *   one with no old), so neither side has both halves to compare;
 * - a **return type** change is neither arity nor parameter shape, so the contract does not ask
 *   for it and nothing is inferred (ADR-A003).
 *
 * A member is attributed to the region whose lines carry its declaration, so a file with several
 * hunks raises the assertion once, where the signature actually changed.
 */
final class ChangedSignatureAssertionExtractor implements RegionAssertionExtractor
{
    /**
     * @return list<Assertion>
     */
    public function forRegion(ChangedFile $file, ChangedRegion $region, string $fileText): array
    {
        $assertions = [];

        foreach ($file->members as $member) {
            if (!$this->parametersChanged($member) || !$this->declaredIn($member->name, $region)) {
                continue;
            }

            $assertions[] = new Assertion(
                AssertionKind::ChangedSignature,
                $member->name,
                $file->path,
                $region,
                sprintf(
                    'the signature of %s changed; its call sites are not shown by the diff',
                    $member->name,
                ),
            );
        }

        return $assertions;
    }

    private function parametersChanged(ChangedMember $member): bool
    {
        if ($member->oldSignature === null || $member->newSignature === null) {
            return false; // nothing to compare against.
        }

        $old = $this->parameters($member->oldSignature);
        $new = $this->parameters($member->newSignature);

        if ($old === null || $new === null) {
            return false; // unreadable: no assertion rather than a guess.
        }

        return $old !== $new;
    }

    /**
     * The normalised parameter list of a signature: one entry per parameter, whitespace collapsed
     * so reformatting alone is not a change.
     *
     * @return list<string>|null Null when the parameter list cannot be read.
     */
    private function parameters(string $signature): ?array
    {
        $tokens = $this->tokenize($signature);
        $total = count($tokens);

        $open = null;

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] === null && $tokens[$i]['text'] === '(') {
                $open = $i;
                break;
            }
        }

        if ($open === null) {
            return null;
        }

        $depth = 0;
        $parameters = [];
        $current = '';

        for ($i = $open; $i < $total; $i++) {
            $id = $tokens[$i]['id'];
            $text = $tokens[$i]['text'];

            if ($id === null && ($text === '(' || $text === '[')) {
                $depth++;

                if ($depth === 1) {
                    continue; // the opening parenthesis itself is not part of a parameter.
                }
            }

            if ($id === null && ($text === ')' || $text === ']')) {
                $depth--;

                if ($depth === 0) {
                    $parameters[] = $current;

                    return $this->normalise($parameters);
                }
            }

            if ($id === null && $text === ',' && $depth === 1) {
                $parameters[] = $current;
                $current = '';

                continue;
            }

            $current .= $text;
        }

        return null; // unbalanced.
    }

    /**
     * @param list<string> $parameters
     *
     * @return list<string>
     */
    private function normalise(array $parameters): array
    {
        $normalised = [];

        foreach ($parameters as $parameter) {
            $collapsed = trim((string) preg_replace('/\s+/', ' ', $parameter));

            if ($collapsed !== '') {
                $normalised[] = $collapsed;
            }
        }

        return $normalised;
    }

    /**
     * Whether this region's lines carry the member's declaration — added or removed. Attribution
     * only; the parser has already decided what the signatures are.
     */
    private function declaredIn(string $member, ChangedRegion $region): bool
    {
        $pattern = '/\bfunction\s*&?\s*' . preg_quote($member, '/') . '\b/';

        foreach ([...$region->addedLines, ...$region->removedLines] as $line) {
            if (preg_match($pattern, $line) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{id:int|null,text:string}>
     */
    private function tokenize(string $code): array
    {
        $tokens = [];

        foreach (@token_get_all('<?php ' . $code) as $token) {
            if (is_array($token) && $token[0] === T_OPEN_TAG) {
                continue;
            }

            $tokens[] = is_array($token)
                ? ['id' => $token[0], 'text' => $token[1]]
                : ['id' => null, 'text' => $token];
        }

        return $tokens;
    }
}
