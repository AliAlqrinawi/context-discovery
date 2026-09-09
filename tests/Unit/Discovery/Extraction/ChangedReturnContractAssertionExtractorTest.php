<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Extraction;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Discovery\Extraction\ChangedReturnContractAssertionExtractor;
use ContextDiscovery\Discovery\Framework\LaravelFrameworkKnowledge;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A body that still satisfies its signature but no longer returns the same *kind* of value.
 *
 * `getAll(...): Collection` whose body goes from `$query->get()` to `$query->first()` type-checks,
 * passes `ChangedSignatureAssertionExtractor` untouched, and breaks every caller that iterates the
 * result. Nothing in the diff shows those callers.
 *
 * Most of this file is negative controls, because the danger here is noise: a rule that fired on
 * any body change would ask "who calls this?" of every ordinary refactor. Four conditions must hold
 * together, and each test below removes exactly one of them.
 */
final class ChangedReturnContractAssertionExtractorTest extends TestCase
{
    /**
     * `--repo` is the **post-image** tree (`03-interfaces.md` §1, ADR-A023), so the added return is a
     * line that really stands in this file — that is what lets the extractor recover its number
     * instead of trusting the hunk's start. Line 11 is the return; `fileWith()` puts the added line
     * there.
     */
    private const FILE = <<<'PHP'
        <?php

        namespace App\Repositories;

        class BranchRepository
        {
            public function getAll(?bool $showInFooter = null): Collection
            {
                $query = Branch::orderBy('order');

                return $query->get();
            }

            public function untouched(): int
            {
                return 1;
            }
        }
        PHP;

    // ---------------------------------------------------------------- Case 1 · the finding

    public function testACollectionBecomingASingleValueIsACardinalityChange(): void
    {
        $assertions = $this->extract(['        return $query->first();'], ['        return $query->get();']);

        self::assertCount(1, $assertions);
        self::assertSame(AssertionKind::ChangedReturnContract, $assertions[0]->kind);
        self::assertSame('getAll', $assertions[0]->subject, 'the subject is the member a caller search can grep for');
        self::assertStringContainsString('a single value or null', $assertions[0]->claim);
        self::assertStringContainsString('a collection', $assertions[0]->claim);
    }

    public function testAScalarFinisherIsAlsoAContractChange(): void
    {
        $assertions = $this->extract(['        return $query->count();'], ['        return $query->get();']);

        self::assertCount(1, $assertions);
        self::assertStringContainsString('a scalar', $assertions[0]->claim);
    }

    public function testTheAssertionCarriesTheRegionItWasRaisedIn(): void
    {
        $assertions = $this->extract(['        return $query->first();'], ['        return $query->get();']);

        self::assertSame('app/Repositories/BranchRepository.php', $assertions[0]->originPath);
        self::assertSame(9, $assertions[0]->originRegion->firstLine);
    }

    // ---------------------------------------------------------------- Case 2 · negative controls

    #[DataProvider('changesThatAreNotReturnContractChanges')]
    public function testAnOrdinaryBodyChangeYieldsNothing(string $why, array $added, array $removed): void
    {
        self::assertSame([], $this->extract($added, $removed), $why);
    }

    /**
     * @return iterable<string, array{string, list<string>, list<string>}>
     */
    public static function changesThatAreNotReturnContractChanges(): iterable
    {
        yield 'a renamed helper call in an assignment' => [
            'no return statement changed, so no contract claim is available',
            ['        $x = calculateSomethingElse();'],
            ['        $x = calculateSomething();'],
        ];

        yield 'a changed string literal' => [
            'a value change is not a shape change',
            ["        \$name = 'bar';"],
            ["        \$name = 'foo';"],
        ];

        yield 'a returned project helper the table does not know' => [
            'an unknown finisher must be passed over, never assumed (ADR-A003)',
            ['        return $this->buildSomethingElse();'],
            ['        return $this->buildSomething();'],
        ];

        yield 'both sides in the same cardinality class' => [
            'get() to all() is many-to-many and says nothing about the contract',
            ['        return $query->all();'],
            ['        return $query->get();'],
        ];

        yield 'a return added but none removed' => [
            'with one side missing there is nothing to compare',
            ['        return $query->first();'],
            [],
        ];

        yield 'a return removed but none added' => [
            'with one side missing there is nothing to compare',
            [],
            ['        return $query->get();'],
        ];

        yield 'the finisher moved but stayed a collection through a chain' => [
            'only the terminal call decides, and it is unchanged',
            ['        return $query->where("x", 1)->get();'],
            ['        return $query->get();'],
        ];

        yield 'reformatting a return' => [
            'the terminal call is identical',
            ['        return $query->get() ;'],
            ['        return $query->get();'],
        ];
    }

    public function testARegionOutsideAnyMemberYieldsNothing(): void
    {
        // The added return stands at line 3, where the namespace declaration was: no enclosing
        // member, so no subject a caller search could grep for.
        $added = ['return $query->first();'];

        $lines = explode("\n", self::FILE);
        $lines[2] = $added[0];

        $region = new ChangedRegion(3, 3, $added, ['        return $query->get();']);
        $file = new ChangedFile('app/Repositories/BranchRepository.php', [$region], []);

        self::assertSame([], $this->extractor()->forRegion($file, $region, implode("\n", $lines)));
    }

    public function testAnAddedReturnTheSpanDoesNotContainYieldsNothing(): void
    {
        // The span is real but the added line is nowhere inside it — the tree is not the post-image
        // the region describes. Guessing a member from the hunk's start is what caused the bug this
        // control locks: with no line to stand on, the honest answer is nothing.
        $region = new ChangedRegion(9, 12, ['        return $query->first();'], ['        return $query->get();']);
        $file = new ChangedFile('app/Repositories/BranchRepository.php', [$region], []);

        self::assertSame([], $this->extractor()->forRegion($file, $region, self::FILE));
    }

    // ---------------------------------------------------------------- fixtures

    /**
     * @param list<string> $added
     * @param list<string> $removed
     *
     * @return list<\ContextDiscovery\Domain\Assertion\Assertion>
     */
    private function extract(array $added, array $removed): array
    {
        // 9..12 is the span git gives a one-line change with its usual context — it opens *above*
        // the changed line, which is precisely the offset the extractor must not mistake for it.
        $region = new ChangedRegion(9, 12, $added, $removed);
        $file = new ChangedFile('app/Repositories/BranchRepository.php', [$region], []);

        return $this->extractor()->forRegion($file, $region, $this->fileWith($added));
    }

    /**
     * The fixture as the reviewed tree holds it: whatever the region added standing at line 11.
     *
     * @param list<string> $added
     */
    private function fileWith(array $added): string
    {
        $lines = explode("\n", self::FILE);

        if ($added !== []) {
            $lines[10] = $added[0];
        }

        return implode("\n", $lines);
    }

    private function extractor(): ChangedReturnContractAssertionExtractor
    {
        return new ChangedReturnContractAssertionExtractor(
            new TokenizerMemberSlicer(),
            new LaravelFrameworkKnowledge(),
        );
    }
}
