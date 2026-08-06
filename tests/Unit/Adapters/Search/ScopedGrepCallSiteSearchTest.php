<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Adapters\Search;

use ContextDiscovery\Adapters\Search\ScopedGrepCallSiteSearch;
use ContextDiscovery\Domain\Source\CallSite;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScopedGrepCallSiteSearch::class)]
final class ScopedGrepCallSiteSearchTest extends TestCase
{
    public function testItFindsCallSitesWithPathLineAndText(): void
    {
        $sites = $this->search()->callSites('reactivate', 'app/', 20);

        self::assertNotSame([], $sites);

        $first = $sites[0];

        self::assertSame('app/Controllers/PlaidController.php', $first->path);
        self::assertSame(7, $first->line);
        self::assertStringContainsString('reactivate(', $first->lineText);
    }

    public function testResultsFollowFilesUnderOrderThenLineOrder(): void
    {
        // Lexicographic file order, then line order — so the bounded subset is the same subset on
        // every machine (P8).
        self::assertSame(
            [
                ['app/Controllers/PlaidController.php', 7],
                ['app/Repositories/PlaidItemRepository.php', 5],
                ['app/Repositories/PlaidItemRepository.php', 11],
                ['app/Services/Sync.php', 7],
            ],
            array_map(
                static fn (CallSite $site): array => [$site->path, $site->line],
                $this->search()->callSites('reactivate', 'app/', 20)
            )
        );
    }

    public function testTheSearchIsBoundedAndKeepsTheFirstMatches(): void
    {
        $sites = $this->search()->callSites('reactivate', 'app/', 2);

        self::assertCount(2, $sites);
        self::assertSame('app/Controllers/PlaidController.php', $sites[0]->path);
        self::assertSame('app/Repositories/PlaidItemRepository.php', $sites[1]->path);
    }

    public function testABoundOfZeroOrLessFindsNothing(): void
    {
        self::assertSame([], $this->search()->callSites('reactivate', 'app/', 0));
        self::assertSame([], $this->search()->callSites('', 'app/', 20));
    }

    public function testALongerIdentifierIsNotAMatch(): void
    {
        // The fixture contains `deactivate(` and `reactivate(` but no `activate(`. Both are
        // occurrences of a different name, not of this one.
        self::assertSame([], $this->search()->callSites('activate', 'app/', 20));
    }

    public function testWhitespaceBeforeTheParenthesisStillMatches(): void
    {
        $search = new ScopedGrepCallSiteSearch(new FakeSourceRepository([
            'app/Spaced.php' => "<?php\n\$x = \$this->reactivate (\$item);\n",
        ]));

        self::assertCount(1, $search->callSites('reactivate', 'app/', 20));
    }

    public function testTheScopePrefixBoundsTheSearch(): void
    {
        foreach ($this->search()->callSites('reactivate', 'app/', 20) as $site) {
            self::assertStringStartsWith('app/', $site->path, 'nothing outside the scope is read');
        }

        // The same call site is reachable when the scope is widened to include it.
        self::assertSame(
            ['tests/Feature/PlaidTest.php'],
            array_map(
                static fn (CallSite $site): string => $site->path,
                $this->search()->callSites('reactivate', 'tests/', 20)
            )
        );
    }

    public function testTheDeclarationItselfIsReportedRatherThanFiltered(): void
    {
        // ADR-A006 keeps the grep crude: a same-named method on another class and the method's own
        // declaration are both known imprecision, recorded so a scored run can measure it. Removing
        // them would be the semantic resolution the ADR rejects as optimisation before measurement.
        $lines = array_map(
            static fn (CallSite $site): string => trim($site->lineText),
            $this->search()->callSites('reactivate', 'app/', 20)
        );

        self::assertContains('public function reactivate(PlaidItem $item, string $token): void', $lines);
        self::assertContains('$other->reactivate($item);', $lines, 'a same-named method elsewhere is kept');
    }

    public function testTheSearchNeverSpawnsASubprocess(): void
    {
        // ADR-A006: shelling out to grep or ripgrep would make output depend on the host's
        // installed tools and locale (P8) and add an external requirement to a zero-dependency
        // tool. This guards the adapter's own source against acquiring one later.
        $file = (string) (new \ReflectionClass(ScopedGrepCallSiteSearch::class))->getFileName();

        // Comments are stripped first: the docblock names `grep` and `ripgrep` to say they are not
        // used, and a prose mention is not a call.
        $code = '';

        foreach ((array) token_get_all((string) file_get_contents($file)) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        foreach (['exec', 'shell_exec', 'proc_open', 'passthru', 'system', 'popen'] as $function) {
            self::assertDoesNotMatchRegularExpression(
                '/\b' . $function . '\s*\(/',
                $code,
                sprintf('%s() must not appear in the caller search', $function)
            );
        }

        self::assertStringNotContainsString('`', $code, 'no shell backtick operator');
    }

    public function testSearchingTwiceGivesTheSameResults(): void
    {
        $search = $this->search();

        self::assertEquals(
            $search->callSites('reactivate', 'app/', 20),
            $search->callSites('reactivate', 'app/', 20)
        );
    }

    private function search(): ScopedGrepCallSiteSearch
    {
        return new ScopedGrepCallSiteSearch(new FakeSourceRepository([
            'app/Repositories/PlaidItemRepository.php' => <<<'PHP'
<?php

class PlaidItemRepository
{
    public function reactivate(PlaidItem $item, string $token): void
    {
    }

    public function refresh(PlaidItem $item): void
    {
        $this->reactivate($item, 'token');
    }
}
PHP,
            'app/Controllers/PlaidController.php' => <<<'PHP'
<?php

class PlaidController
{
    public function store()
    {
        return $this->items->reactivate($item, $token);
    }
}
PHP,
            'app/Services/Sync.php' => <<<'PHP'
<?php

class Sync
{
    public function run()
    {
        $other->reactivate($item);
        $other->deactivate($item);
    }
}
PHP,
            'app/README.md' => 'reactivate( is mentioned here but this is not php',
            'tests/Feature/PlaidTest.php' => "<?php\n\$this->reactivate(\$item);\n",
        ]));
    }
}
