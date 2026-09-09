<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Support;

use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleAssertion;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\ContractVersion;
use ContextDiscovery\Domain\Bundle\Diagnostic;
use ContextDiscovery\Domain\Bundle\DroppedItem;
use ContextDiscovery\Domain\Bundle\RunMetadata;

/**
 * Builds v2 bundles for tests that need one by hand.
 *
 * v2 made the assertion first-class, so a hand-built bundle now needs its claims registered as well
 * as its items — and `Bundle` rejects an item naming a claim it does not carry. That bookkeeping is
 * the same everywhere, so it lives here instead of in a dozen test files, and the tests keep
 * reading as statements about behaviour rather than about construction.
 */
trait BuildsBundles
{
    /** @var array<string, BundleAssertion> */
    private array $claims = [];

    /**
     * Registers a claim and hands it back. Two calls with the same kind, reason and path are the
     * same claim, which is what lets a test build several items for one assertion.
     */
    protected function claim(
        AssertionKind $kind,
        string $reason,
        string $path = 'app/One.php',
        string $subject = 'subject',
    ): BundleAssertion {
        $id = 'a' . substr(sha1($kind->value . '|' . $subject . '|' . $reason . '|' . $path), 0, 12);

        return $this->claims[$id] ??= new BundleAssertion($id, $kind, $subject, $reason, $path, 1, 1);
    }

    /**
     * @param list<BundleItem>  $items
     * @param list<DroppedItem> $dropped
     * @param list<Diagnostic>  $diagnostics
     */
    protected function bundleOf(int $budget, array $items, array $dropped = [], array $diagnostics = []): Bundle
    {
        return new Bundle(
            assertions: array_values($this->claims),
            items: $items,
            diagnostics: $diagnostics,
            dropped: $dropped,
            run: $this->runMetadata($budget),
            usedTokens: array_sum(array_map(static fn (BundleItem $i): int => $i->tokens, $items)),
        );
    }

    protected function runMetadata(int $budget = 8000): RunMetadata
    {
        return new RunMetadata(
            engineVersion: ContractVersion::ENGINE,
            policyVersion: ContractVersion::POLICY,
            frameworkTableVersion: 'test',
            budgetTokens: $budget,
            diffSha: null,
            repoSha: null,
        );
    }

    /**
     * The kind an item belongs to, which in v2 means the kind of the assertion it names.
     */
    protected function kindOf(Bundle $bundle, BundleItem $item): string
    {
        foreach ($bundle->assertions as $assertion) {
            if ($assertion->id === $item->assertionId) {
                return $assertion->kind->value;
            }
        }

        return '';
    }

    /**
     * The reason an item carries, which in v2 means the reason of the assertion it names.
     */
    protected function reasonOf(Bundle $bundle, BundleItem $item): string
    {
        foreach ($bundle->assertions as $assertion) {
            if ($assertion->id === $item->assertionId) {
                return $assertion->reason;
            }
        }

        return '';
    }
}
