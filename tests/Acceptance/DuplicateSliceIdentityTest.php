<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * M16 · experiment-16 through the whole command, against a key written before it ran.
 *
 * `ItemIdentityTest` holds the rule from below. This holds what only the assembled bundle shows:
 * that the collapse fires exactly twice on this fixture, that every guard row survives it, and
 * that framework source is still nowhere near the bundle.
 */
final class DuplicateSliceIdentityTest extends TestCase
{
    private const BUDGET = 8000;

    /**
     * @var array<string, mixed>|null
     */
    private static ?array $bundle = null;

    /**
     * @var list<string>|null
     */
    private static ?array $stderr = null;

    // ---------------------------------------------------------------- R1 · R2 · R3

    public function testOneMemberNamedFromThreeFilesArrivesOnce(): void
    {
        // `Calc::total` is named from two production files and one test, and twice inside one of
        // them. Before ADR-A021 that was three byte-identical items and 75 tokens for one fact.
        $total = array_filter(
            $this->fetchedFrom('app/Services/Calc.php'),
            static fn (array $item): bool => $item['provenance']['member'] === 'total'
        );

        self::assertCount(1, $total);
    }

    public function testNoTwoItemsAreIdentical(): void
    {
        $seen = [];

        foreach (self::bundle()['items'] as $item) {
            $key = json_encode($item);

            self::assertArrayNotHasKey($key, $seen, 'a byte-identical item appears twice');

            $seen[$key] = true;
        }
    }

    // ---------------------------------------------------------------- the guard rows

    public function testSamePathDifferentMembersBothSurvive(): void
    {
        // R4.
        self::assertSame(['subtotal', 'total'], $this->fetchedMembersIn('app/Services/Calc.php'));
    }

    public function testDifferentPathsWithTheSameMemberNameBothSurvive(): void
    {
        // R5.
        self::assertSame(['total'], $this->fetchedMembersIn('app/Services/Formatter.php'));
        self::assertContains('total', $this->fetchedMembersIn('app/Services/Calc.php'));
    }

    public function testOneSpanReachedByThreeResolutionRoutesStaysThreeItems(): void
    {
        // R6, and richer than the key predicted: `ControllerA::helper` arrives as a symbol absence,
        // as a same-file reference, and as a named reference from another file — same path, member,
        // span and text, three different kinds, two different ItemPriority bands.
        $helper = array_filter(
            $this->fetchedFrom('app/Http/ControllerA.php'),
            static fn (array $item): bool => ($item['provenance']['member'] ?? null) === 'helper'
        );

        self::assertCount(3, $helper);
        self::assertSame(
            ['named_reference', 'same_file_reference', 'same_file_symbol_absence'],
            $this->sorted(array_map(static fn (array $i): string => $i['assertion_kind'], $helper)),
        );
    }

    public function testIdenticalMethodBodiesInTwoClassesBothSurvive(): void
    {
        // R7.
        self::assertCount(1, $this->fetchedFrom('app/Services/Alpha.php'));
        self::assertCount(1, $this->fetchedFrom('app/Services/Beta.php'));

        self::assertSame(
            $this->fetchedFrom('app/Services/Alpha.php')[0]['payload'],
            $this->fetchedFrom('app/Services/Beta.php')[0]['payload'],
            'the payloads really are byte-identical'
        );
    }

    public function testTwoSpansOfOneFileBothSurvive(): void
    {
        // R8 · the `use` block, member null, alongside the members.
        $spans = array_map(
            static fn (array $i): array => $i['provenance']['lines'],
            $this->fetchedFrom('app/Http/ControllerA.php')
        );

        self::assertContains([5, 9], $spans, 'the use block');
        self::assertContains([18, 21], $spans, 'and a member');
    }

    public function testAFrameworkReferenceNamedTwiceStillProducesNoItemAndNoVendorSource(): void
    {
        // R9. ADR-A011's successful negative and ADR-A012's floor, unaffected.
        self::assertSame(
            2,
            count(array_filter(
                self::stderr(),
                static fn (string $line): bool => str_starts_with($line, 'framework reference: Illuminate\Support\Facades\Log::info')
            )),
            'one diagnostic per origin'
        );

        foreach (self::bundle()['items'] as $item) {
            self::assertStringStartsNotWith('vendor/', $item['provenance']['path'], 'no vendor path');

            // The declaring tag itself — the one thing that could only have come from reading the
            // facade's own file. A bare mention of `Illuminate\Support\Facades\Log` is NOT that:
            // the changed file's own `use` block contains it, and that block is the project's
            // source, correctly sliced by the own-file absence move.
            self::assertStringNotContainsString(
                '@method static void info',
                $item['payload'],
                'the facade tag was read to settle the reference, and never copied into the bundle'
            );
        }
    }

    public function testTheM14ShapeIsUnchanged(): void
    {
        // R10 · two flags for two origins, one model surface. M14 and M15 both depend on this.
        $flags = array_filter(
            self::bundle()['items'],
            static fn (array $i): bool => $i['lever'] === 'flagged' && str_contains($i['reason'], 'Order::create')
        );

        self::assertCount(2, $flags, 'a flag names its origin, so two origins are two items');
        self::assertSame(['casts', 'fillable'], $this->fetchedMembersIn('app/Models/Order.php'), 'one surface');
    }

    // ---------------------------------------------------------------- the contract

    public function testTheContractAndTheOrderingAreUnchanged(): void
    {
        $bundle = self::bundle();

        self::assertSame(1, $bundle['bundle_version']);
        self::assertSame(['bundle_version', 'budget_tokens', 'used_tokens', 'items', 'dropped'], array_keys($bundle));

        $tokens = array_sum(array_map(static fn (array $i): int => $i['tokens'], $bundle['items']));

        self::assertSame($tokens, $bundle['used_tokens'], 'the total counts what is in the bundle');

        foreach ($bundle['items'] as $item) {
            self::assertSame(['lever', 'reason', 'assertion_kind', 'provenance', 'payload', 'tokens'], array_keys($item));
        }
    }

    public function testTheRunIsDeterministic(): void
    {
        $first = self::invoke();

        for ($i = 0; $i < 4; $i++) {
            self::assertSame($first, self::invoke(), 'P8');
        }
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function sorted(array $values): array
    {
        $values = array_values($values);
        sort($values);

        return $values;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchedFrom(string $path): array
    {
        return array_values(array_filter(
            self::bundle()['items'],
            static fn (array $item): bool => $item['lever'] === 'fetched' && $item['provenance']['path'] === $path
        ));
    }

    /**
     * @return list<string>
     */
    private function fetchedMembersIn(string $path): array
    {
        return array_values(array_filter(array_map(
            static fn (array $item): ?string => $item['provenance']['member'] ?? null,
            $this->fetchedFrom($path)
        )));
    }

    /**
     * @return array<string, mixed>
     */
    private static function bundle(): array
    {
        if (self::$bundle === null) {
            self::invoke();
        }

        /** @var array<string, mixed> */
        return self::$bundle;
    }

    /**
     * @return list<string>
     */
    private static function stderr(): array
    {
        if (self::$stderr === null) {
            self::invoke();
        }

        /** @var list<string> */
        return self::$stderr;
    }

    /**
     * @return array{stdout: string, stderr: string}
     */
    private static function invoke(): array
    {
        $root = dirname(__DIR__, 2);
        $fixture = __DIR__ . '/fixtures/experiment-16';

        self::assertFileExists($fixture . '/diff.patch', 'the experiment-16 fixture is missing');

        $process = proc_open(
            [
                PHP_BINARY,
                $root . '/bin/context-discover',
                '--diff', $fixture . '/diff.patch',
                '--repo', $fixture . '/repo',
                '--budget', (string) self::BUDGET,
                '--format', 'json',
            ],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root
        );

        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), $stderr);

        $bundle = json_decode($stdout, true);

        self::assertIsArray($bundle);

        self::$bundle = $bundle;
        self::$stderr = explode("\n", trim($stderr));

        return ['stdout' => $stdout, 'stderr' => $stderr];
    }
}
