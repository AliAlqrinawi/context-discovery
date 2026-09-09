<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Lever\PremiseCatalogue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Phase 0 acceptance test: "Phase 1 is done when, for all four Phase 0 commits, the tool's
 * bundle reproduces the minimum-context list in that commit's experiment file — fetching the
 * fetch-type items and flagging the flag-type ones — within budget."
 *
 * It runs the **real command**, as a process, against operator-supplied fixtures.
 *
 * **The four diffs are an operator-supplied prerequisite.** The research repository contains no
 * patch text — the experiments describe commits in a private Laravel + Plaid codebase — so this
 * project ships the expectation files only. Until a fixture is complete this harness **fails
 * loudly** rather than passing or skipping quietly. Fabricating a diff would grade the tool against
 * invented ground truth, which is the one thing the answer-key method exists to prevent (ADR-001).
 *
 * Recall and precision are **reported as counts**, never graded into a pass/fail score: the tool
 * judges nothing (P6), and a human reads the table. What *is* asserted is structural — bundle
 * shape, ordering, determinism, budget honesty — plus each fixture's own Checks column.
 */
final class ExperimentKeyTest extends TestCase
{
    private const MISSING_FIXTURE = 'fixture diff absent — acceptance not run';

    /**
     * The fixed item order from 03-interfaces.md §2.
     *
     * @var list<string>
     */
    private const KIND_ORDER = [
        'same_file_symbol_absence',
        'same_file_reference',
        'changed_signature',
        'changed_return_contract',
        'named_reference',
        'unverifiable_premise',
    ];

    // ---------------------------------------------------------------- the four experiments

    #[DataProvider('experiments')]
    public function testTheBundleReproducesTheMinimumContextList(string $experiment): void
    {
        $expectation = $this->expectationFor($experiment);
        $this->requireCompleteFixture($experiment, $expectation);

        $budget = (int) $expectation['budget'];
        $repository = $expectation['repo'];
        $diff = $this->fixturePath($experiment, 'diff.patch');

        $first = $this->runCommand($repository, $diff, $budget);

        self::assertSame(0, $first['exit'], sprintf("%s: exit 0 expected\n%s", $experiment, $first['stderr']));

        $bundle = json_decode($first['stdout'], true);

        self::assertIsArray($bundle, $experiment . ': stdout must be a parseable bundle');

        $this->assertBundleStructure($experiment, $bundle, $budget);
        $this->assertItemOrdering($experiment, $bundle);
        $this->assertBudgetHonesty($experiment, $bundle);

        // Determinism: same diff, same repository state, byte-identical output.
        $second = $this->runCommand($repository, $diff, $budget);

        self::assertSame($first['stdout'], $second['stdout'], $experiment . ': two runs must be byte-identical');
        self::assertSame($first['stderr'], $second['stderr'], $experiment . ': diagnostics must be byte-identical');

        $outcome = $this->scoreAgainstExpectations($bundle, $expectation['rows']);

        $this->assertExperimentChecks($experiment, $bundle, $first['stderr']);
        $this->report($experiment, $bundle, $outcome);

        self::assertSame(
            [],
            $outcome['missing'],
            sprintf('%s: the bundle does not reproduce every entry of the minimum-context list', $experiment)
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function experiments(): iterable
    {
        foreach (['experiment-01', 'experiment-02', 'experiment-03', 'experiment-04'] as $experiment) {
            yield $experiment => [$experiment];
        }
    }

    // ---------------------------------------------------------------- what can be checked today

    #[DataProvider('experiments')]
    public function testEveryFixtureShipsAParseableExpectationFile(string $experiment): void
    {
        // This half of the fixture is shipped, transcribed from the experiment file's step 4.
        $expectation = $this->expectationFor($experiment);

        self::assertArrayHasKey('rows', $expectation, $experiment);
        self::assertArrayHasKey('repo', $expectation, $experiment);
        self::assertArrayHasKey('budget', $expectation, $experiment);

        foreach ($expectation['rows'] as $row) {
            self::assertContains($row['mark'], ['fetch-expected', 'flag-satisfied', 'fetch-if-named'], $experiment);
            self::assertContains($row['kind'], self::KIND_ORDER, $experiment);
            self::assertNotSame('', $row['expect'], $experiment);
        }
    }

    public function testTheProcessContractHoldsForEveryExitCode(): void
    {
        // 03-interfaces.md §1: 0 bundle produced · 1 usage or input error · 2 repository unreadable.
        // This grades the process contract, not an answer key, so it uses a throwaway repository.
        $repository = $this->throwawayRepository();

        try {
            $diff = $repository . '/change.patch';

            self::assertSame(
                0,
                $this->runCommand($repository, $diff, 8000)['exit'],
                'a bundle was produced'
            );

            self::assertSame(
                1,
                $this->invoke(['--diff', $diff, '--repo', $repository])['exit'],
                '--budget is required and has no default'
            );

            self::assertSame(
                2,
                $this->invoke(['--diff', $diff, '--repo', $repository . '/nowhere', '--budget', '8000'])['exit'],
                'the repository root is unreadable'
            );

            self::assertSame(
                0,
                $this->invoke(['--diff', $diff, '--repo', $repository, '--budget', '8000', '--format', 'markdown'])['exit'],
                'the Markdown projection is produced'
            );
        } finally {
            $this->remove($repository);
        }
    }

    // ---------------------------------------------------------------- the gate

    /**
     * @param array{repo:string,budget:string,rows:list<array{mark:string,kind:string,expect:string}>} $expectation
     */
    private function requireCompleteFixture(string $experiment, array $expectation): void
    {
        $missing = [];

        if (!is_file($this->fixturePath($experiment, 'diff.patch'))) {
            $missing[] = 'diff.patch — export the commit from the Laravel + Plaid codebase';
        }

        if (!is_dir($expectation['repo'])) {
            $missing[] = 'repo — a checkout of that codebase at the commit under review';
        }

        if (preg_match('/^\d+$/', $expectation['budget']) !== 1) {
            $missing[] = 'budget — the token budget this run is measured against (ADR-A008 ships no default)';
        }

        if ($missing === []) {
            return;
        }

        self::fail(sprintf(
            "%s: %s\n\nSupply, in tests/Acceptance/fixtures/%s/:\n  - %s\n\n"
            . "Fabricating a diff would grade the tool against invented ground truth, which is the "
            . "one thing the answer-key method exists to prevent (ADR-001).",
            $experiment,
            self::MISSING_FIXTURE,
            $experiment,
            implode("\n  - ", $missing),
        ));
    }

    // ---------------------------------------------------------------- shared assertions

    /**
     * @param array<string, mixed> $bundle
     */
    private function assertBundleStructure(string $experiment, array $bundle, int $budget): void
    {
        self::assertSame(1, $bundle['bundle_version'], $experiment);
        self::assertSame($budget, $bundle['budget_tokens'], $experiment);
        self::assertIsInt($bundle['used_tokens'], $experiment);
        self::assertIsArray($bundle['items'], $experiment);
        self::assertIsArray($bundle['dropped'], $experiment, );
        self::assertArrayNotHasKey('diagnostics', $bundle, $experiment . ': diagnostics belong on stderr');

        foreach ($bundle['items'] as $index => $item) {
            $where = sprintf('%s item %d', $experiment, $index);

            self::assertSame(
                ['lever', 'reason', 'assertion_kind', 'provenance', 'payload', 'tokens'],
                array_keys($item),
                $where
            );
            self::assertContains($item['lever'], ['fetched', 'flagged'], $where);
            self::assertNotSame('', trim($item['reason']), $where . ': every item is self-justifying (P5)');
            self::assertContains($item['assertion_kind'], self::KIND_ORDER, $where);
            self::assertArrayHasKey('path', $item['provenance'], $where);
            self::assertIsInt($item['tokens'], $where);

            if ($item['lever'] === 'flagged') {
                self::assertStringStartsWith('ASSUMPTION: ', $item['payload'], $where);
            }
        }

        foreach ($bundle['dropped'] as $dropped) {
            self::assertSame(['reason', 'note', 'tokens'], array_keys($dropped), $experiment);
        }
    }

    /**
     * @param array<string, mixed> $bundle
     */
    private function assertItemOrdering(string $experiment, array $bundle): void
    {
        $keys = array_map(
            fn (array $item): array => [
                array_search($item['assertion_kind'], self::KIND_ORDER, true),
                $item['provenance']['path'],
                $item['provenance']['member'] ?? '',
            ],
            $bundle['items']
        );

        $sorted = $keys;
        usort($sorted, static fn (array $a, array $b): int => $a <=> $b);

        self::assertSame($sorted, $keys, $experiment . ': items must be in the fixed order (03-interfaces §2)');
    }

    /**
     * @param array<string, mixed> $bundle
     */
    private function assertBudgetHonesty(string $experiment, array $bundle): void
    {
        self::assertSame(
            array_sum(array_column($bundle['items'], 'tokens')),
            $bundle['used_tokens'],
            $experiment . ': used_tokens is the sum of the items'
        );

        if ($bundle['used_tokens'] <= $bundle['budget_tokens']) {
            return;
        }

        // Over budget is permitted only when flagged items alone outweigh it: a flag is never
        // dropped, and nothing is omitted to make the total fit (P10, approved decision D4).
        self::assertSame(
            [],
            array_values(array_filter($bundle['items'], static fn (array $i): bool => $i['lever'] !== 'flagged')),
            $experiment . ': over budget with fetched items still present'
        );
    }

    /**
     * The Checks column of 06-acceptance.md §1.
     *
     * @param array<string, mixed> $bundle
     */
    private function assertExperimentChecks(string $experiment, array $bundle, string $stderr): void
    {
        $kinds = array_column($bundle['items'], 'assertion_kind');

        match ($experiment) {
            'experiment-01' => $this->assertExperimentOne($bundle, $kinds, $stderr),
            'experiment-02' => $this->assertExperimentTwo($kinds),
            'experiment-03' => $this->assertExperimentThree($bundle),
            'experiment-04' => $this->assertExperimentFour($bundle, $kinds),
            default => self::fail('unknown experiment ' . $experiment),
        };
    }

    /**
     * @param array<string, mixed> $bundle
     * @param list<string>         $kinds
     */
    private function assertExperimentOne(array $bundle, array $kinds, string $stderr): void
    {
        self::assertContains('same_file_symbol_absence', $kinds, 'the missing-import assertion is present');

        $transaction = $this->itemsWithPayload($bundle, PremiseCatalogue::SurroundingTransaction);

        self::assertCount(1, $transaction, 'the transaction premise is present');
        self::assertSame('flagged', $transaction[0]['lever'], 'the transaction item is flag-satisfied');

        // No signature changed, and R3 restricts the caller grep to changed signatures.
        self::assertNotContains('changed_signature', $kinds, 'no caller grep was performed');
        self::assertStringNotContainsString('caller search for', $stderr, 'no caller grep was performed');
    }

    /**
     * @param list<string> $kinds
     */
    private function assertExperimentTwo(array $kinds): void
    {
        // "Pulling the model just in case is a precision failure, not caution."
        self::assertNotContains('named_reference', $kinds, 'nothing may be pulled speculatively');
        self::assertNotContains('unverifiable_premise', $kinds, 'no trigger fires on trace logging');
    }

    /**
     * @param array<string, mixed> $bundle
     */
    private function assertExperimentThree(array $bundle): void
    {
        foreach (
            [
                PremiseCatalogue::AtomicLockStore,
                PremiseCatalogue::SchemaIndexSupport,
                PremiseCatalogue::DataStateAfterBehaviourChange,
            ] as $premise
        ) {
            $flags = $this->itemsWithPayload($bundle, $premise);

            self::assertNotSame([], $flags, $premise->value . ' is present');
            self::assertSame('flagged', $flags[0]['lever'], $premise->value . ' is a flag');
            self::assertNotSame('', $flags[0]['provenance']['path'], $premise->value . ' is attributed');
        }

        // X3: config and schema go through the flag path and only the flag path.
        foreach ($bundle['items'] as $item) {
            if ($item['lever'] !== 'fetched') {
                continue;
            }

            self::assertDoesNotMatchRegularExpression(
                '#(^|/)(config|database/migrations)/#',
                $item['provenance']['path'],
                'no dedicated config or migration resolver is invoked (X3)'
            );
        }
    }

    /**
     * @param array<string, mixed> $bundle
     * @param list<string>         $kinds
     */
    private function assertExperimentFour(array $bundle, array $kinds): void
    {
        self::assertContains('changed_signature', $kinds, 'the reverse-caller item is present');

        foreach ($bundle['items'] as $item) {
            if ($item['assertion_kind'] !== 'changed_signature' || $item['lever'] !== 'fetched') {
                continue;
            }

            // A call site is a line, not a member.
            self::assertArrayNotHasKey('member', $item['provenance'], 'call-site provenance');
            self::assertArrayHasKey('lines', $item['provenance'], 'call-site provenance');
            self::assertSame($item['provenance']['lines'][0], $item['provenance']['lines'][1], 'one line');
        }

        // The declared precision guard for the transaction trigger.
        self::assertNotContains('unverifiable_premise', $kinds, 'no premise is emitted');
    }

    // ---------------------------------------------------------------- scoring (reported, not graded)

    /**
     * @param array<string, mixed>                                     $bundle
     * @param list<array{mark:string,kind:string,expect:string}>        $rows
     *
     * @return array{satisfied:list<string>,missing:list<string>,optional:list<string>,required:int}
     */
    private function scoreAgainstExpectations(array $bundle, array $rows): array
    {
        $satisfied = [];
        $missing = [];
        $optional = [];
        $required = 0;

        foreach ($rows as $row) {
            $label = sprintf('%s/%s', $row['kind'], $row['expect']);
            $found = $this->bundleSatisfies($bundle, $row);

            if ($row['mark'] === 'fetch-if-named') {
                $optional[] = $label . ($found ? ' (present)' : ' (not named in the diff)');

                continue;
            }

            $required++;

            if ($found) {
                $satisfied[] = $label;

                continue;
            }

            $missing[] = $label;
        }

        return ['satisfied' => $satisfied, 'missing' => $missing, 'optional' => $optional, 'required' => $required];
    }

    /**
     * @param array<string, mixed>                              $bundle
     * @param array{mark:string,kind:string,expect:string}       $row
     */
    private function bundleSatisfies(array $bundle, array $row): bool
    {
        $wantFlagged = $row['mark'] === 'flag-satisfied';

        foreach ($bundle['items'] as $item) {
            if ($item['assertion_kind'] !== $row['kind']) {
                continue;
            }

            if ($wantFlagged !== ($item['lever'] === 'flagged')) {
                continue;
            }

            if ($wantFlagged) {
                $premise = PremiseCatalogue::tryFrom($row['expect']);

                if ($premise !== null && $item['payload'] === (new AssumptionWriter())->statementForPremise($premise)) {
                    return true;
                }

                continue;
            }

            if ($row['expect'] === 'use-block' && !isset($item['provenance']['member'])) {
                return true;
            }

            if (($item['provenance']['member'] ?? null) === $row['expect']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed>                                                              $bundle
     * @param array{satisfied:list<string>,missing:list<string>,optional:list<string>,required:int} $outcome
     */
    private function report(string $experiment, array $bundle, array $outcome): void
    {
        $items = count($bundle['items']);
        $matched = count($outcome['satisfied']);

        // Counts, not a grade. Written to stderr so the harness reports without judging (P6).
        fwrite(STDERR, sprintf(
            "\n[%s] recall %d/%d · items %d · matched %d · dropped %d · used %d/%d tokens\n"
            . "         satisfied: %s\n         missing:   %s\n         optional:  %s\n",
            $experiment,
            $matched,
            $outcome['required'],
            $items,
            $matched,
            count($bundle['dropped']),
            $bundle['used_tokens'],
            $bundle['budget_tokens'],
            $outcome['satisfied'] === [] ? '—' : implode(', ', $outcome['satisfied']),
            $outcome['missing'] === [] ? '—' : implode(', ', $outcome['missing']),
            $outcome['optional'] === [] ? '—' : implode(', ', $outcome['optional']),
        ));
    }

    // ---------------------------------------------------------------- fixture and process helpers

    /**
     * @return array{repo:string,budget:string,rows:list<array{mark:string,kind:string,expect:string}>}
     */
    private function expectationFor(string $experiment): array
    {
        $path = $this->fixturePath($experiment, 'expected-context.md');

        self::assertFileExists($path, $experiment . ': the expectation file is shipped by this project');

        $text = (string) file_get_contents($path);

        preg_match('/^\s*repo:\s*(.+)$/m', $text, $repo);
        preg_match('/^\s*budget:\s*(.+)$/m', $text, $budget);
        preg_match_all('/^\|\s*(fetch-expected|flag-satisfied|fetch-if-named)\s*\|([^|]*)\|([^|]*)\|/m', $text, $rows, PREG_SET_ORDER);

        return [
            'repo' => trim($repo[1] ?? ''),
            'budget' => trim($budget[1] ?? ''),
            'rows' => array_map(
                static fn (array $row): array => [
                    'mark' => $row[1],
                    'kind' => trim($row[2]),
                    'expect' => trim($row[3]),
                ],
                $rows
            ),
        ];
    }

    private function fixturePath(string $experiment, string $file): string
    {
        return __DIR__ . '/fixtures/' . $experiment . '/' . $file;
    }

    /**
     * @param array<string, mixed>  $bundle
     *
     * @return list<array<string, mixed>>
     */
    private function itemsWithPayload(array $bundle, PremiseCatalogue $premise): array
    {
        $statement = (new AssumptionWriter())->statementForPremise($premise);

        return array_values(array_filter(
            $bundle['items'],
            static fn (array $item): bool => $item['payload'] === $statement
        ));
    }

    /**
     * @return array{stdout:string,stderr:string,exit:int}
     */
    private function runCommand(string $repository, string $diff, int $budget): array
    {
        return $this->invoke(['--diff', $diff, '--repo', $repository, '--budget', (string) $budget]);
    }

    /**
     * The real command, as a process — argv, wiring, both streams and the exit code.
     *
     * @param list<string> $arguments
     *
     * @return array{stdout:string,stderr:string,exit:int}
     */
    private function invoke(array $arguments): array
    {
        $root = dirname(__DIR__, 2);
        $command = array_merge([PHP_BINARY, $root . '/bin/context-discover'], $arguments);

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root
        );

        self::assertIsResource($process, 'the command could not be started');

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['stdout' => $stdout, 'stderr' => $stderr, 'exit' => proc_close($process)];
    }

    private function throwawayRepository(): string
    {
        $root = sys_get_temp_dir() . '/context-discovery-acceptance-' . uniqid('', true);

        mkdir($root . '/app/Services', 0o777, true);
        file_put_contents($root . '/composer.json', '{"autoload":{"psr-4":{"App\\\\":"app/"}}}');
        file_put_contents(
            $root . '/app/Services/Thing.php',
            "<?php\n\nnamespace App\\Services;\n\nclass Thing\n{\n    public function handle(): void\n    {\n    }\n}\n"
        );
        file_put_contents($root . '/change.patch', implode("\n", [
            '--- a/app/Services/Thing.php',
            '+++ b/app/Services/Thing.php',
            '@@ -6,2 +6,3 @@',
            '    public function handle(): void',
            '    {',
            "+        Log::info('x');",
        ]) . "\n");

        return $root;
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (array_diff((array) scandir($path), ['.', '..']) as $entry) {
            $this->remove($path . '/' . $entry);
        }

        rmdir($path);
    }
}
