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
 * M4 · the bare-class *surface* move belongs to the project's own classes.
 *
 * A reference that names a class and no member asks for the class **surface** — every member,
 * sliced. Experiment 1 earned that move for a model and Experiment 4 for an enum, both of them the
 * project's own small classes, and Experiment 1's minimum context is recorded as *"one model and
 * three method bodies. No repository dump, nothing close to it."*
 *
 * Applied to an installed dependency the same move degenerates into the whole-file payload the
 * implementation spec forbids — *"the minimal source slice (ideally one method or class member,
 * **not a whole file**)"*. The M1 fixture measured it: one framework return type, 115 slices,
 * 13,426 tokens.
 *
 * So the discriminator is **ownership, not size** (ADR-A012). These tests exist mostly to prove
 * that, because "it was big" is the plausible-but-wrong rule someone would otherwise write: a large
 * project class is still fetched in full, and a small dependency class still is not.
 */
final class DependencyClassSurfaceTest extends TestCase
{
    // ---------------------------------------------------------------- the S03.4 case

    public function testADependencyClassSurfaceIsNotFetched(): void
    {
        $assertion = $this->assertion('Illuminate\Support\Collection');

        self::assertSame([], $this->resolver()->resolve($assertion), 'no slice from a dependency');
        self::assertTrue($this->resolver()->lookupRan($assertion), 'the question was answered, not failed');
        self::assertSame(
            'vendor/laravel/framework/src/Illuminate/Collections/Collection.php',
            $this->resolver()->dependencyPathFor($assertion),
            'and the answer is citable without opening the file'
        );
    }

    public function testTheDependencysTextIsNeverRead(): void
    {
        // The locator *stats* the file to place the class — that is how PSR-4 resolution works and
        // it is unchanged. What must never happen is a read: text is what could reach the bundle.
        // This spy fails the test if anything asks for a vendor file's contents.
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

        $assertion = $this->assertion('Illuminate\Support\Collection');

        self::assertSame([], $resolver->resolve($assertion));
        self::assertNotNull($resolver->dependencyPathFor($assertion), 'answered from the path alone');

        foreach ($reads as $path) {
            // Composer's own generated map is *metadata* and the locator reads it to place classes
            // at all (ADR-A014). What must never be read is a dependency's **source**, because that
            // is the only thing that could become payload.
            if (str_starts_with($path, 'vendor/composer/')) {
                continue;
            }

            self::assertStringStartsNotWith('vendor/', $path, 'no dependency source was read');
        }
    }

    // ---------------------------------------------------------------- ownership, not size

    public function testALargeProjectClassIsStillFetchedInFull(): void
    {
        $slices = $this->resolver()->resolve($this->assertion('App\Models\Wide'));

        self::assertCount(40, $slices, 'size is not the discriminator — this is the project\'s own code');

        foreach ($slices as $slice) {
            self::assertStringStartsWith('app/', $slice->path);
        }
    }

    public function testASmallDependencyClassIsStillNotFetched(): void
    {
        self::assertSame(
            [],
            $this->resolver()->resolve($this->assertion('Tiny\Package\Thing')),
            'a one-member dependency class is withheld for the same reason a large one is'
        );
    }

    // ---------------------------------------------------------------- negative controls

    public function testAProjectClassWhoseFileIsAbsentStillFlags(): void
    {
        $assertion = $this->assertion('App\Contracts\MissingGateway');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertFalse(
            $this->resolver()->lookupRan($assertion),
            'genuinely unresolved: the prefix is mapped, the file is not there — this must keep flagging'
        );
        self::assertNull($this->resolver()->dependencyPathFor($assertion));
    }

    public function testAnUnmappableClassStillFlags(): void
    {
        $assertion = $this->assertion('Nowhere\At\All');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertFalse($this->resolver()->lookupRan($assertion), 'no map entry is a failure, not an answer');
    }

    public function testASmallProjectClassSurfaceIsUnchanged(): void
    {
        $slices = $this->resolver()->resolve($this->assertion('App\Models\Package'));

        self::assertCount(2, $slices, 'Experiment 1\'s model surface still arrives');
        self::assertSame('app/Models/Package.php', $slices[0]->path);
    }

    /**
     * A path is a dependency's only when it is *inside* the vendor directory. Names that merely
     * contain or resemble it are the project's.
     */
    #[DataProvider('pathsThatAreProjectSource')]
    public function testAPathIsOnlyADependencyWhenItIsInsideTheVendorDirectory(string $label, string $path): void
    {
        $locator = new ComposerPsr4ClassLocator(new FakeSourceRepository(['composer.json' => $this->manifest()]));

        self::assertTrue($locator->isProjectSource($path), $label);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pathsThatAreProjectSource(): iterable
    {
        yield 'ordinary project path' => ['app/', 'app/Models/Package.php'];
        yield 'a directory that starts with the same letters' => ['vendors/', 'vendors/Thing.php'];
        yield 'the word appears deeper in the path' => ['app/vendor/', 'app/vendor/Thing.php'];
        yield 'a file named vendor' => ['vendor.php', 'vendor.php'];
    }

    public function testTheVendorDirectoryIsReadFromTheManifestRatherThanAssumed(): void
    {
        $locator = new ComposerPsr4ClassLocator(new FakeSourceRepository([
            'composer.json' => json_encode([
                'config' => ['vendor-dir' => 'third-party'],
                'autoload' => ['psr-4' => ['App\\' => 'app/']],
            ]),
        ]));

        self::assertFalse($locator->isProjectSource('third-party/acme/lib/Thing.php'), 'the declared directory');
        self::assertTrue($locator->isProjectSource('vendor/acme/lib/Thing.php'), 'and only the declared one');
    }

    public function testAMemberReferenceIsNotAffected(): void
    {
        // M4 decides the *bare class* question only. A `Fqcn::member` reference asks about one
        // member, which M3 answered and which this milestone deliberately leaves alone.
        self::assertNull(
            $this->resolver()->dependencyPathFor($this->assertion('Illuminate\Support\Collection::map')),
            'a member reference is a different question'
        );
    }

    public function testNothingOutsideANamedReferenceIsEverADependencyClass(): void
    {
        foreach (AssertionKind::cases() as $kind) {
            if ($kind === AssertionKind::NamedReference) {
                continue;
            }

            self::assertNull(
                $this->resolver()->dependencyPathFor(
                    new Assertion($kind, 'Illuminate\Support\Collection', 'app/X.php', $this->region(), 'claim')
                ),
                $kind->value
            );
        }
    }

    public function testResolvingTwiceGivesTheSameAnswer(): void
    {
        $assertion = $this->assertion('Illuminate\Support\Collection');

        self::assertSame($this->resolver()->resolve($assertion), $this->resolver()->resolve($assertion));
        self::assertSame(
            $this->resolver()->dependencyPathFor($assertion),
            $this->resolver()->dependencyPathFor($assertion),
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
            'app/Models/Package.php' => $this->smallProjectClass(),
            'app/Models/Wide.php' => $this->wideProjectClass(),
            'vendor/laravel/framework/src/Illuminate/Collections/Collection.php' => $this->wideProjectClass(),
            'vendor/tiny/package/Thing.php' => "<?php\n\nnamespace Tiny\\Package;\n\n"
                . "class Thing\n{\n    public function only(): void\n    {\n    }\n}\n",
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
                    // Both the framework and a small package are mapped, exactly as the M1 fixture's
                    // variant B does. Being *placeable* is what makes the over-fetch possible, and
                    // it is not what the rule keys on. The two-row `Illuminate\` shape is Composer's
                    // real one: `Illuminate\Support\Collection` is found under `Collections/`, by
                    // falling through the more specific prefix.
                    'Illuminate\\Support\\' => [
                        'vendor/laravel/framework/src/Illuminate/Macroable',
                        'vendor/laravel/framework/src/Illuminate/Collections',
                    ],
                    'Illuminate\\' => 'vendor/laravel/framework/src/Illuminate/',
                    'Tiny\\Package\\' => 'vendor/tiny/package/',
                ],
            ],
        ]);
    }

    private function smallProjectClass(): string
    {
        return "<?php\n\nnamespace App\Models;\n\nclass Package\n{\n"
            . "    public function features(): void\n    {\n    }\n\n"
            . "    public function scopeActive(): void\n    {\n    }\n}\n";
    }

    private function wideProjectClass(): string
    {
        $members = '';

        for ($i = 1; $i <= 40; $i++) {
            $members .= sprintf("    public function member%d(): void\n    {\n    }\n\n", $i);
        }

        return "<?php\n\nnamespace App\Models;\n\nclass Wide\n{\n" . rtrim($members) . "\n}\n";
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
}
