<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Flagging;

use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Lever\PremiseCatalogue;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Domain\Source\AncestorDeclaration;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssumptionWriter::class)]
#[CoversClass(PremiseCatalogue::class)]
final class AssumptionWriterTest extends TestCase
{
    private AssumptionWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new AssumptionWriter();
    }

    #[DataProvider('everyCataloguePremise')]
    public function testEveryCataloguePremiseHasExactlyOneFixedStatement(PremiseCatalogue $premise): void
    {
        $statement = $this->writer->statementFor(
            $this->assertion(AssertionKind::UnverifiablePremise, $premise->value)
        );

        self::assertStringStartsWith('ASSUMPTION: ', $statement);
        self::assertStringNotContainsString("\n", $statement, 'a flag is one line');
    }

    /**
     * @return iterable<string, array{PremiseCatalogue}>
     */
    public static function everyCataloguePremise(): iterable
    {
        foreach (PremiseCatalogue::cases() as $premise) {
            yield $premise->value => [$premise];
        }
    }

    public function testTheStatementsAreTheOnesAdrA009Fixes(): void
    {
        self::assertSame(
            'ASSUMPTION: this code assumes a surrounding transaction; caller not checked',
            $this->writer->statementFor(
                $this->assertion(AssertionKind::UnverifiablePremise, 'surrounding-transaction')
            )
        );

        self::assertSame(
            'ASSUMPTION: lock correctness depends on the deployed cache store being atomic',
            $this->writer->statementFor(
                $this->assertion(AssertionKind::UnverifiablePremise, 'atomic-lock-store')
            )
        );

        self::assertSame(
            'ASSUMPTION: locked/filtered lookup assumes supporting schema indexes; migration not verified',
            $this->writer->statementFor(
                $this->assertion(AssertionKind::UnverifiablePremise, 'schema-index-support')
            )
        );
    }

    public function testEveryPremiseHasADistinctStatement(): void
    {
        $statements = array_map(
            fn (PremiseCatalogue $premise): string => $this->writer->statementFor(
                $this->assertion(AssertionKind::UnverifiablePremise, $premise->value)
            ),
            PremiseCatalogue::cases()
        );

        self::assertCount(count($statements), array_unique($statements));
    }

    public function testAPremiseOutsideTheCatalogueCannotBeStated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No such premise');

        $this->writer->statementFor(
            $this->assertion(AssertionKind::UnverifiablePremise, 'looks-a-bit-risky')
        );
    }

    public function testAnUnresolvedReferenceFallsToTheP10Premise(): void
    {
        self::assertSame(
            'ASSUMPTION: named reference could not be resolved on disk; contract unverified',
            $this->writer->statementFor($this->assertion(AssertionKind::NamedReference, 'PlaidAccount'))
        );
    }

    public function testEachFailingLookupStatesItsOwnPremiseAndNeverAShardOne(): void
    {
        // Freeze review 06: one P10 premise per lookup that can fail. "Named reference could not
        // be resolved" is simply false of an unreadable caller scope.
        self::assertSame(
            'ASSUMPTION: callers of this signature could not be searched; scope unreadable',
            $this->writer->statementFor($this->assertion(AssertionKind::ChangedSignature, 'reactivate'))
        );

        self::assertSame(
            'ASSUMPTION: named reference could not be resolved on disk; contract unverified',
            $this->writer->statementFor($this->assertion(AssertionKind::NamedReference, 'PlaidAccount'))
        );
    }

    #[DataProvider('ownFileKinds')]
    public function testTheOwnFileKindsHaveNoFailurePremiseBecauseTheirLookupCannotFail(
        AssertionKind $kind,
    ): void {
        // The extractor skips a changed file it cannot read, so every own-file assertion already
        // has its source in hand: an empty result is a settled answer, never a failure.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No failure premise exists');

        $this->writer->statementFor($this->assertion($kind, 'Log'));
    }

    /**
     * @return iterable<string, array{AssertionKind}>
     */
    public static function ownFileKinds(): iterable
    {
        yield 'a missing import' => [AssertionKind::SameFileSymbolAbsence];
        yield 'a same-file sibling' => [AssertionKind::SameFileReference];
    }

    /**
     * ADR-A028 §5 — S1 is a template filled only from facts the walk read. The "not in its parent"
     * clause appears only when a parent was actually walked, so the sentence never asserts anything
     * about a file that was not opened.
     */
    public function testTheInheritedMemberStatementIsRenderedFromTheWalkAlone(): void
    {
        $oneHop = new AncestorDeclaration(
            'App\Http\Resources\PersonalityResource', 'resolveLocale',
            'App\Http\Resources\Concerns\ResolvesLocale', 'app/Http/Resources/Concerns/ResolvesLocale.php', 7,
            true, 'App\Http\Resources\PersonalityResource', [],
        );

        self::assertSame(
            'ASSUMPTION: resolveLocale() is not declared in PersonalityResource; it is declared in trait App\Http\Resources\Concerns\ResolvesLocale at app/Http/Resources/Concerns/ResolvesLocale.php:7, used by the class itself; body not fetched, contract unverified',
            $this->writer->inheritedMemberStatement($oneHop)
        );

        $twoHops = new AncestorDeclaration(
            'App\Http\Controllers\MenuPdfController', 'success',
            'App\Traits\ApiResponse', 'app/Traits/ApiResponse.php', 9,
            true, 'App\Http\Controllers\Controller', ['App\Http\Controllers\Controller'],
        );

        self::assertSame(
            'ASSUMPTION: success() is not declared in MenuPdfController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified',
            $this->writer->inheritedMemberStatement($twoHops)
        );

        $direct = new AncestorDeclaration('App\OneLevel', 'run', 'App\Support\Direct', 'app/Support/Direct.php', 12, false, null, []);

        self::assertSame(
            'ASSUMPTION: run() is not declared in OneLevel; it is declared in parent App\Support\Direct at app/Support/Direct.php:12; body not fetched, contract unverified',
            $this->writer->inheritedMemberStatement($direct)
        );
    }

    public function testTruncationIsNamedByTheCallerRatherThanInferred(): void
    {
        // A search that hit its bound still returns slices, so the assertion alone cannot say it
        // happened. The pipeline names the premise.
        self::assertSame(
            'ASSUMPTION: additional call sites exist beyond the search bound; not all verified',
            $this->writer->statementForPremise(PremiseCatalogue::CallSitesTruncated)
        );
    }

    public function testTheCatalogueIsClosedAtEightPremises(): void
    {
        // Four earned by findings, three P10 failure premises — one per lookup that can fail
        // (freeze review 06) — and one earned by E5.4 / H.3 and the reviewer pre-check
        // (ADR-A027 §6, ADR-A028). Adding one requires an experiment, a catalogue entry and an
        // ADR-A009 edit (ADR-A003).
        self::assertCount(8, PremiseCatalogue::cases());

        self::assertSame(
            'ASSUMPTION: callers of this signature could not be searched; scope unreadable',
            $this->writer->statementForPremise(PremiseCatalogue::CallerSearchFailed)
        );
    }

    private function assertion(AssertionKind $kind, string $subject): Assertion
    {
        return new Assertion($kind, $subject, 'app/One.php', new ChangedRegion(1, 5, [], []), 'a claim');
    }
}
