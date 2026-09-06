<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * M1 · the Laravel acceptance fixture.
 *
 * `docs/research/M0-laravel-framework-knowledge.md` measured that every `named_reference` flag the
 * tool produced on real Laravel code was a false positive, and separated two independent causes:
 * **A**, the class is not on the application's PSR-4 map, and **B**, the class is placeable but the
 * member is supplied dynamically. M1 turns that measurement into a fixture, *before* anything is
 * implemented, so the next architectural decision is made against a recorded baseline rather than
 * against a recollection.
 *
 * **This harness asserts today's behaviour, not tomorrow's.** The hand-written answer key
 * (`answer-key.json`) states what the tool *should* produce; `baseline-v0.1.0.json` records what it
 * *does* produce at v0.1.0. Asserting the answer key would leave a permanently red suite and would
 * tempt someone to make it green by changing the tool — the one thing this milestone forbids. So:
 *
 * - every run must reproduce the recorded baseline **exactly** — the regression net;
 * - every **guard row** (a row the key marks `match`) is additionally asserted as a live
 *   expectation, because those are the behaviours framework knowledge must not break;
 * - the remaining gap is **reported**, and its census is asserted against the key, so the gap
 *   cannot silently change size either.
 *
 * When framework knowledge does land, each affected scenario's baseline entry is updated in the
 * same commit that changes the behaviour — which is precisely the visibility this fixture exists
 * to create.
 *
 * Two repository variants ship the **same application source** and differ only in `composer.json`:
 *
 * | Variant | PSR-4 map | Isolates |
 * |---|---|---|
 * | `repo-a-app-map` | `App\` only, **no `vendor/`** — the application before `composer install` | problem **A** present |
 * | `repo-b-vendor-map` | plus the framework's own roots, transcribed by hand into `composer.json` | problem **A** removed, so problem **B** is visible alone |
 * | `repo-c-vendor-installed` | `App\` only, **`vendor/` installed** — the same application afterwards | what M6 serves: the map is Composer's, not the application's |
 *
 * Variant B is a **fixture device**. `ComposerPsr4ClassLocator` was not widened (M0 proposal S1 is
 * not implemented); the map is declared in the fixture's own `composer.json`, which is ordinary
 * tool input. Variant B needs `composer install` inside it and is skipped, with instructions, when
 * the framework source is absent — the fixture is worthless if it grades the tool against a copy of
 * Laravel rather than the real thing.
 */
final class LaravelFixtureBaselineTest extends TestCase
{
    private const BUDGET = 20000;

    private const VARIANTS = ['repo-a-app-map', 'repo-b-vendor-map', 'repo-c-vendor-installed'];

    /**
     * Present iff variant B has had `composer install` run in it.
     */
    private const FRAMEWORK_MARKER = 'repo-b-vendor-map/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php';

    /**
     * Variant C needs its own install: its point is that the framework's namespaces are known ONLY
     * to Composer's generated map, which does not exist until `composer install` has run there.
     */
    private const INSTALLED_MARKER = 'repo-c-vendor-installed/vendor/composer/autoload_psr4.php';

    private const PINNED_FRAMEWORK = 'v12.64.0';

    // ---------------------------------------------------------------- the regression net

    /**
     * The whole point of the milestone: run the real command over every scenario, in both variants,
     * and pin the result. A behaviour change anywhere in extraction, resolution, lever choice,
     * assembly or budgeting shows up here as a diff against a recorded fact.
     */
    #[DataProvider('runs')]
    public function testTheRunReproducesTheRecordedV010Baseline(string $variant, string $scenario): void
    {
        $this->requireFrameworkSourceFor($variant);

        $expected = $this->baseline()[$variant][$scenario] ?? null;

        self::assertIsArray($expected, sprintf('%s/%s: no baseline entry is recorded', $variant, $scenario));

        $run = $this->invoke($variant, $scenario);

        self::assertSame(0, $run['exit'], sprintf("%s/%s: exit 0 expected\n%s", $variant, $scenario, $run['stderr']));

        $bundle = json_decode($run['stdout'], true);

        self::assertIsArray($bundle, sprintf('%s/%s: stdout must be a parseable bundle', $variant, $scenario));
        self::assertSame(
            $expected,
            $this->summarise($bundle, $run['stderr']),
            sprintf(
                "%s/%s: the run no longer matches baseline-v0.1.0.json.\n\n"
                . "If this change is intended, update the baseline entry IN THE SAME COMMIT as the\n"
                . "behaviour change, and say in the message which answer-key rows it closes.",
                $variant,
                $scenario
            )
        );
    }

    /**
     * P8, per scenario: same diff, same repository state, byte-identical output on both streams.
     */
    #[DataProvider('runs')]
    public function testTheRunIsDeterministic(string $variant, string $scenario): void
    {
        $this->requireFrameworkSourceFor($variant);

        $first = $this->invoke($variant, $scenario);
        $second = $this->invoke($variant, $scenario);

        self::assertSame($first['stdout'], $second['stdout'], sprintf('%s/%s: bundle', $variant, $scenario));
        self::assertSame($first['stderr'], $second['stderr'], sprintf('%s/%s: diagnostics', $variant, $scenario));
    }

    // ---------------------------------------------------------------- the guards

    /**
     * A **guard row** is one the hand-written key marks `match`: v0.1.0 already does the right
     * thing, and framework knowledge must not take it away. Four kinds live here, and each is a
     * named risk in M0 §12:
     *
     * - the model surface Experiment 1 requires (S02.2) — must stay fetched;
     * - a project-local `create` on a class that is not a Model (S07.1) — must stay fetched, or a
     *   name-matching framework layer has swallowed application code (risk R1);
     * - a genuinely absent class and a misspelled member (S08.1, S08.2) — must stay flagged;
     * - the missing-import absence that is Experiment 1's headline finding (S10.1) — must stay
     *   fetched (risk R2, severity Critical).
     */
    #[DataProvider('guardRows')]
    public function testTheGuardRowHoldsInBothVariants(array $row): void
    {
        foreach (self::VARIANTS as $variant) {
            if ($variant !== 'repo-a-app-map' && !$this->frameworkSourceIsInstalled()) {
                continue;
            }

            $entry = $this->baseline()[$variant][$row['scenario']] ?? null;

            if ($entry === null) {
                continue;
            }

            $flagged = in_array($row['subject'], $entry['flagged_subjects'], true);
            $where = sprintf('%s in %s', $row['id'], $variant);

            match ($row['expected']['item']) {
                'flagged' => self::assertTrue($flagged, $where . ': must remain flagged — ' . $row['why']),
                'fetched' => self::assertFalse($flagged, $where . ': must remain fetched — ' . $row['why']),
                'none' => self::assertFalse($flagged, $where . ': must produce no item — ' . $row['why']),
                default => self::fail($where . ': unknown expected item ' . $row['expected']['item']),
            };
        }
    }

    /**
     * The precision scenario must produce a completely empty bundle. Laravel member names appear in
     * this fixture's comments, docblock, `@see` tag, string literals and a `::class` constant — one
     * of the literals is `Cache::lock`, ADR-A009's own premise trigger — and none of them is a
     * reference. Recorded as a live assertion because M0 proposes reading `@method` tags out of
     * docblocks, and this is the line that separates a facade's docblock from application prose.
     */
    public function testNoReferenceIsFormedFromCommentsStringsOrDocblocks(): void
    {
        foreach (self::VARIANTS as $variant) {
            if ($variant !== 'repo-a-app-map' && !$this->frameworkSourceIsInstalled()) {
                continue;
            }

            $entry = $this->baseline()[$variant]['S09-comments-strings-docblocks'];

            self::assertSame(0, $entry['items'], $variant . ': the bundle must be empty');
            self::assertSame(0, $entry['used_tokens'], $variant);
            self::assertSame([], $entry['stderr'], $variant . ': not even a diagnostic');
        }
    }

    /**
     * The chained forms are outside the closed three-form list (ADR-A003). Their continued absence
     * is the observable form of the extractor not having been widened — a *recorded* false
     * negative, not a pass.
     */
    public function testTheChainedFormsStillProduceNothing(): void
    {
        $entry = $this->baseline()['repo-a-app-map']['S03-eloquent-static-and-chain'];

        self::assertNotContains('App\Models\Package::get', $entry['flagged_subjects'], 'chain links are not extracted');

        $relations = $this->baseline()['repo-a-app-map']['S04-relation-and-instance'];

        self::assertSame([], $relations['flagged_subjects'], 'features()/createMany()/fresh() form no subject');
    }

    // ---------------------------------------------------------------- fixture integrity

    public function testBothVariantsShipTheSameApplicationSource(): void
    {
        $a = $this->digestOf(self::fixtureRoot() . '/repo-a-app-map/app');
        $b = $this->digestOf(self::fixtureRoot() . '/repo-b-vendor-map/app');

        self::assertSame($a, $b, 'the two variants must differ only in composer.json');
        self::assertNotSame([], $a, 'the application source is shipped');
    }

    public function testEveryScenarioShipsADiffAndAnAnswerKeyRow(): void
    {
        $scenarios = self::scenarios();
        $covered = array_unique(array_column($this->answerKey()['rows'], 'scenario'));

        sort($scenarios);
        sort($covered);

        self::assertSame($scenarios, $covered, 'every scenario is answered, and every answer names a scenario');

        foreach ($scenarios as $scenario) {
            self::assertFileExists(self::fixtureRoot() . '/diffs/' . $scenario . '.diff', $scenario);

            foreach (self::VARIANTS as $variant) {
                self::assertArrayHasKey($scenario, $this->baseline()[$variant], $variant . '/' . $scenario);
            }
        }
    }

    public function testTheAnswerKeyIsWellFormed(): void
    {
        $key = $this->answerKey();

        self::assertSame(1, $key['answer_key_version']);
        self::assertSame(self::PINNED_FRAMEWORK, $key['laravel_framework'], 'the key names the version it was written against');
        self::assertSame(self::BUDGET, $key['budget_tokens']);

        $ids = [];

        foreach ($key['rows'] as $row) {
            $where = $row['id'];

            self::assertArrayHasKey($row['classification'], $key['classifications'], $where);
            self::assertContains($row['expected']['item'], ['none', 'fetched', 'flagged'], $where);
            self::assertIsBool($row['expected']['flag'], $where);
            self::assertIsBool($row['expected']['diagnostic'], $where);

            foreach (['a', 'b'] as $variant) {
                self::assertArrayHasKey($row['gap'][$variant], $key['gap_values'], $where);
            }

            // Internal coherence: only an `unresolved` row may expect a flag, and every row that
            // expects a flag must be `unresolved`. That is ADR-A009's rule — a premise exists only
            // where an unverified premise exists — stated as a test.
            self::assertSame(
                $row['classification'] === 'unresolved',
                $row['expected']['flag'],
                $where . ': a flag is expected if and only if the reference is genuinely unresolved'
            );

            self::assertNotSame('', trim($row['why']), $where . ': every row states why');
            self::assertNotSame('', trim($row['input']), $where);
            self::assertNotContains($row['id'], $ids, 'row ids are unique');

            $ids[] = $row['id'];
        }
    }

    /**
     * The hand-written key claims what v0.1.0 currently does. That claim is checked against the
     * measured baseline, so the key cannot drift into wishful thinking.
     */
    public function testTheAnswerKeyAgreesWithTheMeasuredBaselineAboutCurrentBehaviour(): void
    {
        foreach ($this->answerKey()['rows'] as $row) {
            if ($row['subject'] === '(none formed)') {
                continue;
            }

            foreach (['a' => 'repo-a-app-map', 'b' => 'repo-b-vendor-map'] as $short => $variant) {
                $flagged = in_array(
                    $row['subject'],
                    $this->baseline()[$variant][$row['scenario']]['flagged_subjects'],
                    true
                );

                self::assertSame(
                    str_starts_with($row['current'][$short], 'flagged'),
                    $flagged,
                    sprintf('%s: the key says the %s run is "%s"', $row['id'], $variant, $row['current'][$short])
                );
            }
        }
    }

    /**
     * The two problems M0 separated, asserted as separate facts so no future change can merge them.
     */
    public function testProblemAAndProblemBAreSeparable(): void
    {
        $a = $this->baseline()['repo-a-app-map'];
        $b = $this->baseline()['repo-b-vendor-map'];

        // Problem B alone: the class is App\Models\Package in both variants, so the map never
        // mattered, and the flag is identical on both sides.
        self::assertSame(
            $a['S02-eloquent-static-create']['flagged_subjects'],
            $b['S02-eloquent-static-create']['flagged_subjects'],
            'a dynamically supplied member on a placeable class is unaffected by the map'
        );

        // The facade rule M3 added can only act once the class is placeable, so the SAME reference
        // now behaves differently in the two variants — a sharper demonstration of the split than
        // the identical flags this assertion checked before M3.
        self::assertSame(
            ['Illuminate\Support\Facades\DB::transaction', 'Illuminate\Support\Facades\Log::info'],
            $a['S01-facade-static-call']['flagged_subjects'],
            'problem A: unplaceable, so no rule can reach it and the flag stands'
        );
        self::assertSame(
            [],
            $b['S01-facade-static-call']['flagged_subjects'],
            'problem A removed: the facade rule reads the tag in the one file the locator placed'
        );
        self::assertSame(0, $b['S01-facade-static-call']['items'], 'and it fetches no framework source');

        if (!$this->frameworkSourceIsInstalled()) {
            return;
        }

        // Variant C is the realistic application, and it is the reason M6 exists: before ADR-A014 it
        // behaved exactly like variant A — the framework on disk, unplaceable — because only
        // Composer's generated map knows where it lives.
        if (is_file(self::fixtureRoot() . '/' . self::INSTALLED_MARKER)) {
            $c = $this->baseline()['repo-c-vendor-installed'];

            self::assertSame(
                $b['S01-facade-static-call']['flagged_subjects'],
                $c['S01-facade-static-call']['flagged_subjects'],
                'reading Composer\'s own map reaches what hand-widening composer.json reached'
            );
            self::assertSame([], $c['S06-declared-framework-static']['fetched_slices_by_path'],
                'and locating a dependency still fetches none of it');
        }

        // Problem A alone: statically declared framework methods flag in A and resolve in B.
        self::assertNotSame([], $a['S06-declared-framework-static']['flagged_subjects'], 'flagged when unmapped');
        self::assertSame([], $b['S06-declared-framework-static']['flagged_subjects'], 'resolved when mapped');

        // Until M4 this asserted the opposite: that one framework return type expanded into the
        // whole class surface — 115 slices, 13,426 tokens — once the map reached it. That was the
        // cost of resolving a class without deciding what a *dependency* reference means. M4 decided
        // it, so the assertion now records the fix rather than the defect.
        self::assertLessThan(
            2 * $a['S03-eloquent-static-and-chain']['used_tokens'],
            $b['S03-eloquent-static-and-chain']['used_tokens'],
            'placing a dependency class no longer costs anything: its surface is not fetched'
        );
        // The claim is about *vendor* source, and it is stated that way. Asserting an empty map
        // said the same thing only for as long as nothing else was fetched here; ADR-A020 now
        // fetches the project's own `app/Models/Package.php` surface for the very same references,
        // which is application code and exactly what Experiment 1 asks for. Tightened to the
        // guarantee M4 actually made rather than relaxed away from it.
        self::assertSame(
            [],
            array_keys(array_filter(
                $b['S03-eloquent-static-and-chain']['fetched_slices_by_path'],
                static fn (int $count, string $path): bool => str_contains($path, 'vendor/'),
                ARRAY_FILTER_USE_BOTH
            )),
            'and nothing from vendor/ reaches the bundle'
        );
    }

    /**
     * Counts, never a grade (P6). A human reads the table; nothing here decides anything.
     */
    public function testTheGapCensusIsStable(): void
    {
        $census = [];

        foreach ($this->answerKey()['rows'] as $row) {
            foreach (['a', 'b'] as $variant) {
                $key = strtoupper($variant) . ':' . $row['gap'][$variant];
                $census[$key] = ($census[$key] ?? 0) + 1;
            }
        }

        ksort($census);

        self::assertSame(
            [
                // M3 closed three rows (the two facades in variant B, the local scope in both),
                // M4 a fourth (the bare dependency class), M5 the last two (declared dependency
                // members). `over_fetch` is now zero: nothing from vendor/ reaches any bundle.
                'A:false_positive_flag' => 9,
                'A:known_false_negative' => 3,
                'A:match' => 11,
                'B:false_positive_flag' => 4,
                'B:known_false_negative' => 3,
                'B:match' => 16,
            ],
            $census,
            'the recorded gap changed; that is a finding, not a test failure to paper over'
        );

        $lines = '';

        foreach ($census as $label => $count) {
            $lines .= sprintf("         %-28s %d\n", $label, $count);
        }

        fwrite(STDERR, sprintf(
            "\n[laravel-m1] %d scenarios · %d answer-key rows · Laravel %s · framework source %s\n%s",
            count(self::scenarios()),
            count($this->answerKey()['rows']),
            self::PINNED_FRAMEWORK,
            $this->frameworkSourceIsInstalled() ? 'installed' : 'ABSENT (variant B skipped)',
            $lines
        ));
    }

    // ---------------------------------------------------------------- providers

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function runs(): iterable
    {
        foreach (self::VARIANTS as $variant) {
            foreach (self::scenarios() as $scenario) {
                yield $variant . '/' . $scenario => [$variant, $scenario];
            }
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function guardRows(): iterable
    {
        $key = json_decode((string) file_get_contents(self::fixtureRoot() . '/answer-key.json'), true);

        foreach ($key['rows'] as $row) {
            if ($row['gap']['a'] !== 'match' || $row['subject'] === '(none formed)') {
                continue;
            }

            yield $row['id'] => [$row];
        }
    }

    /**
     * @return list<string>
     */
    private static function scenarios(): array
    {
        $names = [];

        foreach ((array) glob(self::fixtureRoot() . '/diffs/*.diff') as $path) {
            $names[] = basename((string) $path, '.diff');
        }

        sort($names);

        return $names;
    }

    // ---------------------------------------------------------------- helpers

    private static function fixtureRoot(): string
    {
        return __DIR__ . '/fixtures/laravel-m1';
    }

    private function frameworkSourceIsInstalled(): bool
    {
        return is_file(self::fixtureRoot() . '/' . self::FRAMEWORK_MARKER);
    }

    private function requireFrameworkSourceFor(string $variant): void
    {
        if ($variant === 'repo-c-vendor-installed' && !is_file(self::fixtureRoot() . '/' . self::INSTALLED_MARKER)) {
            self::markTestSkipped(
                "variant C needs its own install — its whole point is that the framework is known only
"
                . "to Composer's generated map.

"
                . "    composer install --no-interaction --no-scripts \
"
                . '      --working-dir tests/Acceptance/fixtures/laravel-m1/repo-c-vendor-installed'
            );
        }

        if ($variant !== 'repo-b-vendor-map' || $this->frameworkSourceIsInstalled()) {
            return;
        }

        self::markTestSkipped(
            "variant B needs the real framework source.\n\n"
            . "    composer install --no-interaction --no-scripts \\\n"
            . "      --working-dir tests/Acceptance/fixtures/laravel-m1/repo-b-vendor-map\n\n"
            . 'laravel/framework is pinned to ' . self::PINNED_FRAMEWORK . ' and the tree is gitignored, so the '
            . "fixture exercises real Laravel rather than a committed copy that could drift.\n"
            . 'Variant A needs no vendor directory and has already run.'
        );
    }

    /**
     * The recorded v0.1.0 behaviour.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function baseline(): array
    {
        return json_decode((string) file_get_contents(self::fixtureRoot() . '/baseline-v0.1.0.json'), true);
    }

    /**
     * @return array<string, mixed>
     */
    private function answerKey(): array
    {
        return json_decode((string) file_get_contents(self::fixtureRoot() . '/answer-key.json'), true);
    }

    /**
     * The comparable shape of a run: what the bundle contains and what the diagnostics said, with
     * per-path slice counts rather than payload text, so the baseline stays readable and a drift
     * shows as one changed number rather than a wall of source.
     *
     * @param array<string, mixed> $bundle
     *
     * @return array<string, mixed>
     */
    private function summarise(array $bundle, string $stderr): array
    {
        $byPath = [];
        $flagged = [];
        $kinds = [];

        foreach ($bundle['items'] as $item) {
            $kinds[$item['assertion_kind']] = ($kinds[$item['assertion_kind']] ?? 0) + 1;

            if ($item['lever'] === 'fetched') {
                $path = $item['provenance']['path'];
                $byPath[$path] = ($byPath[$path] ?? 0) + 1;

                continue;
            }

            preg_match('/depends on (\S+),/', $item['reason'], $match);
            $flagged[] = $match[1] ?? $item['reason'];
        }

        ksort($byPath);
        ksort($kinds);
        sort($flagged);

        $stderr = trim($stderr);

        return [
            'items' => count($bundle['items']),
            'fetched' => array_sum($byPath),
            'flagged' => count($flagged),
            'used_tokens' => $bundle['used_tokens'],
            'dropped' => count($bundle['dropped']),
            'assertion_kinds' => $kinds,
            'fetched_slices_by_path' => $byPath,
            'flagged_subjects' => $flagged,
            'stderr' => $stderr === '' ? [] : explode("\n", $stderr),
        ];
    }

    /**
     * @return array{stdout:string,stderr:string,exit:int}
     */
    private function invoke(string $variant, string $scenario): array
    {
        $root = dirname(__DIR__, 2);

        $process = proc_open(
            [
                PHP_BINARY,
                $root . '/bin/context-discover',
                '--diff', self::fixtureRoot() . '/diffs/' . $scenario . '.diff',
                '--repo', self::fixtureRoot() . '/' . $variant,
                '--budget', (string) self::BUDGET,
                '--format', 'json',
            ],
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

    /**
     * @return array<string, string>
     */
    private function digestOf(string $directory): array
    {
        $digest = [];

        $walk = static function (string $path, string $prefix) use (&$walk, &$digest): void {
            foreach (array_diff((array) scandir($path), ['.', '..']) as $entry) {
                $absolute = $path . '/' . $entry;
                $relative = $prefix === '' ? (string) $entry : $prefix . '/' . $entry;

                if (is_dir($absolute)) {
                    $walk($absolute, $relative);

                    continue;
                }

                $digest[$relative] = md5_file($absolute);
            }
        };

        $walk($directory, '');
        ksort($digest);

        return $digest;
    }
}
