<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Resolution;

use ContextDiscovery\Adapters\Autoload\ComposerPsr4ClassLocator;
use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Discovery\Framework\LaravelFrameworkKnowledge;
use ContextDiscovery\Discovery\Resolution\NamedReferenceResolver;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * M14 · ADR-A020 — when a project member cannot be resolved, the class's surface is.
 *
 * M7 measured that Experiment 1's model surface — the best-evidenced move in the corpus — never
 * fired on a real Laravel pull request, because real code writes `Setting::updateOrCreate(...)`
 * and never `new Setting`. M13 bounded the fix to three conditions and measured a false positive
 * for dropping each one. These tests hold that boundary from the outside: every excluded case is
 * excluded by the condition it fails, and never by the name it happens to have.
 *
 * The **member** flag is not this method's business. `unresolvedMemberSurface()` answers only
 * "what can this region be read against?"; the pipeline keeps the flag beside the answer, and
 * `ProjectSurfaceFallbackPipelineTest` is where that is proved.
 */
final class ProjectSurfaceFallbackTest extends TestCase
{
    // ---------------------------------------------------------------- the move G1 was missing

    public function testAnUnresolvedMemberOnAProjectClassOffersTheClassSurface(): void
    {
        $slices = $this->resolver()->unresolvedMemberSurface($this->assertion('App\Models\Package::where'));

        self::assertSame(
            ['fillable', 'casts', 'scopeActive'],
            array_map(static fn ($slice): string => (string) $slice->member, $slices),
            'the surface Experiment 1 asks for, reached through the form real Laravel code uses'
        );

        foreach ($slices as $slice) {
            self::assertSame('app/Models/Package.php', $slice->path);
        }
    }

    public function testTheAnswerDoesNotDependOnWhichMemberWasNamed(): void
    {
        // `where`, `create` and `query` reach Eloquent by three different mechanisms — the builder,
        // the builder again, and plain inheritance from Model (M1 rows S03.1, S02.1, S05.1). None
        // of that is known here, and it does not need to be: the class is the project's and the
        // member is unaccounted for, which is the whole rule.
        $reference = $this->resolver()->unresolvedMemberSurface($this->assertion('App\Models\Package::where'));

        foreach (['create', 'query', 'updateOrCreate', 'firstOrFail'] as $member) {
            self::assertEquals(
                $reference,
                $this->resolver()->unresolvedMemberSurface($this->assertion('App\Models\Package::' . $member)),
                $member
            );
        }
    }

    // ---------------------------------------------------------------- the three conditions

    public function testAMemberThatResolvesIsNeverOverridden(): void
    {
        // THE TRAP M13 measured. `Registry::create` is an ordinary project class that happens to
        // declare a method Eloquent also has. Its `create()` is right there, so the minimal slice
        // *is* the member — "ideally one method or class member, not a whole file".
        $assertion = $this->assertion('App\Services\Registry::create');

        self::assertSame([], $this->resolver()->unresolvedMemberSurface($assertion), 'no surface');

        $resolved = $this->resolver()->resolve($assertion);

        self::assertCount(1, $resolved, 'and the declared member alone, exactly as before M14');
        self::assertSame('create', $resolved[0]->member);
    }

    public function testTheExclusionIsTheDeclarationAndNotTheName(): void
    {
        // The same class, the same method name, differing only in whether it is declared. If the
        // rule had been written as "`create` is Eloquent's", both answers would be the same.
        self::assertSame([], $this->resolver()->unresolvedMemberSurface($this->assertion('App\Services\Registry::create')));
        self::assertNotSame([], $this->resolver()->unresolvedMemberSurface($this->assertion('App\Models\Package::create')));
    }

    public function testAMemberReachedByAFrameworkNamingConventionIsNotOverridden(): void
    {
        // `Package::active()` is Laravel's `scopeActive` (M3, ADR-A011). It resolves, so the
        // minimal slice is that one scope and not the whole model.
        $assertion = $this->assertion('App\Models\Package::active');

        self::assertSame([], $this->resolver()->unresolvedMemberSurface($assertion));
        self::assertCount(1, $this->resolver()->resolve($assertion));
    }

    public function testAFrameworkDeclaredMemberIsNotConvertedIntoASurface(): void
    {
        // A project class that *is* a facade. The framework declares the member by tag, so the
        // reference is settled (ADR-A011) and there is nothing unaccounted for to fall back from.
        $assertion = $this->assertion('App\Support\Ledger::post');

        self::assertNotNull($this->resolver()->frameworkDeclarationFor($assertion), 'settled by the tag');
        self::assertSame([], $this->resolver()->unresolvedMemberSurface($assertion), 'so no surface');
    }

    public function testAnUnplaceableClassIsNotConvertedIntoASurface(): void
    {
        foreach (['App\Contracts\MissingGateway::resolve', 'Nowhere\At\All::thing'] as $subject) {
            self::assertSame([], $this->resolver()->unresolvedMemberSurface($this->assertion($subject)), $subject);
        }
    }

    public function testADependencyClassIsNotConvertedIntoASurface(): void
    {
        // ADR-A012's whole point. A facade member is settled from its tag and a dependency member
        // from its declaration; neither may become a slice of somebody else's source.
        foreach (['Illuminate\Support\Facades\Log::info', 'Tiny\Package\Thing::only', 'Tiny\Package\Thing::nope'] as $subject) {
            self::assertSame([], $this->resolver()->unresolvedMemberSurface($this->assertion($subject)), $subject);
        }
    }

    public function testNoDependencySourceIsReadOnTheFallbackPath(): void
    {
        $reads = [];
        $inner = new FakeSourceRepository($this->files());

        $source = new class ($inner, $reads) implements \ContextDiscovery\Ports\SourceRepository {
            /** @param list<string> $reads */
            public function __construct(private FakeSourceRepository $inner, public array &$reads)
            {
            }

            public function exists(string $relativePath): bool
            {
                return $this->inner->exists($relativePath);
            }

            public function text(string $relativePath): ?string
            {
                $this->reads[] = $relativePath;

                return $this->inner->text($relativePath);
            }

            /** @return list<string> */
            public function filesUnder(string $prefix, string $extension): array
            {
                return $this->inner->filesUnder($prefix, $extension);
            }
        };

        $resolver = new NamedReferenceResolver(
            new ComposerPsr4ClassLocator($source),
            $source,
            new TokenizerMemberSlicer(),
            new LaravelFrameworkKnowledge()
        );

        $resolver->unresolvedMemberSurface($this->assertion('Illuminate\Support\Facades\Log::info'));
        $resolver->unresolvedMemberSurface($this->assertion('Tiny\Package\Thing::nope'));

        foreach ($reads as $path) {
            if (str_starts_with($path, 'vendor/composer/')) {
                continue; // Composer's generated map is metadata, not source (ADR-A014).
            }

            self::assertStringStartsNotWith('vendor/', $path, 'the fallback never opens a dependency');
        }
    }

    // ---------------------------------------------------------------- the bound stays where it was

    public function testOnlyTheLocatedFileIsEverOpened(): void
    {
        // ADR-A010. `Package extends Model`, and the parent is on disk and placeable here. If the
        // fallback had grown a traversal, this is where it would show.
        $reads = [];
        $inner = new FakeSourceRepository($this->files());

        $source = new class ($inner, $reads) implements \ContextDiscovery\Ports\SourceRepository {
            /** @param list<string> $reads */
            public function __construct(private FakeSourceRepository $inner, public array &$reads)
            {
            }

            public function exists(string $relativePath): bool
            {
                return $this->inner->exists($relativePath);
            }

            public function text(string $relativePath): ?string
            {
                $this->reads[] = $relativePath;

                return $this->inner->text($relativePath);
            }

            /** @return list<string> */
            public function filesUnder(string $prefix, string $extension): array
            {
                return $this->inner->filesUnder($prefix, $extension);
            }
        };

        $resolver = new NamedReferenceResolver(
            new ComposerPsr4ClassLocator($source),
            $source,
            new TokenizerMemberSlicer(),
            new LaravelFrameworkKnowledge()
        );

        $resolver->unresolvedMemberSurface($this->assertion('App\Models\Package::where'));

        $sources = array_values(array_filter(
            array_unique($reads),
            static fn (string $path): bool => !str_starts_with($path, 'vendor/composer/') && $path !== 'composer.json'
        ));

        self::assertSame(['app/Models/Package.php'], $sources, 'one file, no ancestry');
    }

    // ---------------------------------------------------------------- negative controls

    public function testABareClassIsLeftToTheSurfacePathItAlreadyHas(): void
    {
        self::assertSame(
            [],
            $this->resolver()->unresolvedMemberSurface($this->assertion('App\Models\Package')),
            'this is the member fallback; a bare class never reaches it'
        );
        self::assertNotSame([], $this->resolver()->resolve($this->assertion('App\Models\Package')));
    }

    #[DataProvider('otherKinds')]
    public function testNothingOutsideANamedReferenceEverGetsASurface(AssertionKind $kind): void
    {
        $assertion = new Assertion($kind, 'App\Models\Package::where', 'app/X.php', $this->region(), 'claim');

        self::assertSame([], $this->resolver()->unresolvedMemberSurface($assertion), $kind->value);
    }

    /**
     * @return iterable<string, array{AssertionKind}>
     */
    public static function otherKinds(): iterable
    {
        foreach (AssertionKind::cases() as $kind) {
            if ($kind !== AssertionKind::NamedReference) {
                yield $kind->value => [$kind];
            }
        }
    }

    public function testAskingTwiceGivesTheSameAnswer(): void
    {
        $assertion = $this->assertion('App\Models\Package::where');

        self::assertEquals(
            $this->resolver()->unresolvedMemberSurface($assertion),
            $this->resolver()->unresolvedMemberSurface($assertion),
            'P8'
        );
    }

    // ---------------------------------------------------------------- fixtures

    /**
     * @return array<string, string>
     */
    private function files(): array
    {
        return [
            'composer.json' => $this->manifest(),
            'app/Models/Package.php' => "<?php\n\nnamespace App\Models;\n\n"
                . "use Illuminate\Database\Eloquent\Model;\n\nclass Package extends Model\n{\n"
                . "    protected \$fillable = ['slug'];\n\n"
                . "    protected \$casts = ['is_active' => 'bool'];\n\n"
                . "    public function scopeActive(\$query): void\n    {\n    }\n}\n",
            'app/Services/Registry.php' => "<?php\n\nnamespace App\Services;\n\nfinal class Registry\n{\n"
                . "    public function __construct(private array \$names)\n    {\n    }\n\n"
                . "    public static function create(array \$names): self\n    {\n        return new self(\$names);\n    }\n}\n",
            'app/Support/Ledger.php' => "<?php\n\nnamespace App\Support;\n\n"
                . "use Illuminate\Support\Facades\Facade;\n\n/**\n * @method static void post(string \$entry)\n */\n"
                . "class Ledger extends Facade\n{\n    protected static function getFacadeAccessor(): string\n"
                . "    {\n        return 'ledger';\n    }\n}\n",
            'vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php' =>
                "<?php\n\nnamespace Illuminate\Database\Eloquent;\n\nabstract class Model\n{\n"
                . "    public static function query(): void\n    {\n    }\n\n"
                . "    public static function create(array \$attributes): void\n    {\n    }\n}\n",
            'vendor/laravel/framework/src/Illuminate/Support/Facades/Log.php' =>
                "<?php\n\nnamespace Illuminate\Support\Facades;\n\n/**\n * @method static void info(string \$message)\n */\n"
                . "class Log extends Facade\n{\n}\n",
            'vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php' =>
                "<?php\n\nnamespace Illuminate\Support\Facades;\n\nabstract class Facade\n{\n}\n",
            'vendor/tiny/package/Thing.php' => "<?php\n\nnamespace Tiny\Package;\n\nclass Thing\n{\n"
                . "    public function only(): void\n    {\n    }\n}\n",
        ];
    }

    private function resolver(): NamedReferenceResolver
    {
        $source = new FakeSourceRepository($this->files());

        return new NamedReferenceResolver(
            new ComposerPsr4ClassLocator($source),
            $source,
            new TokenizerMemberSlicer(),
            new LaravelFrameworkKnowledge()
        );
    }

    private function manifest(): string
    {
        return (string) json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                    'Illuminate\\' => 'vendor/laravel/framework/src/Illuminate/',
                    'Tiny\\Package\\' => 'vendor/tiny/package/',
                ],
            ],
        ]);
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
            'app/Consumer.php',
            $this->region(),
            'the region depends on ' . $subject . ', whose contract is defined in another file',
        );
    }
}
