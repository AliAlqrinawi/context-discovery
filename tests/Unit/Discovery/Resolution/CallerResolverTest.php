<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Resolution;

use ContextDiscovery\Adapters\Search\ScopedGrepCallSiteSearch;
use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Resolution\CallerResolver;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Domain\Source\CallSite;
use ContextDiscovery\Domain\Source\SourceSlice;
use ContextDiscovery\Ports\CallSiteSearch;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CallerResolver::class)]
final class CallerResolverTest extends TestCase
{
    public function testAChangedSignatureResolvesToCallSitesWithLineProvenance(): void
    {
        $slices = $this->resolver()->resolve($this->assertion(AssertionKind::ChangedSignature, 'reactivate'));

        self::assertCount(2, $slices);

        self::assertSame('app/Controllers/PlaidController.php', $slices[0]->path);
        self::assertNull($slices[0]->member, 'a call site is a line, not a member');
        self::assertSame($slices[0]->firstLine, $slices[0]->lastLine, 'a call site spans one line');
        self::assertStringContainsString('reactivate(', $slices[0]->text);
    }

    #[DataProvider('kindsThatAreNotSignatureChanges')]
    public function testTheGrepRunsForChangedSignatureOnlyAndNoSearchIsPerformed(AssertionKind $kind): void
    {
        // A caller question that is not a signature change — Exp 1's "is this wrapped in a
        // transaction?" — is flagged, never searched (P2, ADR-A006). The search must not even run.
        $search = $this->spy();
        $resolver = new CallerResolver($search, $this->repository(), 'app/', 20);

        self::assertSame([], $resolver->resolve($this->assertion($kind, 'syncFromResponse')));
        self::assertFalse($search->wasCalled, 'no reverse-graph work is done for a flag-type question');
    }

    /**
     * @return iterable<string, array{AssertionKind}>
     */
    public static function kindsThatAreNotSignatureChanges(): iterable
    {
        yield 'the transaction premise' => [AssertionKind::UnverifiablePremise];
        yield 'a missing import' => [AssertionKind::SameFileSymbolAbsence];
        yield 'a same-file sibling' => [AssertionKind::SameFileReference];
        yield 'a cross-file reference' => [AssertionKind::NamedReference];
    }

    public function testTheSearchIsAskedForOneMoreThanTheBoundSoTruncationIsCertain(): void
    {
        $search = $this->spy();

        (new CallerResolver($search, $this->repository(), 'app/', 20))
            ->resolve($this->assertion(AssertionKind::ChangedSignature, 'reactivate'));

        self::assertSame(21, $search->max);
        self::assertSame('app/', $search->scope);
        self::assertSame('reactivate', $search->method);
    }

    public function testAnOverflowingSearchReturnsMoreThanTheBoundSoTheCallerCanTell(): void
    {
        $resolver = new CallerResolver($this->searchReturning(5), $this->repository(), 'app/', 3);

        self::assertCount(4, $resolver->resolve($this->assertion(AssertionKind::ChangedSignature, 'x')));
        self::assertSame(3, $resolver->bound());
    }

    public function testTheBoundAndScopeAreVisibleToTheCaller(): void
    {
        $resolver = new CallerResolver($this->spy(), $this->repository(), 'src/', 5);

        self::assertSame(5, $resolver->bound());
        self::assertSame('src/', $resolver->scope());
    }

    // ------------------------------------------------------------ failure vs successful negative

    public function testASearchThatCompletesWithZeroCallSitesIsASuccessfulNegative(): void
    {
        // Freeze review 05: the question was asked and answered "none". Nothing is unverified, so
        // no item and no flag — the pipeline records one diagnostic instead.
        $assertion = $this->assertion(AssertionKind::ChangedSignature, 'neverCalled');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertTrue($this->resolver()->lookupRan($assertion), 'the scope held PHP files, so it was searched');
    }

    public function testAnUnreadableScopeIsALookupFailure(): void
    {
        // Nothing could be searched, so an empty result is a failure and is flagged (P10).
        $empty = new FakeSourceRepository();
        $resolver = new CallerResolver(new ScopedGrepCallSiteSearch($empty), $empty, 'app/', 20);
        $assertion = $this->assertion(AssertionKind::ChangedSignature, 'reactivate');

        self::assertSame([], $resolver->resolve($assertion));
        self::assertFalse($resolver->lookupRan($assertion));
    }

    public function testAFailedCallerSearchIsStatedWithItsOwnPremise(): void
    {
        // Freeze review 06: `caller-search-failed`, never `unresolved-reference` — nothing about a
        // named reference failed here.
        $empty = new FakeSourceRepository();
        $resolver = new CallerResolver(new ScopedGrepCallSiteSearch($empty), $empty, 'app/', 20);
        $assertion = $this->assertion(AssertionKind::ChangedSignature, 'reactivate');

        self::assertFalse($resolver->lookupRan($assertion));

        $statement = (new AssumptionWriter())->statementFor($assertion);

        self::assertSame(
            'ASSUMPTION: callers of this signature could not be searched; scope unreadable',
            $statement
        );
        self::assertStringNotContainsString('named reference', $statement);
    }

    public function testAScopeThatHoldsNoPhpFilesIsALookupFailure(): void
    {
        $docsOnly = new FakeSourceRepository(['app/README.md' => 'reactivate( in prose']);
        $resolver = new CallerResolver(new ScopedGrepCallSiteSearch($docsOnly), $docsOnly, 'app/', 20);

        self::assertFalse($resolver->lookupRan($this->assertion(AssertionKind::ChangedSignature, 'reactivate')));
    }

    #[DataProvider('kindsThatAreNotSignatureChanges')]
    public function testOnlyAChangedSignatureCanEverBeASuccessfulNegativeHere(AssertionKind $kind): void
    {
        self::assertFalse($this->resolver()->lookupRan($this->assertion($kind, 'x')));
    }

    public function testResolvingTwiceGivesTheSameSlices(): void
    {
        $resolver = $this->resolver();
        $assertion = $this->assertion(AssertionKind::ChangedSignature, 'reactivate');

        self::assertEquals($resolver->resolve($assertion), $resolver->resolve($assertion));
    }

    // ------------------------------------------------------------ helpers

    private function resolver(): CallerResolver
    {
        $source = $this->repository();

        return new CallerResolver(new ScopedGrepCallSiteSearch($source), $source, 'app/', 20);
    }

    private function repository(): FakeSourceRepository
    {
        return new FakeSourceRepository([
            'app/Controllers/PlaidController.php' => "<?php\n\$this->items->reactivate(\$item);\n",
            'app/Services/Sync.php' => "<?php\n\n\$other->reactivate(\$item);\n",
        ]);
    }

    private function spy(): CallSiteSearch
    {
        return new class implements CallSiteSearch {
            public bool $wasCalled = false;
            public ?string $method = null;
            public ?string $scope = null;
            public ?int $max = null;

            /**
             * @return list<CallSite>
             */
            public function callSites(string $methodName, string $scopePrefix, int $max): array
            {
                $this->wasCalled = true;
                $this->method = $methodName;
                $this->scope = $scopePrefix;
                $this->max = $max;

                return [];
            }
        };
    }

    private function searchReturning(int $count): CallSiteSearch
    {
        return new class ($count) implements CallSiteSearch {
            public function __construct(private readonly int $count)
            {
            }

            /**
             * @return list<CallSite>
             */
            public function callSites(string $methodName, string $scopePrefix, int $max): array
            {
                $sites = [];

                for ($i = 1; $i <= min($this->count, $max); $i++) {
                    $sites[] = new CallSite('app/Caller.php', $i, sprintf('line %d', $i));
                }

                return $sites;
            }
        };
    }

    private function assertion(AssertionKind $kind, string $subject): Assertion
    {
        return new Assertion(
            $kind,
            $subject,
            'app/Repositories/PlaidItemRepository.php',
            new ChangedRegion(20, 24, [], []),
            'a claim',
        );
    }
}
