<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Lever;

use ContextDiscovery\Discovery\Lever\LeverPolicy;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Tests\Fakes\FakeClassLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The exact decision rule from `fetch-vs-flag.md`: named, single and depth-one on disk is
 * fetched; reverse-graph-deep or unknowable from files is flagged.
 */
#[CoversClass(LeverPolicy::class)]
final class LeverPolicyTest extends TestCase
{
    private LeverPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new LeverPolicy();
    }

    #[DataProvider('cheapNamedDepthOneKinds')]
    public function testACheapNamedDepthOneSourceIsFetched(AssertionKind $kind): void
    {
        self::assertSame(
            Lever::Fetched,
            $this->policy->leverFor($this->assertion($kind, 'subject'), new FakeClassLocator())
        );
    }

    /**
     * @return iterable<string, array{AssertionKind}>
     */
    public static function cheapNamedDepthOneKinds(): iterable
    {
        yield 'the file is already on disk and already read' => [AssertionKind::SameFileSymbolAbsence];
        yield 'a sibling is in the same file' => [AssertionKind::SameFileReference];
        yield 'a bounded grep is the cheap side of reverse-caller' => [AssertionKind::ChangedSignature];
    }

    public function testAnUnknowableRuntimeOrDataFactIsFlagged(): void
    {
        self::assertSame(
            Lever::Flagged,
            $this->policy->leverFor(
                $this->assertion(AssertionKind::UnverifiablePremise, 'atomic-lock-store'),
                new FakeClassLocator()
            )
        );
    }

    public function testAResolvableNamedReferenceIsFetched(): void
    {
        $locator = new FakeClassLocator(['App\Models\PlaidAccount' => 'app/Models/PlaidAccount.php']);

        self::assertSame(
            Lever::Fetched,
            $this->policy->leverFor(
                $this->assertion(AssertionKind::NamedReference, 'App\Models\PlaidAccount'),
                $locator
            )
        );
    }

    public function testANamedReferenceThePsr4MapCannotPlaceIsFlagged(): void
    {
        // Nothing is hunted for; the contract is simply stated as unverified (P10).
        self::assertSame(
            Lever::Flagged,
            $this->policy->leverFor(
                $this->assertion(AssertionKind::NamedReference, 'Vendor\Absent\Thing'),
                new FakeClassLocator()
            )
        );
    }

    public function testTheDecisionDependsOnNothingButTheKindAndTheMap(): void
    {
        // No confidence, no weighting, no "probably useful": the same kind and map always agree.
        $locator = new FakeClassLocator(['A' => 'a.php']);

        foreach (AssertionKind::cases() as $kind) {
            self::assertSame(
                $this->policy->leverFor($this->assertion($kind, 'A'), $locator),
                $this->policy->leverFor($this->assertion($kind, 'A'), $locator)
            );
        }
    }

    private function assertion(AssertionKind $kind, string $subject): Assertion
    {
        return new Assertion($kind, $subject, 'app/One.php', new ChangedRegion(1, 5, [], []), 'a claim');
    }
}
