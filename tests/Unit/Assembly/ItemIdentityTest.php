<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Assembly;

use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Assembly\TokenEstimate;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Assertion\ResolvedAssertion;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Domain\Source\SourceSlice;
use PHPUnit\Framework\TestCase;

/**
 * M16 · ADR-A021 — two items are the same item when everything a reviewer can see is the same.
 *
 * G4: one member named from two files arrived twice, identical in every field. The copy is not a
 * second fact, because a **fetched** item's provenance is the *declaring* site — so the origin
 * that would have distinguished the two is not in either item.
 *
 * These tests are mostly **negative controls**. The cheap boundaries all reach the right answer on
 * the easy case and destroy something on a harder one, so each field of the identity is asserted
 * by the row that forces it: R4 the member, R5 the path, R6 the kind and reason, R7 the payload,
 * R8 the span. `experiment-16` measured all six candidates; this is the same set held from below.
 */
final class ItemIdentityTest extends TestCase
{
    // ---------------------------------------------------------------- the collapse

    public function testTwoIdenticalFetchedItemsBecomeOne(): void
    {
        $bundle = $this->assemble([
            $this->fetched('App\Services\Calc::total', 'app/A.php', 'app/Services/Calc.php', 'total', 7, 10, 'BODY'),
            $this->fetched('App\Services\Calc::total', 'app/B.php', 'app/Services/Calc.php', 'total', 7, 10, 'BODY'),
        ]);

        self::assertCount(1, $bundle->items, 'one fact, one item');
    }

    public function testThreeIdenticalItemsAlsoBecomeOne(): void
    {
        $bundle = $this->assemble([
            $this->fetched('App\Services\Calc::total', 'app/A.php', 'app/Services/Calc.php', 'total', 7, 10, 'BODY'),
            $this->fetched('App\Services\Calc::total', 'app/B.php', 'app/Services/Calc.php', 'total', 7, 10, 'BODY'),
            $this->fetched('App\Services\Calc::total', 'tests/C.php', 'app/Services/Calc.php', 'total', 7, 10, 'BODY'),
        ]);

        self::assertCount(1, $bundle->items);
    }

    public function testTheSurvivorIsTheFirstResolvedAndTokensFollow(): void
    {
        $bundle = $this->assemble([
            $this->fetched('App\Services\Calc::total', 'app/A.php', 'app/Services/Calc.php', 'total', 7, 10, 'BODY'),
            $this->fetched('App\Services\Calc::total', 'app/B.php', 'app/Services/Calc.php', 'total', 7, 10, 'BODY'),
        ]);

        self::assertSame('BODY', $bundle->items[0]->payload);
        self::assertSame(
            $bundle->items[0]->tokens,
            $bundle->usedTokens,
            'the total counts what is actually in the bundle'
        );
    }

    // ---------------------------------------------------------------- the guard rows

    public function testSamePathDifferentMembersAreTwoItems(): void
    {
        // R4. A rule keyed on the path alone destroys this.
        $bundle = $this->assemble([
            $this->fetched('App\Services\Calc::total', 'app/A.php', 'app/Services/Calc.php', 'total', 7, 10, 'ONE'),
            $this->fetched('App\Services\Calc::subtotal', 'app/A.php', 'app/Services/Calc.php', 'subtotal', 12, 15, 'TWO'),
        ]);

        self::assertCount(2, $bundle->items);
    }

    public function testDifferentPathsWithTheSameMemberNameAreTwoItems(): void
    {
        // R5. A rule keyed on the member name alone destroys this.
        $bundle = $this->assemble([
            $this->fetched('App\Services\Calc::total', 'app/A.php', 'app/Services/Calc.php', 'total', 7, 10, 'ONE'),
            $this->fetched('App\Services\Formatter::total', 'app/A.php', 'app/Services/Formatter.php', 'total', 7, 10, 'TWO'),
        ]);

        self::assertCount(2, $bundle->items);
    }

    public function testTheSameSpanReachedByTwoResolutionRoutesStaysTwoItems(): void
    {
        // R6, the row that decides the shape. Same path, member, span and payload; different kinds,
        // and `ItemPriority` puts them in different drop bands. Collapsing them would silently
        // change what survives a budget. Every location-keyed boundary fails here.
        $bundle = $this->assemble([
            $this->fetched('helper', 'app/A.php', 'app/A.php', 'helper', 18, 21, 'BODY', AssertionKind::SameFileReference),
            $this->fetched('App\Http\A::helper', 'app/B.php', 'app/A.php', 'helper', 18, 21, 'BODY'),
        ]);

        self::assertCount(2, $bundle->items);
        self::assertNotSame($bundle->items[0]->assertionKind, $bundle->items[1]->assertionKind);
    }

    public function testIdenticalTextInTwoClassesStaysTwoItems(): void
    {
        // R7. A rule keyed on the payload text destroys this, and the reviewer is left believing
        // only one of the two classes declares it.
        $bundle = $this->assemble([
            $this->fetched('App\Services\Alpha::run', 'app/A.php', 'app/Services/Alpha.php', 'run', 7, 10, 'SAME'),
            $this->fetched('App\Services\Beta::run', 'app/A.php', 'app/Services/Beta.php', 'run', 7, 10, 'SAME'),
        ]);

        self::assertCount(2, $bundle->items);
    }

    public function testTwoSpansOfOneFileStayTwoItems(): void
    {
        // R8. The `use` block and a member of the same file.
        $bundle = $this->assemble([
            $this->fetched('Auditor', 'app/A.php', 'app/A.php', null, 5, 9, 'USES', AssertionKind::SameFileSymbolAbsence),
            $this->fetched('helper', 'app/A.php', 'app/A.php', 'helper', 18, 21, 'BODY', AssertionKind::SameFileReference),
        ]);

        self::assertCount(2, $bundle->items);
    }

    public function testANullMemberIsNotTheSameAsAnEmptyOne(): void
    {
        // The identity encodes "no member" distinctly, so a `use`-block slice cannot collide with a
        // member that happens to be named ''. Defensive, and cheap.
        $bundle = $this->assemble([
            $this->fetched('X', 'app/A.php', 'app/A.php', null, 5, 9, 'SAME', AssertionKind::SameFileSymbolAbsence),
            $this->fetched('X', 'app/A.php', 'app/A.php', '', 5, 9, 'SAME', AssertionKind::SameFileSymbolAbsence),
        ]);

        self::assertCount(2, $bundle->items);
    }

    // ---------------------------------------------------------------- flags

    public function testTwoFlagsForOneSubjectFromTwoOriginsBothSurvive(): void
    {
        // R10, and M15's row T2. A flag's provenance IS the origin, so these are two different
        // items pointing at two different lines. No special case is needed for that — the identity
        // separates them on its own, which is the property that makes the rule safe.
        $bundle = $this->assemble([
            $this->flagged('App\Models\Order::create', 'app/A.php', 1, 20),
            $this->flagged('App\Models\Order::create', 'app/B.php', 1, 20),
        ]);

        self::assertCount(2, $bundle->items);
        self::assertNotSame($bundle->items[0]->provenance->path, $bundle->items[1]->provenance->path);
    }

    public function testTwoFlagsWithIdenticalTextButDifferentOriginsAreNotCollapsed(): void
    {
        // The B2 trap, from below: both assumption statements are the same sentence. Deduplicating
        // on payload would take one of the two lines away from the reviewer.
        $bundle = $this->assemble([
            $this->flagged('App\Models\Order::create', 'app/A.php', 1, 20),
            $this->flagged('App\Models\Order::create', 'app/B.php', 4, 9),
        ]);

        self::assertSame(
            [$bundle->items[0]->payload, $bundle->items[1]->payload],
            [$bundle->items[0]->payload, $bundle->items[0]->payload],
            'the statements really are identical'
        );
        self::assertCount(2, $bundle->items, 'and they are still two items');
    }

    public function testAFlagAndAFetchOfTheSameSubjectAreNotCollapsed(): void
    {
        // ADR-A020's shape: one assertion yields both. The levers differ, so the identities do.
        $bundle = $this->assemble([
            $this->flagged('App\Models\Order::create', 'app/A.php', 1, 20),
            $this->fetched('App\Models\Order::create', 'app/A.php', 'app/Models/Order.php', 'fillable', 9, 9, 'F'),
        ]);

        self::assertCount(2, $bundle->items);
    }

    // ---------------------------------------------------------------- determinism

    public function testTheResultDoesNotDependOnTheOrderTiesAreSortedIn(): void
    {
        $resolved = [
            $this->fetched('App\Services\Calc::total', 'app/A.php', 'app/Services/Calc.php', 'total', 7, 10, 'BODY'),
            $this->fetched('App\Services\Calc::total', 'app/B.php', 'app/Services/Calc.php', 'total', 7, 10, 'BODY'),
            $this->fetched('App\Services\Calc::subtotal', 'app/B.php', 'app/Services/Calc.php', 'subtotal', 12, 15, 'TWO'),
        ];

        $first = $this->assemble($resolved);

        for ($i = 0; $i < 5; $i++) {
            $again = $this->assemble($resolved);

            self::assertEquals($first->items, $again->items, 'P8');
            self::assertSame($first->usedTokens, $again->usedTokens);
        }
    }

    // ---------------------------------------------------------------- fixtures

    /**
     * @param list<ResolvedAssertion> $resolved
     */
    private function assemble(array $resolved): \ContextDiscovery\Domain\Bundle\Bundle
    {
        return (new BundleAssembler(new TokenEstimate()))->assemble($resolved, 8000);
    }

    private function fetched(
        string $subject,
        string $originPath,
        string $slicePath,
        ?string $member,
        int $firstLine,
        int $lastLine,
        string $text,
        AssertionKind $kind = AssertionKind::NamedReference,
    ): ResolvedAssertion {
        return ResolvedAssertion::fetched(
            $this->assertion($kind, $subject, $originPath),
            [new SourceSlice($slicePath, $member, $firstLine, $lastLine, $text)],
        );
    }

    private function flagged(string $subject, string $originPath, int $first, int $last): ResolvedAssertion
    {
        return ResolvedAssertion::flagged(
            new Assertion(
                AssertionKind::NamedReference,
                $subject,
                $originPath,
                new ChangedRegion($first, $last, ['x'], []),
                'the region depends on ' . $subject . ', whose contract is defined in another file',
            ),
            'ASSUMPTION: ' . $subject . ' is defined elsewhere; not verified',
        );
    }

    private function assertion(AssertionKind $kind, string $subject, string $originPath): Assertion
    {
        return new Assertion(
            $kind,
            $subject,
            $originPath,
            new ChangedRegion(1, 20, ['x'], []),
            'the region depends on ' . $subject . ', whose contract is defined in another file',
        );
    }
}
