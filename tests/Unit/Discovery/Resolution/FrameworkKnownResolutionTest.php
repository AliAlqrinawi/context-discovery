<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Resolution;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Assembly\BudgetEnforcer;
use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Assembly\ItemPriority;
use ContextDiscovery\Assembly\TokenEstimate;
use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Extraction\ChangedSignatureAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\NamedReferenceAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\OwnFileAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\UnverifiablePremiseAssertionExtractor;
use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Framework\FrameworkDeclaration;
use ContextDiscovery\Discovery\Framework\FrameworkKnowledge;
use ContextDiscovery\Discovery\Framework\LaravelFrameworkKnowledge;
use ContextDiscovery\Discovery\Lever\LeverPolicy;
use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
use ContextDiscovery\Discovery\Resolution\CallerResolver;
use ContextDiscovery\Discovery\Resolution\NamedReferenceResolver;
use ContextDiscovery\Discovery\Resolution\OwnFileResolver;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Adapters\Search\ScopedGrepCallSiteSearch;
use ContextDiscovery\Pipeline\DiscoverContext;
use ContextDiscovery\Tests\Fakes\FakeClassLocator;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\TestCase;

/**
 * What the two framework rules do once they are wired into resolution and the pipeline.
 *
 * The rules themselves are tested in `Unit\Discovery\Framework\LaravelFrameworkKnowledgeTest`. What
 * this file grades is the part the architecture already owned and that M3 reused rather than
 * replaced: the **successful-negative** outcome (`lookupRan()` → stage 5d, freeze review 05).
 *
 * The three outcomes a named reference can now have, and the one that is new:
 *
 * | Case | Slices | `lookupRan()` | Bundle | Diagnostic |
 * |---|---|---|---|---|
 * | project member found | the slice | — | **fetched item** | — |
 * | scope convention | the `scope…` slice | — | **fetched item**, project-local | — |
 * | framework-declared | none | **true** | **no item** | the citation |
 * | genuinely unresolved | none | false | **flagged item** | `unresolved …` |
 *
 * Nothing was added to make that table fit: no assertion kind, no premise, no lever, no bundle
 * field. Row three is the shape a caller search with zero results already had.
 */
final class FrameworkKnownResolutionTest extends TestCase
{
    private const FACADE = 'vendor/illuminate/Support/Facades/Log.php';

    private const MODEL = 'app/Models/Package.php';

    private const REGISTRY = 'app/Services/Registry.php';

    // ---------------------------------------------------------------- framework-declared

    public function testAFacadeMemberIsAnsweredWithACitationAndNoSlice(): void
    {
        $assertion = $this->assertion('Illuminate\Support\Facades\Log::info');

        self::assertSame([], $this->resolver()->resolve($assertion), 'the framework source is never fetched');
        self::assertTrue($this->resolver()->lookupRan($assertion), 'the question was answered, not failed');

        $citation = $this->resolver()->frameworkDeclarationFor($assertion);

        self::assertNotNull($citation);
        self::assertStringContainsString(self::FACADE . ':', $citation, 'the citation names file and line');
        self::assertStringContainsString('@method static', $citation);
    }

    public function testTheFrameworkKnownReferenceProducesNoBundleItemAndOneDiagnostic(): void
    {
        $bundle = $this->bundleFor($this->diffAdding("        Log::info('hello');"));

        self::assertSame([], $bundle->items, 'no item — and above all no framework source (Experiment 2)');
        self::assertSame(0, $bundle->usedTokens);
        self::assertCount(1, $this->diagnostics);
        self::assertStringStartsWith(
            'framework reference: Illuminate\Support\Facades\Log::info declared at',
            $this->diagnostics[0]
        );
    }

    /**
     * The two outcomes side by side in one bundle, so the distinction is not merely asserted twice
     * in isolation: a settled contract states no assumption, an unsettled one still does.
     */
    public function testASettledContractStatesNoAssumptionWhileAnUnsettledOneStillDoes(): void
    {
        $bundle = $this->bundleFor($this->diffAdding(
            "        Log::info('hello');",
            "        MissingGateway::resolve('x');",
        ));

        $payloads = array_map(static fn ($item): string => $item->payload, $bundle->items);

        self::assertCount(1, $payloads, 'only the unresolved reference earns an item');
        self::assertStringContainsString('ASSUMPTION', $payloads[0]);
        self::assertSame('App\Contracts\MissingGateway::resolve', $bundle->items[0]->provenance->member);

        foreach ($payloads as $payload) {
            self::assertStringNotContainsString(
                'Log::info',
                $payload,
                'ADR-A009: a contract the run settled leaves nothing unverified to state'
            );
        }
    }

    // ---------------------------------------------------------------- the scope convention

    public function testALocalScopeIsFetchedFromTheProjectsOwnFile(): void
    {
        $slices = $this->resolver()->resolve($this->assertion('App\Models\Package::active'));

        self::assertCount(1, $slices);
        self::assertSame(self::MODEL, $slices[0]->path, 'the slice is application code, not framework code');
        self::assertSame('scopeActive', $slices[0]->member);
        self::assertStringContainsString('function scopeActive', $slices[0]->text);
    }

    public function testALocalScopeIsAFetchedItemNotADiagnostic(): void
    {
        $bundle = $this->bundleFor($this->diffAdding('        Package::active()->get();'));
        $members = array_map(static fn ($item): ?string => $item->provenance->member, $bundle->items);

        self::assertContains('scopeActive', $members);
        self::assertSame([], $this->diagnostics, 'a resolved reference is not a negative');

        foreach ($bundle->items as $item) {
            self::assertSame('fetched', $item->lever->value);
        }
    }

    // ---------------------------------------------------------------- what must not change

    public function testAProjectMemberWinsAndTheFrameworkIsNeverConsulted(): void
    {
        // `Registry::create` is an ordinary project static factory that happens to share Eloquent's
        // most recognisable member name. A framework layer matching on the *name* would swallow it.
        $spy = new class () implements FrameworkKnowledge {
            public bool $consulted = false;

            public function declarationOf(string $member, string $classFileText): ?FrameworkDeclaration
            {
                $this->consulted = true;

                return null;
            }

            public function sameFileMemberFor(string $member, string $classFileText): ?string
            {
                $this->consulted = true;

                return null;
            }
        };

        $resolver = new NamedReferenceResolver(
            $this->locator(),
            $this->source(),
            new TokenizerMemberSlicer(),
            $spy
        );

        $slices = $resolver->resolve($this->assertion('App\Services\Registry::create'));

        self::assertCount(1, $slices);
        self::assertSame('create', $slices[0]->member);
        self::assertFalse($spy->consulted, 'the project answered, so the framework was never asked');
    }

    public function testAMisspelledMemberOnAModelStillFlags(): void
    {
        $assertion = $this->assertion('App\Models\Package::activatte');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertFalse(
            $this->resolver()->lookupRan($assertion),
            'a typo on a class that really does extend Model is exactly what must not be swallowed'
        );
    }

    public function testAnUnplaceableClassStillFlags(): void
    {
        $assertion = $this->assertion('App\Contracts\MissingGateway::resolve');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertFalse($this->resolver()->lookupRan($assertion));
    }

    public function testABareClassNameAsksForASurfaceNotAMemberContract(): void
    {
        $assertion = $this->assertion('Illuminate\Support\Facades\Log');

        self::assertNull(
            $this->resolver()->frameworkDeclarationFor($assertion),
            'no member was named, so there is no framework declaration to cite'
        );
    }

    public function testNothingOutsideANamedReferenceIsEverFrameworkKnown(): void
    {
        foreach (AssertionKind::cases() as $kind) {
            if ($kind === AssertionKind::NamedReference) {
                continue;
            }

            self::assertNull(
                $this->resolver()->frameworkDeclarationFor(
                    new Assertion($kind, 'Log', 'app/X.php', $this->region(), 'claim')
                ),
                $kind->value . ': framework knowledge is consulted for named references only, so '
                    . 'Experiment 1\'s missing-import absence can never be suppressed'
            );
        }
    }

    public function testTwoRunsAreByteIdentical(): void
    {
        $diff = $this->diffAdding("        Log::info('hello');", '        Package::active()->get();');

        $first = $this->bundleFor($diff);
        $firstDiagnostics = $this->diagnostics;

        $second = $this->bundleFor($diff);

        self::assertEquals($first, $second, 'P8');
        self::assertSame($firstDiagnostics, $this->diagnostics, 'P8, including the diagnostics');
    }

    // ---------------------------------------------------------------- fixtures

    /** @var list<string> */
    private array $diagnostics = [];

    private function resolver(): NamedReferenceResolver
    {
        return new NamedReferenceResolver(
            $this->locator(),
            $this->source(),
            new TokenizerMemberSlicer(),
            new LaravelFrameworkKnowledge()
        );
    }

    private function locator(): FakeClassLocator
    {
        return new FakeClassLocator([
            'Illuminate\Support\Facades\Log' => self::FACADE,
            'App\Models\Package' => self::MODEL,
            'App\Services\Registry' => self::REGISTRY,
        ]);
    }

    private function source(): FakeSourceRepository
    {
        return new FakeSourceRepository([
            self::FACADE => "<?php\n\nnamespace Illuminate\Support\Facades;\n\n"
                . "/**\n * @method static void info(string \$message, array \$context = [])\n */\n"
                . "class Log extends Facade\n{\n"
                . "    protected static function getFacadeAccessor()\n    {\n        return 'log';\n    }\n}\n",

            self::MODEL => "<?php\n\nnamespace App\Models;\n\n"
                . "use Illuminate\Database\Eloquent\Model;\n\n"
                . "class Package extends Model\n{\n"
                . "    public function scopeActive(\$query)\n    {\n"
                . "        return \$query->where('is_active', true);\n    }\n}\n",

            self::REGISTRY => "<?php\n\nnamespace App\Services;\n\n"
                . "final class Registry\n{\n"
                . "    public static function create(array \$entries): self\n    {\n"
                . "        return new self();\n    }\n}\n",

            'app/Repositories/PackageRepository.php' => $this->repositoryText(),
        ]);
    }

    private function repositoryText(): string
    {
        return "<?php\n\nnamespace App\Repositories;\n\n"
            . "use App\Contracts\MissingGateway;\nuse App\Models\Package;\nuse Illuminate\Support\Facades\Log;\n\n"
            . "class PackageRepository\n{\n"
            . "    public function handle(): void\n    {\n    }\n}\n";
    }

    /**
     * A hunk that adds lines inside `handle()`, which begins on line 10 of the repository text.
     */
    private function diffAdding(string ...$added): string
    {
        $lines = [
            'diff --git a/app/Repositories/PackageRepository.php b/app/Repositories/PackageRepository.php',
            '--- a/app/Repositories/PackageRepository.php',
            '+++ b/app/Repositories/PackageRepository.php',
            sprintf('@@ -10,3 +10,%d @@', 3 + count($added)),
            '     public function handle(): void',
            '     {',
        ];

        foreach ($added as $line) {
            $lines[] = '+' . $line;
        }

        $lines[] = '     }';

        return implode("\n", $lines) . "\n";
    }

    private function region(): ChangedRegion
    {
        return new ChangedRegion(1, 2, ['x'], []);
    }

    private function assertion(string $subject): Assertion
    {
        return new Assertion(
            AssertionKind::NamedReference,
            $subject,
            'app/Repositories/PackageRepository.php',
            $this->region(),
            'the region depends on ' . $subject . ', whose contract is defined in another file',
        );
    }

    private function bundleFor(string $diffText): Bundle
    {
        $this->diagnostics = [];

        $source = $this->source();
        $slicer = new TokenizerMemberSlicer();
        $locator = $this->locator();

        $context = new DiscoverContext(
            new UnifiedDiffParser(),
            $source,
            new AssertionExtractor([
                new OwnFileAssertionExtractor($slicer),
                new NamedReferenceAssertionExtractor($slicer),
                new ChangedSignatureAssertionExtractor(),
                new UnverifiablePremiseAssertionExtractor($slicer),
            ]),
            new LeverPolicy(),
            $locator,
            new OwnFileResolver($source, $slicer),
            new NamedReferenceResolver($locator, $source, $slicer, new LaravelFrameworkKnowledge()),
            new CallerResolver(new ScopedGrepCallSiteSearch($source), $source, 'app/', 20),
            new AssumptionWriter(),
            new BundleAssembler(new TokenEstimate()),
            new BudgetEnforcer(new ItemPriority()),
        );

        return $context->run($diffText, 20000, function (string $line): void {
            $this->diagnostics[] = $line;
        });
    }
}
