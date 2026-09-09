<?php

declare(strict_types=1);

namespace ContextDiscovery\Assembly;

use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Assertion\ResolvedAssertion;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Bundle\Provenance;
use ContextDiscovery\Domain\Source\SourceSlice;
use InvalidArgumentException;

/**
 * Resolved assertions become bundle items, each carrying its reason, lever and provenance.
 *
 * No item enters without a reason — the invariant lives in BundleItem and is left to raise here,
 * because an item with no reason is a defect and not a warning (P5).
 *
 * Order is decided here, once, so the JSON and the Markdown are two renderings of one ordering
 * (03-interfaces.md §2). Budget enforcement is not this class's job: the bundle leaves here with
 * its full contents and an honest token total, and BudgetEnforcer decides what survives.
 */
final class BundleAssembler
{
    /**
     * The fixed item order, so output is diffable (03-interfaces.md §2).
     */
    private const KIND_ORDER = [
        AssertionKind::SameFileSymbolAbsence->value => 0,
        AssertionKind::SameFileReference->value => 1,
        AssertionKind::ChangedSignature->value => 2,
        // Beside the signature change: the same question about the same member, so a reader meets
        // them together. Inserting here keeps every pre-existing kind's relative order unchanged.
        AssertionKind::ChangedReturnContract->value => 3,
        AssertionKind::NamedReference->value => 4,
        AssertionKind::UnverifiablePremise->value => 5,
    ];

    public function __construct(private readonly TokenEstimate $tokenEstimate)
    {
    }

    /**
     * @param list<ResolvedAssertion> $resolved
     */
    public function assemble(array $resolved, int $budgetTokens): Bundle
    {
        $items = [];
        $seen = [];

        foreach ($resolved as $resolvedAssertion) {
            foreach ($this->itemsFor($resolvedAssertion) as $item) {
                $identity = self::identityOf($item);

                if (isset($seen[$identity])) {
                    // ADR-A021. One member named from two files resolves twice, and the second
                    // item is identical in every field a reviewer can see — including `reason`,
                    // because a *fetched* item's provenance is the DECLARING site, never the
                    // requesting one. So the copy is not a second fact; it is the same bytes
                    // printed again, and ADR-A005 spends tokens only where they buy something.
                    //
                    // Nothing is omitted and nothing is recorded: the surviving item is
                    // byte-identical to the one skipped, so every reason, provenance and byte
                    // still reaches the reviewer. That is what separates this from a budget drop,
                    // which loses an item and must therefore say so (P10).
                    //
                    // Flags need no special case. Their provenance IS the origin, so two origins
                    // give two identities and both survive — the behaviour M15 keyed as row T2.
                    continue;
                }

                $seen[$identity] = true;
                $items[] = $item;
            }
        }

        // usort is stable, so items with equal keys keep the order they were resolved in (P8).
        // The collapse above runs first, so which copy survives is a function of resolution order
        // alone rather than of the sort's tie-breaking.
        usort($items, $this->compare(...));

        return new Bundle(
            items: $items,
            dropped: [],
            budgetTokens: $budgetTokens,
            usedTokens: array_sum(array_map(static fn (BundleItem $item): int => $item->tokens, $items)),
        );
    }

    /**
     * One item per slice: the bundle schema gives each item a single path, member and line span,
     * so two slices cannot share one provenance. They share the reason instead — each is
     * separately justified by the same assertion.
     *
     * @return list<BundleItem>
     */
    private function itemsFor(ResolvedAssertion $resolved): array
    {
        $assertion = $resolved->assertion;

        if ($resolved->lever === Lever::Flagged) {
            $statement = (string) $resolved->statement;

            return [
                new BundleItem(
                    lever: Lever::Flagged,
                    reason: $assertion->claim,
                    assertionKind: $assertion->kind,
                    // A flag carries its origin path and line span always, and a member only when
                    // the failing assertion already names one — a premise names a premise, not a
                    // member, and nothing here derives one (ADR-A009, freeze review 04).
                    provenance: new Provenance(
                        $assertion->originPath,
                        $assertion->kind === AssertionKind::UnverifiablePremise ? null : $assertion->subject,
                        $assertion->originRegion->firstLine,
                        $assertion->originRegion->lastLine,
                    ),
                    payload: $statement,
                    tokens: $this->tokenEstimate->of($statement),
                ),
            ];
        }

        if ($resolved->slices === []) {
            // A resolver that finds nothing must hand the assertion back to be flagged (P10).
            // Reaching assembly with neither slices nor a statement would drop the concern
            // silently, and a silent omission is indistinguishable from "nothing needed".
            throw new InvalidArgumentException(sprintf(
                'A fetched assertion reached assembly with no slices and no statement: %s "%s" '
                . 'in %s. It should have been flagged (P10).',
                $assertion->kind->value,
                $assertion->subject,
                $assertion->originPath,
            ));
        }

        return array_map(
            fn (SourceSlice $slice): BundleItem => new BundleItem(
                lever: Lever::Fetched,
                reason: $assertion->claim,
                assertionKind: $assertion->kind,
                provenance: new Provenance(
                    $slice->path,
                    $slice->member,
                    $slice->firstLine,
                    $slice->lastLine,
                ),
                payload: $slice->text,
                tokens: $this->tokenEstimate->of($slice->text),
            ),
            $resolved->slices,
        );
    }

    /**
     * Everything a reviewer can see, and nothing else — ADR-A021.
     *
     * Every field here is forced by a row of `experiment-16`'s key that goes red without it: the
     * member by R4, the path by R5, the kind and reason by R6, the payload by R7, the line span by
     * R8. `tokens` is a function of the payload and adds nothing.
     *
     * **R6 is why the slice's location is not enough.** `ControllerA::helper` arrives three times
     * with the same path, member, span and text — as a symbol absence, as a same-file reference,
     * and as a named reference from another file. Those answer three different questions and sit in
     * two different `ItemPriority` bands, so collapsing them would change what survives a budget.
     *
     * A separator that cannot occur inside a path, a member name or an assertion kind keeps the
     * concatenation unambiguous for the two fields that could contain anything — reason and
     * payload — by putting them last and by encoding the null member distinctly from an empty one.
     */
    private static function identityOf(BundleItem $item): string
    {
        return implode("\0", [
            $item->lever->value,
            $item->assertionKind->value,
            $item->provenance->path,
            $item->provenance->member ?? "\1",
            (string) $item->provenance->firstLine,
            (string) $item->provenance->lastLine,
            $item->reason,
            $item->payload,
        ]);
    }

    private function compare(BundleItem $a, BundleItem $b): int
    {
        return self::KIND_ORDER[$a->assertionKind->value] <=> self::KIND_ORDER[$b->assertionKind->value]
            ?: strcmp($a->provenance->path, $b->provenance->path)
            ?: strcmp($a->provenance->member ?? '', $b->provenance->member ?? '');
    }
}
