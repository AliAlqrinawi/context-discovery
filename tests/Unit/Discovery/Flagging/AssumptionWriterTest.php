<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Flagging;

use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Lever\PremiseCatalogue;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use InvalidArgumentException;
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

    public function testABoundedCallerSearchFallsToTheTruncationPremise(): void
    {
        self::assertSame(
            'ASSUMPTION: additional call sites exist beyond the search bound; not all verified',
            $this->writer->statementFor($this->assertion(AssertionKind::ChangedSignature, 'reactivate'))
        );
    }

    public function testTheCatalogueIsClosedAtSixPremises(): void
    {
        // Adding one requires an experiment, a catalogue entry and an ADR-A009 edit (ADR-A003).
        self::assertCount(6, PremiseCatalogue::cases());
    }

    private function assertion(AssertionKind $kind, string $subject): Assertion
    {
        return new Assertion($kind, $subject, 'app/One.php', new ChangedRegion(1, 5, [], []), 'a claim');
    }
}
