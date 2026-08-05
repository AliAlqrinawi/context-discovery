<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Fakes;

use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\DroppedItem;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Bundle\Provenance;

/**
 * A bundle built by hand, so the writers can be exercised before anything assembles one.
 *
 * Shaped after the illustrative bundle in the implementation spec: a fetched slice with full
 * provenance, a flagged premise with no line span, and an own-file slice with a path only.
 * `used_tokens` is the sum of the items' tokens, so a writer that drops or double-counts one
 * shows up immediately.
 */
final class BundleFixture
{
    public const BUDGET_TOKENS = 8000;
    public const USED_TOKENS = 236;

    public static function full(): Bundle
    {
        return new Bundle(
            items: [
                new BundleItem(
                    lever: Lever::Fetched,
                    reason: 'changed call site depends on PlaidAccount::forItem() and official_name',
                    assertionKind: AssertionKind::NamedReference,
                    provenance: new Provenance('app/Models/PlaidAccount.php', 'forItem', 41, 58),
                    payload: "public function forItem(PlaidItem \$item)\n{\n    return static::query();\n}",
                    tokens: 180,
                ),
                new BundleItem(
                    lever: Lever::Flagged,
                    reason: 'reconciliation assumes a surrounding DB transaction; caller not verified',
                    assertionKind: AssertionKind::UnverifiablePremise,
                    provenance: new Provenance('app/Services/Plaid/PlaidAccountService.php', 'syncFromResponse'),
                    payload: 'ASSUMPTION: this code assumes a surrounding transaction; caller not checked',
                    tokens: 14,
                ),
                new BundleItem(
                    lever: Lever::Fetched,
                    reason: "the region uses Log, which is absent from the file's use block",
                    assertionKind: AssertionKind::SameFileSymbolAbsence,
                    provenance: new Provenance('app/Services/Plaid/PlaidAccountService.php'),
                    // Contains a fenced block, so the Markdown writer has to widen its own fence.
                    payload: "/** ```php */\nuse App\\Models\\PlaidAccount;",
                    tokens: 42,
                ),
            ],
            dropped: [
                new DroppedItem('route wiring for reauth endpoint', 'below budget priority', 260),
                new DroppedItem('ExchangePublicTokenRequest validation rules', 'below budget priority', 310),
            ],
            budgetTokens: self::BUDGET_TOKENS,
            usedTokens: self::USED_TOKENS,
        );
    }

    /**
     * Experiment 2's correct answer: emptiness is a result, not a failure.
     */
    public static function empty(): Bundle
    {
        return new Bundle(items: [], dropped: [], budgetTokens: self::BUDGET_TOKENS, usedTokens: 0);
    }
}
