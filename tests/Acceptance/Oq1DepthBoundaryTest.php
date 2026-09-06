<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * M2 · the depth boundary ADR-A010 draws, checked against the files themselves.
 *
 * ADR-A010 (architecture repository, `decisions/ADR-A010-inheritance-and-annotation-traversal.md`)
 * resolves open question OQ1:
 *
 * > Resolution may open exactly **one file beyond the changed file** — the file the `ClassLocator`
 * > places for the class the assertion names. Inheritance (`extends`, `use <Trait>`) and annotation
 * > (`@mixin`) chains are not followed, whether to fetch a slice or merely to verify that a member
 * > exists.
 *
 * and states the admissibility test a future recognition rule must pass:
 *
 * > **Single-file recognition** — a rule is admissible if and only if every fact it depends on is
 * > readable in the changed file or in the single file the `ClassLocator` places for the named class.
 *
 * A test that merely restated that sentence would be documentation wearing a test's clothes. This
 * one instead **measures the fixture**: for every M1 row the ADR classifies, it opens the real files
 * and checks where the member is actually declared. If Laravel moves a method between releases, or
 * if someone edits the fixture, the classification stops being true and this fails — which is the
 * only useful thing a lock can do.
 *
 * It asserts nothing about what the CLI *should* do. The v0.1.0 behaviour these rows produce is
 * already pinned by {@see LaravelFixtureBaselineTest}; this file pins the *reason* that behaviour is
 * currently correct-by-policy, so the two cannot drift apart silently.
 *
 * **Nothing here implements framework knowledge.** No rule is applied; the declaration sites are
 * located by direct inspection inside the test, which is exactly the work the ADR forbids the tool
 * from doing.
 */
final class Oq1DepthBoundaryTest extends TestCase
{
    private const FRAMEWORK = 'repo-b-vendor-map/vendor/laravel/framework/src/Illuminate';

    private const APP = 'repo-a-app-map/app';

    /**
     * ADR-A010's boundary table, transcribed. `hops` counts files opened **beyond the changed
     * file**; the bound is 1.
     *
     * `declaring_fact` is the pattern that must be present in `declared_in` for the row to be true.
     * It differs in kind by row on purpose: a scope is a declared function, a facade member is only
     * a docblock tag, and that asymmetry is part of what ADR-A010 decides.
     *
     * @var array<string, array{member:string, class_file:string, declared_in:string, hops:int, mechanism:string, declaring_fact:string}>
     */
    private const BOUNDARY = [
        // Within the bound — the declaring fact is inside the file the locator already opens.
        'S07.2 Package::active' => [
            'member' => 'scopeActive',
            'class_file' => self::APP . '/Models/Package.php',
            'declared_in' => self::APP . '/Models/Package.php',
            'hops' => 1,
            'mechanism' => 'naming convention, same file',
            'declaring_fact' => '/\bfunction\s+scopeActive\s*\(/',
        ],
        'S01.1 Log::info' => [
            'member' => 'info',
            'class_file' => self::FRAMEWORK . '/Support/Facades/Log.php',
            'declared_in' => self::FRAMEWORK . '/Support/Facades/Log.php',
            'hops' => 1,
            'mechanism' => '@method static tag, same file',
            'declaring_fact' => '/@method\s+static\s+[^\n]*\binfo\s*\(/',
        ],

        // Outside the bound — only a traversal reaches the declaration.
        'S05.1 Package::query' => [
            'member' => 'query',
            'class_file' => self::APP . '/Models/Package.php',
            'declared_in' => self::FRAMEWORK . '/Database/Eloquent/Model.php',
            'hops' => 2,
            'mechanism' => 'extends — language-guaranteed',
            'declaring_fact' => '/\bpublic\s+static\s+function\s+query\s*\(/',
        ],
        'S02.1 Package::create' => [
            'member' => 'create',
            'class_file' => self::APP . '/Models/Package.php',
            'declared_in' => self::FRAMEWORK . '/Database/Eloquent/Builder.php',
            'hops' => 3,
            'mechanism' => '__callStatic forwarding — a Laravel rule',
            'declaring_fact' => '/\bfunction\s+create\s*\(/',
        ],
        'S03.1 Package::where' => [
            'member' => 'where',
            'class_file' => self::APP . '/Models/Package.php',
            'declared_in' => self::FRAMEWORK . '/Database/Eloquent/Builder.php',
            'hops' => 3,
            'mechanism' => '__callStatic forwarding — a Laravel rule',
            'declaring_fact' => '/\bfunction\s+where\s*\(/',
        ],
        'S03.2 Package::orderBy' => [
            'member' => 'orderBy',
            'class_file' => self::APP . '/Models/Package.php',
            'declared_in' => self::FRAMEWORK . '/Database/Query/Builder.php',
            'hops' => 4,
            'mechanism' => '@mixin — an unenforced annotation',
            'declaring_fact' => '/\bfunction\s+orderBy\s*\(/',
        ],
    ];

    // ---------------------------------------------------------------- the boundary itself

    /**
     * Every row the ADR places **outside** the bound must really be outside it: the member is absent
     * from the class's own file, and present in an ancestor. If either half stopped being true, the
     * row would no longer be evidence for anything.
     */
    #[DataProvider('rowsOutsideTheBound')]
    public function testARowOutsideTheBoundIsUnreachableWithoutATraversal(string $label, array $row): void
    {
        $this->requireFrameworkSource();

        self::assertFalse(
            $this->declaresFunction($row['class_file'], $row['member']),
            $label . ': the member must be absent from the class\'s own file, or the row proves nothing'
        );

        self::assertTrue(
            $this->declaresFunction($row['declared_in'], $row['member']),
            $label . ': the member must really be declared at ' . $row['declared_in']
        );
        self::assertMatchesRegularExpression(
            $row['declaring_fact'],
            $this->read($row['declared_in']),
            $label . ': the declaring fact must really be in ' . $row['declared_in']
        );

        self::assertGreaterThan(1, $row['hops'], $label . ': rows outside the bound are at two hops or more');
    }

    /**
     * Every row the ADR places **within** the bound must be reachable from one file. This is the
     * half that unblocks the next milestone, so it is the half most worth pinning: if it stopped
     * holding, "single-file recognition" would be an empty category.
     */
    #[DataProvider('rowsWithinTheBound')]
    public function testARowWithinTheBoundIsSettledByOneFile(string $label, array $row): void
    {
        $this->requireFrameworkSourceFor($row);

        self::assertSame(
            $row['class_file'],
            $row['declared_in'],
            $label . ': within the bound, the declaring fact is in the file the locator already opens'
        );
        self::assertSame(1, $row['hops'], $label);

        // The row must be TRUE, not merely self-consistent: the fact that settles the member has to
        // be present in that one file. Without this, mislabelling a two-hop row as one hop would go
        // unnoticed — which is precisely the drift this test exists to catch.
        self::assertMatchesRegularExpression(
            $row['declaring_fact'],
            $this->read($row['declared_in']),
            $label . ': the declaring fact must really be in ' . $row['declared_in']
        );
    }

    /**
     * S05 is the ADR's minimal decision case, so it gets its own assertions rather than sharing the
     * table's: one real declared method, one `extends`, and no Laravel semantics anywhere in the
     * chain. If this case ever became resolvable at one hop, the ADR would have to be reopened.
     */
    public function testS05IsTheMinimalCaseTheAdrClaimsItIs(): void
    {
        $this->requireFrameworkSource();

        $package = $this->read(self::APP . '/Models/Package.php');

        self::assertMatchesRegularExpression(
            '/\bclass\s+Package\s+extends\s+Model\b/',
            $package,
            'the chain is one plain `extends`'
        );
        self::assertSame(
            ['features', 'scopeActive'],
            $this->functionsIn($package),
            'Package declares exactly two members, and `query` is not one of them'
        );

        $model = $this->read(self::FRAMEWORK . '/Database/Eloquent/Model.php');

        self::assertStringContainsString(
            'public static function query()',
            $model,
            'Model::query is an ordinary declared static method — no __callStatic, no facade, no builder'
        );

        // The whole point of S05: PHP settles this, so no framework knowledge is involved.
        foreach (['__callStatic', 'Facade', '@mixin'] as $mechanism) {
            self::assertStringNotContainsString(
                $mechanism,
                $package,
                'the application side of the minimal case involves no dynamic dispatch of any kind'
            );
        }
    }

    /**
     * The `@mixin` case is at four hops, which is the ADR's third ground for treating annotations
     * separately: even relaxing the bound to two would not reach it.
     */
    public function testTheOnlyMixinCaseIsDeeperThanAnyPlausibleRelaxation(): void
    {
        $this->requireFrameworkSource();

        $row = self::BOUNDARY['S03.2 Package::orderBy'];

        self::assertSame(4, $row['hops']);
        self::assertStringContainsString(
            '@mixin \Illuminate\Database\Query\Builder',
            $this->read(self::FRAMEWORK . '/Database/Eloquent/Builder.php'),
            'the annotation is real, and it is an annotation — nothing enforces it'
        );
        self::assertFalse(
            $this->declaresFunction(self::FRAMEWORK . '/Database/Eloquent/Builder.php', 'orderBy'),
            'orderBy is not declared on the Eloquent builder; only the annotation points at it'
        );
    }

    // ---------------------------------------------------------------- what stays forbidden

    /**
     * **D1**, the guarantee ADR-A010 marks inviolable: resolved sources never become new input. It
     * is P4/X1, which the research marks *never*, and unlike **D2** no experiment can reopen it.
     *
     * Enforced structurally rather than by discipline, exactly as `05-traceability.md` §5 claims:
     * no queue or worklist type exists to hold a pending reference, and resolution hands nothing
     * back to extraction.
     */
    public function testD1HoldsStructurally(): void
    {
        $root = dirname(__DIR__, 2) . '/src';

        // Comments are stripped first: `NamedReferenceResolver`'s docblock says there is "no
        // worklist here that could hold a pending reference", and a check fooled by prose
        // describing the absence of a thing would be worthless.
        foreach (['Discovery/Resolution', 'Pipeline'] as $directory) {
            foreach ((array) glob($root . '/' . $directory . '/*.php') as $path) {
                self::assertDoesNotMatchRegularExpression(
                    '/\b(worklist|pending|queue|frontier|visited|toVisit)\b/i',
                    $this->codeWithoutComments((string) file_get_contents((string) $path)),
                    basename((string) $path)
                        . ': no worklist may exist — depth > 1 must stay unreachable, not merely unconfigured'
                );
            }
        }

        // No resolver may even see an extractor: with no dependency, re-entry is not expressible.
        foreach ((array) glob($root . '/Discovery/Resolution/*.php') as $path) {
            self::assertStringNotContainsString(
                '\\Extraction\\',
                $this->codeWithoutComments((string) file_get_contents((string) $path)),
                basename((string) $path) . ': resolution must not depend on extraction (01-architecture.md §4)'
            );
        }

        // And the one place that holds both: extraction runs exactly once, before resolution, and
        // nothing loops back to it.
        $pipeline = (string) file_get_contents($root . '/Pipeline/DiscoverContext.php');

        self::assertSame(1, substr_count($pipeline, '->extract('), 'stage 3 runs exactly once');
        self::assertLessThan(
            strpos($pipeline, '->resolve(') ?: PHP_INT_MAX,
            strpos($pipeline, '->extract(') ?: PHP_INT_MAX,
            'extraction precedes resolution, and stage 5 never returns to stage 3'
        );
    }

    /**
     * The resolver opens the located file and stops.
     *
     * Counting read *call sites* was the first form of this check, and M3 showed it to be the wrong
     * measure: adding `frameworkDeclarationFor()` introduced a second call that reads **the same
     * file**, which the ADR permits. What the bound actually says is that every read is of the path
     * the `ClassLocator` returned — so that is what is asserted now. A read of anything else, at any
     * number of call sites, is the violation.
     */
    public function testTheResolverReadsOnlyTheFileTheLocatorPlaced(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/src/Discovery/Resolution/NamedReferenceResolver.php'
        );

        self::assertStringContainsString('never reads that file\'s `use` block', $source, 'the stated bound');

        $reads = substr_count($source, '$this->source->text(');

        self::assertGreaterThan(0, $reads, 'the resolver reads at all');
        self::assertSame(
            $reads,
            substr_count($source, '$this->source->text($path)'),
            'every read is of the located path — a read of any other expression is a traversal'
        );

        $assignments = preg_match_all('/\$path\s*=(?!=)\s*(.+?);/', $source, $matches);

        self::assertGreaterThan(0, $assignments);

        foreach ($matches[1] as $expression) {
            self::assertStringContainsString(
                '$this->locator->pathFor(',
                $expression,
                '$path comes only from the class locator, so no second file can be named'
            );
        }

        // And nothing may reach the repository for a path derived from the file's own contents.
        self::assertStringNotContainsString('extends', $this->codeWithoutComments($source), 'no ancestry is read');
        self::assertStringNotContainsString('@mixin', $this->codeWithoutComments($source), 'no annotation is followed');
    }

    // ---------------------------------------------------------------- providers and helpers

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function rowsOutsideTheBound(): iterable
    {
        foreach (self::BOUNDARY as $label => $row) {
            if ($row['hops'] > 1) {
                yield $label => [$label, $row];
            }
        }
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function rowsWithinTheBound(): iterable
    {
        foreach (self::BOUNDARY as $label => $row) {
            if ($row['hops'] === 1) {
                yield $label => [$label, $row];
            }
        }
    }

    private static function fixture(string $relative): string
    {
        return __DIR__ . '/fixtures/laravel-m1/' . $relative;
    }

    private function frameworkSourceIsInstalled(): bool
    {
        return is_file(self::fixture(self::FRAMEWORK . '/Database/Eloquent/Model.php'));
    }

    private function requireFrameworkSource(): void
    {
        if ($this->frameworkSourceIsInstalled()) {
            return;
        }

        self::markTestSkipped(
            "this check reads the real framework source.\n\n"
            . "    composer install --no-interaction --no-scripts \\\n"
            . '      --working-dir tests/Acceptance/fixtures/laravel-m1/repo-b-vendor-map'
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function requireFrameworkSourceFor(array $row): void
    {
        if (!str_starts_with((string) $row['class_file'], self::FRAMEWORK)) {
            return; // an application file — always present.
        }

        $this->requireFrameworkSource();
    }

    private function read(string $relative): string
    {
        $path = self::fixture($relative);

        self::assertFileExists($path);

        return (string) file_get_contents($path);
    }

    /**
     * Identifiers and literals only — every comment and docblock removed, so a structural check can
     * never be satisfied or defeated by something a human wrote *about* the code.
     */
    private function codeWithoutComments(string $source): string
    {
        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    private function declaresFunction(string $relative, string $member): bool
    {
        return preg_match('/\bfunction\s+' . preg_quote($member, '/') . '\s*\(/', $this->read($relative)) === 1;
    }

    /**
     * @return list<string>
     */
    private function functionsIn(string $source): array
    {
        preg_match_all('/\bfunction\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $source, $matches);

        return array_values(array_unique($matches[1]));
    }
}
