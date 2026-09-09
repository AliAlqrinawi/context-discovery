<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Fakes;

use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleAssertion;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\ContractVersion;
use ContextDiscovery\Domain\Bundle\Diagnostic;
use ContextDiscovery\Domain\Bundle\DroppedItem;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Bundle\Provenance;
use ContextDiscovery\Domain\Bundle\RunMetadata;

/**
 * A bundle built by hand, so the writers can be exercised before anything assembles one.
 *
 * Shaped after the illustrative bundle in the implementation spec: a fetched slice with full
 * provenance, a flagged premise with no line span, and an own-file slice with a path only.
 * `used_tokens` is the sum of the **items'** tokens — diagnostics are carried here too and must
 * not move it, which is how a writer that counts them shows up immediately (ADR-A024).
 */
final class BundleFixture
{
    public const BUDGET_TOKENS = 8000;
    public const USED_TOKENS = 236;

    public const NAMED_REFERENCE_ID = 'a1111111111aa';
    public const PREMISE_ID = 'a2222222222bb';
    public const ABSENCE_ID = 'a3333333333cc';

    public static function full(): Bundle
    {
        return new Bundle(
            assertions: [
                new BundleAssertion(
                    id: self::NAMED_REFERENCE_ID,
                    kind: AssertionKind::NamedReference,
                    subject: 'App\Models\PlaidAccount::forItem',
                    reason: 'changed call site depends on PlaidAccount::forItem() and official_name',
                    originPath: 'app/Services/Plaid/PlaidAccountService.php',
                    originFirstLine: 120,
                    originLastLine: 168,
                ),
                new BundleAssertion(
                    id: self::PREMISE_ID,
                    kind: AssertionKind::UnverifiablePremise,
                    subject: 'surrounding-transaction',
                    reason: 'reconciliation assumes a surrounding DB transaction; caller not verified',
                    originPath: 'app/Services/Plaid/PlaidAccountService.php',
                    originFirstLine: 120,
                    originLastLine: 168,
                ),
                new BundleAssertion(
                    id: self::ABSENCE_ID,
                    kind: AssertionKind::SameFileSymbolAbsence,
                    subject: 'Log',
                    reason: "the region uses Log, which is absent from the file's use block",
                    originPath: 'app/Services/Plaid/PlaidAccountService.php',
                    originFirstLine: 120,
                    originLastLine: 168,
                ),
            ],
            items: [
                new BundleItem(
                    lever: Lever::Fetched,
                    assertionId: self::NAMED_REFERENCE_ID,
                    provenance: new Provenance('app/Models/PlaidAccount.php', 'forItem', 41, 58),
                    payload: "public function forItem(PlaidItem \$item)\n{\n    return static::query();\n}",
                    tokens: 180,
                ),
                new BundleItem(
                    lever: Lever::Flagged,
                    assertionId: self::PREMISE_ID,
                    provenance: new Provenance('app/Services/Plaid/PlaidAccountService.php', 'syncFromResponse'),
                    payload: 'ASSUMPTION: this code assumes a surrounding transaction; caller not checked',
                    tokens: 14,
                ),
                new BundleItem(
                    lever: Lever::Fetched,
                    assertionId: self::ABSENCE_ID,
                    provenance: new Provenance('app/Services/Plaid/PlaidAccountService.php'),
                    // Contains a fenced block, so the Markdown writer has to widen its own fence.
                    payload: "/** ```php */\nuse App\\Models\\PlaidAccount;",
                    tokens: 42,
                ),
            ],
            diagnostics: [
                new Diagnostic('call_sites_truncated', self::NAMED_REFERENCE_ID, [
                    'limit' => 20,
                    'subject' => 'forItem',
                    'scope' => 'app/',
                ]),
            ],
            dropped: [
                new DroppedItem('route wiring for reauth endpoint', 'below budget priority', 260),
                new DroppedItem('ExchangePublicTokenRequest validation rules', 'below budget priority', 310),
            ],
            run: self::run(),
            usedTokens: self::USED_TOKENS,
        );
    }

    /**
     * Experiment 2's correct answer: emptiness is a result, not a failure.
     */
    public static function empty(): Bundle
    {
        return new Bundle(
            assertions: [],
            items: [],
            diagnostics: [],
            dropped: [],
            run: self::run(),
            usedTokens: 0,
        );
    }

    public static function run(): RunMetadata
    {
        return new RunMetadata(
            engineVersion: ContractVersion::ENGINE,
            policyVersion: ContractVersion::POLICY,
            frameworkTableVersion: 'fixture',
            budgetTokens: self::BUDGET_TOKENS,
            diffSha: 'deadbeef0000',
            repoSha: null,
        );
    }
}
