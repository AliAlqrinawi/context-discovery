<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * M14 · experiment-14 — the whole command, against a key written before it ran.
 *
 * `ProjectSurfaceFallbackTest` holds the resolver's boundary. This holds the two things only the
 * assembled bundle can show: that the unresolved-member **flag survives** beside the surface, and
 * that a class reached two ways still yields **one** surface.
 *
 * Row **H** is why the flag survives. `Package::activatte` is a typo, and it satisfies the same
 * three conditions as `Package::create`; ADR-A016 established that no fact the tool has separates
 * them. Replacing the flag would report the typo as satisfied context — M0's risk R1, and the
 * silence P10 forbids. The rule cannot tell them apart, so it does not decide between them: it
 * flags both and offers the surface for both.
 */
final class ProjectSurfaceFallbackAcceptanceTest extends TestCase
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

    // ---------------------------------------------------------------- rows A–D · the move itself

    public function testAnEloquentMemberReferenceFetchesTheModelSurface(): void
    {
        // A, B, C. Real Laravel reaches a model this way and never as `new Package`, which is why
        // M7 measured Experiment 1's move never firing (gap G1).
        self::assertSame(
            ['casts', 'fillable', 'scopeActive'],
            $this->fetchedMembersIn('app/Models/Package.php'),
            'the model surface, reached through a static call'
        );
    }

    public function testASecondModelIsServedIndependently(): void
    {
        // D. Not one lucky class.
        self::assertSame(['fillable'], $this->fetchedMembersIn('app/Models/Setting.php'));
    }

    public function testThreeUnresolvedMembersOnOneModelProduceOneSurface(): void
    {
        // A, B, C name three members of App\Models\Package. The class has three members, and it is
        // sliced once, not three times.
        self::assertCount(3, $this->fetchedFrom('app/Models/Package.php'), 'one surface, not three');
    }

    // ---------------------------------------------------------------- row H · the deciding row

    public function testATypoKeepsItsFlag(): void
    {
        self::assertContains(
            'App\Models\Package::activatte',
            $this->flaggedSubjects(),
            'THE CRITICAL GUARD: a misspelled member on a model must not be reported as provided'
        );
        self::assertContains(
            'unresolved named_reference: App\Models\Package::activatte in app/Consumer.php',
            self::stderr(),
            'and it is still said out loud'
        );
    }

    public function testEveryUnresolvedMemberKeepsItsFlagBesideTheSurface(): void
    {
        // The flag is *retained*, not replaced — for the members that really exist as much as for
        // the typo, because nothing available distinguishes them.
        foreach ([
            'App\Models\Package::where',
            'App\Models\Package::create',
            'App\Models\Package::query',
            'App\Models\Setting::updateOrCreate',
            'App\Models\Invoice::create',
        ] as $subject) {
            self::assertContains($subject, $this->flaggedSubjects(), $subject);
        }
    }

    // ---------------------------------------------------------------- rows E–G · the exclusions

    public function testAResolvedMemberStillFetchesOnlyThatMember(): void
    {
        // E. `Registry::create` — an ordinary project class carrying Eloquent's most recognisable
        // method name. Excluded because the member resolves, never because of the name.
        self::assertSame(['create'], $this->fetchedMembersIn('app/Services/Registry.php'));
        self::assertNotContains('App\Services\Registry::create', $this->flaggedSubjects(), 'and no flag');
    }

    public function testAnUnplaceableClassProducesOneFlagAndNoSurface(): void
    {
        // F. A second outcome here would be a new false positive, not new context.
        self::assertSame(
            ['App\Contracts\MissingGateway::resolve'],
            array_values(array_filter(
                $this->flaggedSubjects(),
                static fn (string $subject): bool => str_contains($subject, 'MissingGateway')
            )),
            'exactly one flag'
        );
        self::assertSame([], $this->fetchedFrom('app/Contracts/MissingGateway.php'));
    }

    public function testAFrameworkMemberIsStillSettledWithNoItemAtAll(): void
    {
        // G. ADR-A011's successful negative, and ADR-A012's floor beneath it.
        self::assertNotContains('Illuminate\Support\Facades\Log::info', $this->flaggedSubjects());

        foreach (self::bundle()['items'] as $item) {
            self::assertStringStartsNotWith(
                'vendor/',
                $item['provenance']['path'],
                'no framework source may enter the bundle'
            );
        }
    }

    // ---------------------------------------------------------------- row I · the duplicate guard

    public function testAClassReachedBothAsABareTypeAndThroughAnUnresolvedMemberIsSlicedOnce(): void
    {
        // `?Invoice` is a bare-class reference and `Invoice::create` an unresolved member. Both ask
        // for the same surface; the bundle contains it once.
        self::assertSame(['fillable'], $this->fetchedMembersIn('app/Models/Invoice.php'));
    }

    public function testTheAlreadySupportedBareFormIsUnchanged(): void
    {
        // I. `new Widget()` fetched a surface before M14 and fetches the same one now.
        self::assertSame(['fillable', 'label'], $this->fetchedMembersIn('app/Models/Widget.php'));
    }

    // ---------------------------------------------------------------- row J · the noise floor

    public function testProseNeverBecomesAReference(): void
    {
        // Docblocks, comments, string literals and `::class` name several of these classes. None of
        // them may produce an item, and none of them introduces a class the code does not use.
        $paths = array_unique(array_map(
            static fn (array $item): string => $item['provenance']['path'],
            self::bundle()['items']
        ));

        sort($paths);

        self::assertSame(
            [
                'app/Consumer.php',
                'app/Models/Invoice.php',
                'app/Models/Package.php',
                'app/Models/Setting.php',
                'app/Models/Widget.php',
                'app/Services/Registry.php',
            ],
            $paths
        );
    }

    // ---------------------------------------------------------------- row X · the frozen contract

    public function testTheContractIsUnchanged(): void
    {
        $bundle = self::bundle();

        self::assertSame(1, $bundle['bundle_version']);
        self::assertSame(
            ['bundle_version', 'budget_tokens', 'used_tokens', 'items', 'dropped'],
            array_keys($bundle),
            'no field was added for this'
        );

        foreach ($bundle['items'] as $item) {
            self::assertSame(['lever', 'reason', 'assertion_kind', 'provenance', 'payload', 'tokens'], array_keys($item));
            self::assertContains($item['lever'], ['fetched', 'flagged']);
            self::assertNotSame('', $item['reason'], 'P5: no item without a reason');
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
     * @return list<string>
     */
    private function flaggedSubjects(): array
    {
        $subjects = [];

        foreach (self::bundle()['items'] as $item) {
            if ($item['lever'] !== 'flagged') {
                continue;
            }

            if (preg_match('/depends on (\S+),/', $item['reason'], $match) === 1) {
                $subjects[] = $match[1];
            }
        }

        return $subjects;
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
        return array_map(
            static fn (array $item): string => (string) $item['provenance']['member'],
            $this->fetchedFrom($path)
        );
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
        $fixture = __DIR__ . '/fixtures/experiment-14';

        self::assertFileExists($fixture . '/diff.patch', 'the experiment-14 fixture is missing');

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
