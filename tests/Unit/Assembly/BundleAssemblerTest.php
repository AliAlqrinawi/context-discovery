<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Assembly;

use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Assembly\TokenEstimate;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Assertion\ResolvedAssertion;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Domain\Source\SourceSlice;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BundleAssembler::class)]
final class BundleAssemblerTest extends TestCase
{
    private BundleAssembler $assembler;

    protected function setUp(): void
    {
        $this->assembler = new BundleAssembler(new TokenEstimate());
    }

    public function testAFlaggedAssertionBecomesOneItemCarryingTheAssumptionAndItsOrigin(): void
    {
        $resolved = ResolvedAssertion::flagged(
            $this->assertion(
                AssertionKind::UnverifiablePremise,
                'surrounding-transaction',
                'app/Services/Plaid/PlaidAccountService.php',
                120,
                168,
            ),
            'ASSUMPTION: this code assumes a surrounding transaction; caller not checked',
        );

        $bundle = $this->assembler->assemble([$resolved], 8000);

        self::assertCount(1, $bundle->items);

        $item = $bundle->items[0];

        self::assertSame(Lever::Flagged, $item->lever);
        self::assertSame(AssertionKind::UnverifiablePremise, $item->assertionKind);
        self::assertSame('app/Services/Plaid/PlaidAccountService.php', $item->provenance->path);
        self::assertSame(120, $item->provenance->firstLine);
        self::assertSame(168, $item->provenance->lastLine);
        self::assertStringStartsWith('ASSUMPTION:', $item->payload);
        self::assertSame(19, $item->tokens, 'a flag costs almost nothing');
    }

    public function testAFlaggedPremiseCarriesNoMemberBecauseAPremiseNamesNone(): void
    {
        $bundle = $this->assembler->assemble([
            ResolvedAssertion::flagged(
                $this->assertion(AssertionKind::UnverifiablePremise, 'atomic-lock-store', 'app/One.php'),
                'ASSUMPTION: lock store',
            ),
        ], 8000);

        self::assertNull($bundle->items[0]->provenance->member);
    }

    public function testAFlaggedReferenceCarriesTheMemberTheFailingAssertionNamed(): void
    {
        // ADR-A009 as patched at freeze review 04: a flag carries a member only when the failing
        // assertion already names one — an unresolved reference does.
        $bundle = $this->assembler->assemble([
            ResolvedAssertion::flagged(
                $this->assertion(AssertionKind::NamedReference, 'PlaidAccount', 'app/One.php'),
                'ASSUMPTION: named reference could not be resolved on disk; contract unverified',
            ),
        ], 8000);

        self::assertSame('PlaidAccount', $bundle->items[0]->provenance->member);
        self::assertSame('app/One.php', $bundle->items[0]->provenance->path);
    }

    public function testEachSliceBecomesItsOwnItemBecauseProvenanceHoldsOneSource(): void
    {
        $resolved = ResolvedAssertion::fetched(
            $this->assertion(AssertionKind::NamedReference, 'PlaidAccount', 'app/Services/Thing.php'),
            [
                new SourceSlice('app/Models/PlaidAccount.php', 'forItem', 41, 58, 'forItem body'),
                new SourceSlice('app/Models/PlaidAccount.php', 'fillable', 20, 24, 'fillable body'),
            ],
        );

        $bundle = $this->assembler->assemble([$resolved], 8000);

        self::assertCount(2, $bundle->items);
        self::assertSame('fillable', $bundle->items[0]->provenance->member);
        self::assertSame('forItem', $bundle->items[1]->provenance->member);

        foreach ($bundle->items as $item) {
            self::assertSame(Lever::Fetched, $item->lever);
            self::assertSame(
                'the change depends on PlaidAccount',
                $item->reason,
                'both items are justified by the same assertion'
            );
        }
    }

    public function testItemsAreOrderedByKindThenPathThenMember(): void
    {
        $bundle = $this->assembler->assemble([
            ResolvedAssertion::fetched(
                $this->assertion(AssertionKind::NamedReference, 'B', 'app/One.php'),
                [new SourceSlice('app/Zed.php', 'zeta', 1, 2, 'z')],
            ),
            ResolvedAssertion::flagged(
                $this->assertion(AssertionKind::UnverifiablePremise, 'atomic-lock-store', 'app/One.php'),
                'ASSUMPTION: lock store',
            ),
            ResolvedAssertion::fetched(
                $this->assertion(AssertionKind::SameFileSymbolAbsence, 'Log', 'app/One.php'),
                [new SourceSlice('app/One.php', null, 3, 9, 'use block')],
            ),
            ResolvedAssertion::fetched(
                $this->assertion(AssertionKind::ChangedSignature, 'reactivate', 'app/One.php'),
                [new SourceSlice('app/Caller.php', null, 88, 88, 'call site')],
            ),
            ResolvedAssertion::fetched(
                $this->assertion(AssertionKind::SameFileReference, 'upsertFromPlaid', 'app/One.php'),
                [new SourceSlice('app/One.php', 'upsertFromPlaid', 70, 90, 'sibling body')],
            ),
            ResolvedAssertion::fetched(
                $this->assertion(AssertionKind::NamedReference, 'A', 'app/One.php'),
                [
                    new SourceSlice('app/Alpha.php', 'second', 5, 6, 'b'),
                    new SourceSlice('app/Alpha.php', 'first', 1, 2, 'a'),
                ],
            ),
        ], 8000);

        self::assertSame(
            [
                'same_file_symbol_absence:app/One.php:',
                'same_file_reference:app/One.php:upsertFromPlaid',
                'changed_signature:app/Caller.php:',
                'named_reference:app/Alpha.php:first',
                'named_reference:app/Alpha.php:second',
                'named_reference:app/Zed.php:zeta',
                'unverifiable_premise:app/One.php:',
            ],
            array_map(
                static fn ($item): string => sprintf(
                    '%s:%s:%s',
                    $item->assertionKind->value,
                    $item->provenance->path,
                    $item->provenance->member ?? ''
                ),
                $bundle->items
            )
        );
    }

    public function testEqualKeysKeepTheOrderTheyWereResolvedIn(): void
    {
        $slices = [
            new SourceSlice('app/Same.php', null, 10, 10, 'first resolved'),
            new SourceSlice('app/Same.php', null, 20, 20, 'second resolved'),
        ];

        $bundle = $this->assembler->assemble([
            ResolvedAssertion::fetched(
                $this->assertion(AssertionKind::NamedReference, 'X', 'app/One.php'),
                $slices,
            ),
        ], 8000);

        self::assertSame('first resolved', $bundle->items[0]->payload);
        self::assertSame('second resolved', $bundle->items[1]->payload);
    }

    public function testUsedTokensIsTheSumOfTheItemsAndTheBudgetIsEchoed(): void
    {
        $bundle = $this->assembler->assemble([
            ResolvedAssertion::fetched(
                $this->assertion(AssertionKind::NamedReference, 'X', 'app/One.php'),
                [new SourceSlice('app/Alpha.php', 'a', 1, 2, str_repeat('x', 40))],
            ),
            ResolvedAssertion::flagged(
                $this->assertion(AssertionKind::UnverifiablePremise, 'p', 'app/One.php'),
                str_repeat('y', 20),
            ),
        ], 8000);

        self::assertSame(15, $bundle->usedTokens);
        self::assertSame(
            $bundle->usedTokens,
            array_sum(array_map(static fn ($item): int => $item->tokens, $bundle->items))
        );
        self::assertSame(8000, $bundle->budgetTokens);
    }

    public function testTheAssemblerDropsNothingBecauseThatIsNotItsJob(): void
    {
        $bundle = $this->assembler->assemble([
            ResolvedAssertion::fetched(
                $this->assertion(AssertionKind::NamedReference, 'X', 'app/One.php'),
                [new SourceSlice('app/Alpha.php', 'a', 1, 2, str_repeat('x', 4000))],
            ),
        ], 10);

        self::assertSame([], $bundle->dropped);
        self::assertGreaterThan($bundle->budgetTokens, $bundle->usedTokens);
    }

    public function testNoResolvedAssertionsGivesAnEmptyButValidBundle(): void
    {
        $bundle = $this->assembler->assemble([], 8000);

        self::assertSame([], $bundle->items);
        self::assertSame([], $bundle->dropped);
        self::assertSame(0, $bundle->usedTokens);
        self::assertSame(8000, $bundle->budgetTokens);
    }

    public function testAnAssertionWithNoClaimIsRejectedRatherThanAdmittedWithoutAReason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty reason');

        $this->assembler->assemble([
            ResolvedAssertion::fetched(
                new Assertion(
                    AssertionKind::NamedReference,
                    'X',
                    'app/One.php',
                    new ChangedRegion(1, 2, [], []),
                    '   ',
                ),
                [new SourceSlice('app/Alpha.php', 'a', 1, 2, 'text')],
            ),
        ], 8000);
    }

    public function testAFetchedAssertionThatResolvedToNothingIsRejectedRatherThanVanishing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('should have been flagged');

        $this->assembler->assemble([
            ResolvedAssertion::fetched(
                $this->assertion(AssertionKind::NamedReference, 'Missing', 'app/One.php'),
                [],
            ),
        ], 8000);
    }

    private function assertion(
        AssertionKind $kind,
        string $subject,
        string $path,
        int $firstLine = 1,
        int $lastLine = 5,
    ): Assertion {
        return new Assertion(
            $kind,
            $subject,
            $path,
            new ChangedRegion($firstLine, $lastLine, [], []),
            sprintf('the change depends on %s', $subject),
        );
    }
}
