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
        AssertionKind::NamedReference->value => 3,
        AssertionKind::UnverifiablePremise->value => 4,
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

        foreach ($resolved as $resolvedAssertion) {
            foreach ($this->itemsFor($resolvedAssertion) as $item) {
                $items[] = $item;
            }
        }

        // usort is stable, so items with equal keys keep the order they were resolved in (P8).
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

    private function compare(BundleItem $a, BundleItem $b): int
    {
        return self::KIND_ORDER[$a->assertionKind->value] <=> self::KIND_ORDER[$b->assertionKind->value]
            ?: strcmp($a->provenance->path, $b->provenance->path)
            ?: strcmp($a->provenance->member ?? '', $b->provenance->member ?? '');
    }
}
