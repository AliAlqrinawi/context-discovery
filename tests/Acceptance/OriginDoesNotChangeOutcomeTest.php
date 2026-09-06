<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * M15 · the file a reference originates in does not change what the bundle fetches.
 *
 * M14 recorded an observation: `User::create(...)` in a test file caused a project-class surface
 * that M7's answer key did not ask for, and wrote *"test files are scaffolding"* down as an
 * **unproven** discriminator. M15 keyed it and measured it, and the discriminator did not survive.
 *
 * The corpus's own decision rule (`fetch-vs-flag.md`) is *"is the resolving source named, single,
 * and depth-one on disk? → yes → FETCH the minimal slice"*. It has no term for where the
 * referencing line lives, and `context-types.md` type 2 says *"the diff explicitly references"* —
 * the diff, not a privileged part of it. The one row in the whole corpus that reasons about test
 * files, M7's E5.12, names four references that are all **dependency** classes: they are omitted by
 * ownership (ADR-A012/A013) in any file whatever, so the row supplies a rationale and not one
 * discriminating instance.
 *
 * This test is a **lock**, in the sense M2's table lock was. It does not assert that origin-blind
 * behaviour is desirable forever; it asserts that it is what the tool does today, so that adding
 * an origin rule cannot happen quietly — it has to come with an experiment that turns these
 * assertions red on purpose.
 *
 * The fixture's categories are the real corpus's, read from its own `composer.json`: `app/` and
 * `database/seeders/` are declared by `autoload.psr-4`, `tests/` by `autoload-dev.psr-4`, and
 * `routes/` by neither.
 */
final class OriginDoesNotChangeOutcomeTest extends TestCase
{
    private const BUDGET = 8000;

    /**
     * @var array<string, mixed>|null
     */
    private static ?array $bundle = null;

    // ---------------------------------------------------------------- the deciding pair

    public function testAnUnresolvedMemberIsTreatedTheSameFromProductionAndFromATest(): void
    {
        // P2 · `Order::create` from a controller, and T3 · `AuditLog::create` from a test. Same
        // shape, same ownership, same member state, different origin — same outcome.
        self::assertSame(['casts', 'fillable', 'scopeActive'], $this->fetchedMembersIn('app/Models/Order.php'));
        self::assertSame(['casts', 'fillable'], $this->fetchedMembersIn('app/Models/AuditLog.php'));

        self::assertContains('App\Models\Order::create', $this->flaggedSubjects());
        self::assertContains('App\Models\AuditLog::create', $this->flaggedSubjects());
    }

    public function testAResolvingMemberReachedOnlyFromATestIsStillFetched(): void
    {
        // T1b · `OrderCalculator::subtotal` is called from the test and nowhere else. Named,
        // single, depth-one — the FETCH side of the corpus's rule, with no origin term in it.
        self::assertContains('subtotal', $this->fetchedMembersIn('app/Services/OrderCalculator.php'));
    }

    // ---------------------------------------------------------------- the guards

    public function testASeederIsProductionBecauseComposerSaysSo(): void
    {
        // S1 · a seeder LOOKS like scaffolding, and the project's own `autoload` section declares
        // it production. Any rule built on the word "test", or on a hand-written path list, is at
        // risk of catching this; M7 emitted exactly this flag from SettingSeeder.
        self::assertContains('App\Models\Order::firstOrCreate', $this->flaggedSubjects());
        self::assertSame(
            'database/seeders/OrderSeeder.php',
            $this->originOf('App\Models\Order::firstOrCreate'),
        );
    }

    public function testAFileNoPsr4SectionMapsIsStillHeard(): void
    {
        // R1 · `routes/web.php` is autoloaded by neither section. A binary production/dev rule has
        // no answer for it, which is itself a reason the distinction is not as clean as it looks.
        self::assertContains('App\Models\Order::whereActive', $this->flaggedSubjects());
    }

    public function testTheSameSubjectFromTwoOriginsFlagsTwiceAndSurfacesOnce(): void
    {
        // T2 · two flags, because each names a different line the reviewer must look at — the
        // shape M7 already produced for `Setting::where` across three files. One surface, because
        // two copies of the same members would double-count tokens (ADR-A005).
        $flags = array_filter($this->flaggedSubjects(), static fn (string $s): bool => $s === 'App\Models\Order::create');

        self::assertCount(2, $flags, 'one flag per origin');
        self::assertCount(3, $this->fetchedFrom('app/Models/Order.php'), 'one surface');
    }

    public function testAClassTheDiffCreatesIsWithheldFromBothOrigins(): void
    {
        // V1 · `app/Models/Coupon.php` is created by this diff, so ADR-A019 withholds its members
        // whichever file names them. An origin rule must not become a second, redundant reason
        // for a suppression that is already correct.
        self::assertSame([], $this->fetchedFrom('app/Models/Coupon.php'));

        $flags = array_filter($this->flaggedSubjects(), static fn (string $s): bool => $s === 'App\Models\Coupon::create');

        self::assertCount(2, $flags, 'still heard from both origins');
    }

    public function testLexicalNoiseProducesNothingInEitherCategory(): void
    {
        // N1 · the same docblock, comment, string-literal and `::class` noise appears in a
        // production file and in a test file, so a new origin rule cannot appear to "fix" noise it
        // never caused. Nothing outside these five files may be touched at all.
        $paths = array_values(array_unique(array_map(
            static fn (array $item): string => $item['provenance']['path'],
            self::bundle()['items']
        )));

        sort($paths);

        self::assertSame(
            [
                'app/Http/Controllers/OrderController.php',
                'app/Models/AuditLog.php',
                'app/Models/Order.php',
                'app/Services/OrderCalculator.php',
                'database/seeders/OrderSeeder.php',
                'routes/web.php',
                'tests/Feature/OrderTest.php',
                'tests/Support/CreatesOrders.php',
            ],
            $paths
        );
    }

    // ---------------------------------------------------------------- the recorded defect

    public function testTheDuplicateMemberSliceIsOriginIndependent(): void
    {
        // G4, found while measuring M15 and deliberately NOT fixed here. `OrderCalculator::total`
        // is named from a production file and from a test, and arrives twice. The scratch check in
        // `docs/research/M15-origin-value.md` §9 shows two PRODUCTION files do exactly the same, so
        // this is a duplicate-subject defect and not an origin one. Asserted as it stands so that
        // fixing it is a deliberate act with its own key.
        $total = array_filter(
            $this->fetchedFrom('app/Services/OrderCalculator.php'),
            static fn (array $item): bool => $item['provenance']['member'] === 'total'
        );

        self::assertCount(2, $total, 'recorded, not fixed: one fact, two identical slices');
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @return list<string>
     */
    private function flaggedSubjects(): array
    {
        $subjects = [];

        foreach (self::bundle()['items'] as $item) {
            if ($item['lever'] === 'flagged' && preg_match('/depends on (\S+),/', $item['reason'], $m) === 1) {
                $subjects[] = $m[1];
            }
        }

        return $subjects;
    }

    private function originOf(string $subject): ?string
    {
        foreach (self::bundle()['items'] as $item) {
            if ($item['lever'] === 'flagged' && str_contains($item['reason'], 'depends on ' . $subject . ',')) {
                return $item['provenance']['path'];
            }
        }

        return null;
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
        if (self::$bundle !== null) {
            return self::$bundle;
        }

        $root = dirname(__DIR__, 2);
        $fixture = __DIR__ . '/fixtures/experiment-15';

        self::assertFileExists($fixture . '/diff.patch', 'the experiment-15 fixture is missing');

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

        return self::$bundle = $bundle;
    }
}
