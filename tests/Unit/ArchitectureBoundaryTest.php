<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The static check `02-project-structure.md` §4 sanctions: "Rules, enforceable by review or a
 * static check."
 *
 * Every assertion here reproduces a rule the frozen contract already states — a dependency
 * direction, a naming convention, an exclusion, a determinism guarantee. Nothing new is decided.
 * The point is that these rules stop depending on someone remembering them: the boundary the
 * architecture draws is checked by the suite that runs on every change.
 *
 * This is the guard that catches the failure mode ADR-003 names — the tool quietly growing into
 * the open-ended context engine the evidence does not justify.
 */
final class ArchitectureBoundaryTest extends TestCase
{
    /**
     * `02-project-structure.md` §2: what each group may know about. Dependencies point inward, and
     * this is the whole permitted edge set — anything outside it is a violation, which also makes
     * the graph acyclic by construction (§4 rules 1–3, 5).
     *
     * @var array<string, list<string>>
     */
    private const MAY_DEPEND_ON = [
        'Domain' => [],
        'Ports' => ['Domain'],
        'Discovery' => ['Domain', 'Ports'],
        'Assembly' => ['Domain', 'Ports'],
        'Pipeline' => ['Domain', 'Ports', 'Discovery', 'Assembly'],
        'Adapters' => ['Domain', 'Ports'],
        'Cli' => ['Domain', 'Ports', 'Discovery', 'Assembly', 'Pipeline', 'Adapters'],
    ];

    /**
     * `02-project-structure.md` §5. Each one signals a specific way the "context engine" idea
     * metastasises.
     *
     * @var list<string>
     */
    private const FORBIDDEN_CLASS_SUFFIXES = [
        'Engine', 'Agent', 'Plugin', 'EventBus', 'Listener', 'Cache',
        'Index', 'Graph', 'Score', 'Severity', 'Reviewer', 'Client',
    ];

    // ---------------------------------------------------------------- dependency direction

    #[DataProvider('sourceFiles')]
    public function testDependenciesPointInward(string $path, string $source): void
    {
        $group = $this->groupOf($path);

        self::assertArrayHasKey($group, self::MAY_DEPEND_ON, sprintf('%s sits outside every group', $path));

        $allowed = self::MAY_DEPEND_ON[$group];

        foreach ($this->projectImports($source) as $import) {
            $target = $this->groupOfNamespace($import);

            if ($target === $group) {
                continue; // within a group, direction is governed by §3's domain rules.
            }

            self::assertContains(
                $target,
                $allowed,
                sprintf('%s (%s) may not depend on %s — %s', $path, $group, $target, $import)
            );
        }
    }

    public function testDomainDependsOnNothingOutsideDomain(): void
    {
        // §4 rule 1. `ResolvedAssertion` needs `Lever` and `SourceSlice`, and `BundleItem` needs
        // `AssertionKind`, so the rule is "nothing outside the Domain layer" — the intra-Domain
        // direction is governed by §3's three boundary rules, checked below.
        foreach (self::sourceFiles() as [$path, $source]) {
            if ($this->groupOf($path) !== 'Domain') {
                continue;
            }

            foreach ($this->projectImports($source) as $import) {
                self::assertSame(
                    'Domain',
                    $this->groupOfNamespace($import),
                    sprintf('%s reaches outside the Domain layer: %s', $path, $import)
                );
            }
        }
    }

    public function testTheThreeDomainBoundaryRulesHold(): void
    {
        // §3: Diff types never reference Assertion or Bundle types; Bundle types reference no Diff
        // type. (Assertion may reference a diff *origin*, which is why it is not symmetric.)
        foreach (self::sourceFiles() as [$path, $source]) {
            $imports = $this->projectImports($source);

            if (str_starts_with($path, 'src/Domain/Diff/')) {
                foreach ($imports as $import) {
                    self::assertStringNotContainsString('Domain\Assertion', $import, $path);
                    self::assertStringNotContainsString('Domain\Bundle', $import, $path);
                }
            }

            if (str_starts_with($path, 'src/Domain/Bundle/')) {
                foreach ($imports as $import) {
                    self::assertStringNotContainsString('Domain\Diff', $import, $path);
                }
            }
        }
    }

    public function testWiringIsTheOnlyPlaceAConcreteAdapterIsNamed(): void
    {
        // §4 rule 4: adding an adapter must change exactly one file.
        $namingAnAdapter = [];

        foreach ([...self::sourceFiles(), ['bin/context-discover', $this->read('bin/context-discover')]] as [$path, $source]) {
            if (str_starts_with($path, 'src/Adapters/')) {
                continue; // an adapter naming a sibling is not the concern.
            }

            foreach ($this->projectImports($source) as $import) {
                if (str_starts_with($import, 'Adapters\\')) {
                    $namingAnAdapter[$path] = true;
                }
            }
        }

        self::assertSame(['src/Cli/Wiring.php'], array_keys($namingAnAdapter));
    }

    // ---------------------------------------------------------------- naming

    #[DataProvider('sourceFiles')]
    public function testNoForbiddenClassName(string $path, string $source): void
    {
        $name = basename($path, '.php');

        foreach (self::FORBIDDEN_CLASS_SUFFIXES as $forbidden) {
            self::assertStringEndsNotWith(
                $forbidden,
                $name,
                sprintf('%s signals scope escape (§5 forbidden names)', $name)
            );
        }
    }

    #[DataProvider('sourceFiles')]
    public function testNoBannedVocabularyInIdentifiers(string $path, string $source): void
    {
        // §5: the research repository's vocabulary, verbatim. No synonyms. Comments are exempt —
        // `ItemPriority` has to be able to say it is *not* a relevance score.
        $offenders = [];

        foreach ($this->identifiers($source) as $identifier) {
            foreach (['relevance', 'score', 'candidate'] as $banned) {
                if (stripos($identifier, $banned) !== false) {
                    $offenders[] = $identifier;
                }
            }
        }

        self::assertSame(
            [],
            array_values(array_unique($offenders)),
            sprintf('%s uses a synonym the vocabulary rule bans', $path)
        );
    }

    public function testInterfacesAreNamedForTheCapabilityWithoutASuffix(): void
    {
        foreach (self::sourceFiles() as [$path, $source]) {
            if (!str_contains($source, "\ninterface ")) {
                continue;
            }

            self::assertStringEndsNotWith('Interface', basename($path, '.php'), $path);
        }
    }

    #[DataProvider('suffixConventions')]
    public function testTheSuffixConventionsHold(string $directory, string $suffix): void
    {
        foreach ($this->filesIn($directory) as $path) {
            self::assertStringEndsWith($suffix, basename($path, '.php'), $path);
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function suffixConventions(): iterable
    {
        yield 'extractors' => ['src/Discovery/Extraction', 'AssertionExtractor'];
        yield 'resolvers' => ['src/Discovery/Resolution', 'Resolver'];
        yield 'writers' => ['src/Adapters/Serialization', 'Writer'];
    }

    public function testThereIsExactlyOneDecisionObjectAndItIsAPolicy(): void
    {
        $policies = [];

        foreach (self::sourceFiles() as [$path, $source]) {
            if (str_ends_with(basename($path, '.php'), 'Policy')) {
                $policies[] = $path;
            }
        }

        self::assertSame(['src/Discovery/Lever/LeverPolicy.php'], $policies);
    }

    #[DataProvider('sourceFiles')]
    public function testEveryFileDeclaresStrictTypes(string $path, string $source): void
    {
        self::assertStringContainsString('declare(strict_types=1);', $source, $path);
    }

    #[DataProvider('domainFiles')]
    public function testDomainValueObjectsAreImmutable(string $path, string $source): void
    {
        self::assertDoesNotMatchRegularExpression(
            '/\bpublic\s+(?!readonly\b|function\b|const\b|static\b)[^\s;{(]+\s+\$/',
            $source,
            sprintf('%s has a mutable public property; value objects are readonly', $path)
        );

        self::assertDoesNotMatchRegularExpression('/function\s+set[A-Z]/', $source, $path);
        self::assertStringNotContainsString('__set', $source, $path);
    }

    // ---------------------------------------------------------------- exclusions

    #[DataProvider('sourceFiles')]
    public function testNothingReachesTheNetworkOrSpawnsAProcess(string $path, string $source): void
    {
        // P9, X5: inputs are free and local. No network, no subprocess, no LLM.
        foreach (
            [
                'curl_init', 'curl_exec', 'fsockopen', 'stream_socket_client', 'file_get_contents_url',
                'exec', 'shell_exec', 'proc_open', 'passthru', 'system', 'popen',
            ] as $function
        ) {
            self::assertDoesNotMatchRegularExpression(
                '/\b' . $function . '\s*\(/',
                $this->withoutComments($source),
                sprintf('%s calls %s()', $path, $function)
            );
        }
    }

    #[DataProvider('sourceFiles')]
    public function testNothingIntroducesNonDeterminism(string $path, string $source): void
    {
        // P8: no clock, no randomness, no locale-dependent ordering, no parallelism.
        foreach (
            [
                'rand', 'mt_rand', 'random_int', 'shuffle', 'array_rand', 'uniqid',
                'time', 'date', 'microtime', 'hrtime', 'setlocale', 'strcoll', 'usleep', 'sleep',
            ] as $function
        ) {
            self::assertDoesNotMatchRegularExpression(
                '/\b' . $function . '\s*\(/',
                $this->withoutComments($source),
                sprintf('%s calls %s()', $path, $function)
            );
        }

        self::assertStringNotContainsString('SORT_LOCALE_STRING', $source, $path);
        self::assertStringNotContainsString('pcntl_', $source, $path);
    }

    #[DataProvider('sourceFiles')]
    public function testNoGlobalFunctionsSingletonsOrServiceLocation(string $path, string $source): void
    {
        // §4 rule 6.
        self::assertDoesNotMatchRegularExpression('/^function /m', $source, $path);
        self::assertDoesNotMatchRegularExpression('/\bgetInstance\s*\(/', $source, $path);
        self::assertDoesNotMatchRegularExpression('/\bstatic\s+\$instance\b/', $source, $path);
    }

    #[DataProvider('sourceFiles')]
    public function testNoRuntimeClassDiscovery(string $path, string $source): void
    {
        // §4 rule 6, ADR-A002: what runs is what Wiring constructs.
        foreach (['ReflectionClass', 'class_exists', 'get_declared_classes', 'spl_autoload_register'] as $mechanism) {
            self::assertStringNotContainsString(
                $mechanism,
                $this->withoutComments($source),
                sprintf('%s discovers classes at run time', $path)
            );
        }
    }

    public function testTheExcludedCapabilitiesHaveNoModule(): void
    {
        // 05-traceability §5: X1–X5 are enforced structurally, not by discipline.
        $names = array_map(
            static fn (array $file): string => basename($file[0], '.php'),
            iterator_to_array(self::sourceFiles(), false)
        );

        foreach (
            [
                'ImportFollower', 'DependencyGraph', 'Crawler', 'Traversal',   // X1, X2
                'Worklist', 'Queue', 'Pending',                                // X2
                'ConfigResolver', 'MigrationResolver',                         // X3
                'Ranking',                                                     // X4
                'LlmClient', 'Prompt', 'Reviewer',                             // X5
                'EventDispatcher', 'Repository',                               // banned patterns
            ] as $excluded
        ) {
            self::assertNotContains($excluded, $names, sprintf('%s is out of scope', $excluded));
        }
    }

    public function testTheRuntimeHasZeroDependencies(): void
    {
        // ADR-A001. Composer is used for autoloading and a dev-only test runner; nothing else is
        // required at run time.
        $manifest = json_decode($this->read('composer.json'), true);

        self::assertIsArray($manifest);
        self::assertSame(['php'], array_keys($manifest['require']));
    }

    // ---------------------------------------------------------------- the closed sets

    public function testTheMoveSetIsClosed(): void
    {
        // ADR-A003: four extractors, three resolvers. Adding one needs an experiment, a
        // requirement entry, an ADR and a Wiring change — in that order.
        self::assertCount(4, $this->implementationsOf('RegionAssertionExtractor'));
        self::assertCount(3, $this->implementationsOf('AssertionResolver'));
    }

    #[DataProvider('closedEnumerations')]
    public function testTheEnumerationsAreClosed(string $path, int $expected): void
    {
        self::assertSame($expected, substr_count($this->read($path), "\n    case "));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function closedEnumerations(): iterable
    {
        yield 'five assertion kinds, one per move' => ['src/Domain/Assertion/AssertionKind.php', 5];
        yield 'seven premises' => ['src/Discovery/Lever/PremiseCatalogue.php', 7];
        yield 'two levers' => ['src/Domain/Bundle/Lever.php', 2];
        yield 'three exit codes' => ['src/Cli/ExitCode.php', 3];
    }

    public function testThereAreExactlyFivePorts(): void
    {
        self::assertCount(5, $this->filesIn('src/Ports'));
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function sourceFiles(): iterable
    {
        $root = dirname(__DIR__, 2);
        $files = [];

        $walk = static function (string $directory) use (&$walk, $root, &$files): void {
            foreach (array_diff((array) scandir($directory), ['.', '..']) as $entry) {
                $absolute = $directory . '/' . $entry;

                if (is_dir($absolute)) {
                    $walk($absolute);

                    continue;
                }

                if (str_ends_with((string) $entry, '.php')) {
                    $files[] = substr($absolute, strlen($root) + 1);
                }
            }
        };

        $walk($root . '/src');
        sort($files, SORT_STRING);

        foreach ($files as $relative) {
            yield $relative => [$relative, (string) file_get_contents($root . '/' . $relative)];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function domainFiles(): iterable
    {
        foreach (self::sourceFiles() as $name => [$path, $source]) {
            if (str_starts_with($path, 'src/Domain/')) {
                yield $name => [$path, $source];
            }
        }
    }

    /**
     * Project imports only — a top-level `use ContextDiscovery\…;`, never a trait use inside a
     * class body (which is indented) and never a vendor import.
     *
     * @return list<string> Namespace paths with the root prefix stripped.
     */
    private function projectImports(string $source): array
    {
        preg_match_all('/^use ContextDiscovery\\\\([^;]+);/m', $source, $matches);

        return array_map(
            static fn (string $import): string => trim($import),
            $matches[1]
        );
    }

    private function groupOf(string $path): string
    {
        return explode('/', substr($path, strlen('src/')))[0];
    }

    private function groupOfNamespace(string $import): string
    {
        return explode('\\', $import)[0];
    }

    /**
     * Declared names and variables, with comments and strings excluded.
     *
     * @return list<string>
     */
    private function identifiers(string $source): array
    {
        $identifiers = [];

        foreach ((array) @token_get_all($source) as $token) {
            if (!is_array($token) || !in_array($token[0], [T_STRING, T_VARIABLE], true)) {
                continue;
            }

            $identifiers[] = $token[1];
        }

        return $identifiers;
    }

    private function withoutComments(string $source): string
    {
        $code = '';

        foreach ((array) @token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    /**
     * @return list<string>
     */
    private function implementationsOf(string $interface): array
    {
        $found = [];

        foreach (self::sourceFiles() as [$path, $source]) {
            if (preg_match('/\bimplements\s+' . $interface . '\b/', $source) === 1) {
                $found[] = $path;
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    private function filesIn(string $directory): array
    {
        $found = [];

        foreach (self::sourceFiles() as [$path]) {
            if (dirname($path) === $directory) {
                $found[] = $path;
            }
        }

        return $found;
    }

    private function read(string $relativePath): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $relativePath);
    }
}
