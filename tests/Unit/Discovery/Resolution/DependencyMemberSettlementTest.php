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
 * M5 · a declared dependency member is settled, an undeclared one still flags.
 *
 * ADR-A012 stopped a bare dependency *class* from being dumped and deliberately left the *member*
 * case alone. ADR-A013 closes it, on the sentence that authorises the move in the first place:
 * `context-types.md` type 2 is *"Named collaborator code (**application code**, depth one)"* and
 * covers *"a class, model, enum, or **method**"*. Fetching a package's method was never earned.
 *
 * Two things this file exists to pin, because they are where the rule could do harm:
 *
 * 1. **Ownership alone is not enough.** `Str::slugg()` is a typo against a real dependency. Settling
 *    it on ownership would report a nonexistent method as provided — the silent omission P10 forbids
 *    and M0 records as risk R1. A verdict needs positive evidence that the member is *there*.
 * 2. **M3 is preserved and still necessary.** `Log::info` keeps its `@method static` citation, which
 *    says more than a path can; and the facade rule is the only one that reaches a *project* facade,
 *    where ownership says nothing at all.
 */
final class DependencyMemberSettlementTest extends TestCase
{
    // ---------------------------------------------------------------- the S06 case

    #[DataProvider('declaredDependencyMembers')]
    public function testADeclaredDependencyMemberIsSettledAndNotFetched(string $subject, string $path): void
    {
        $assertion = $this->assertion($subject);

        self::assertSame([], $this->resolver()->resolve($assertion), 'the dependency source is not fetched');
        self::assertTrue($this->resolver()->lookupRan($assertion), 'the question was answered, not failed');

        $citation = $this->resolver()->dependencyMemberDeclarationFor($assertion);

        self::assertNotNull($citation);
        self::assertStringStartsWith($path . ':', $citation, 'cited to the file and the line');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function declaredDependencyMembers(): iterable
    {
        yield 'Str::slug' => ['Vendorish\Support\Str::slug', 'vendor/vendorish/support/Str.php'];
        yield 'Arr::only' => ['Vendorish\Support\Arr::only', 'vendor/vendorish/support/Arr.php'];
    }

    public function testTheFetchPathNeverOpensADependencyFile(): void
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

        self::assertSame([], $resolver->resolve($this->assertion('Vendorish\Support\Str::slug')));

        foreach ($reads as $path) {
            // Composer's generated map is metadata, not source — see the note in
            // DependencyClassSurfaceTest. A dependency's own file is what must stay unread here.
            if (str_starts_with($path, 'vendor/composer/')) {
                continue;
            }

            self::assertStringStartsNotWith('vendor/', $path, 'nothing that could become payload was read');
        }
    }

    // ---------------------------------------------------------------- M3 preserved

    public function testAFacadeKeepsItsTagCitationRatherThanTheOwnershipOne(): void
    {
        $assertion = $this->assertion('Vendorish\Facades\Log::info');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertNotNull(
            $this->resolver()->frameworkDeclarationFor($assertion),
            'the tag is the evidence, and it is richer than a path'
        );
        self::assertNull(
            $this->resolver()->dependencyMemberDeclarationFor($assertion),
            'the member is not declared as code, so the ownership rule has nothing to cite'
        );
    }

    public function testAProjectFacadeIsStillReachedOnlyByTheFacadeRule(): void
    {
        // Under `app/`, so ownership says nothing. Without M3's rule this would flag.
        $assertion = $this->assertion('App\Facades\Pay::charge');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertNull($this->resolver()->dependencyMemberDeclarationFor($assertion), 'not a dependency');
        self::assertNotNull($this->resolver()->frameworkDeclarationFor($assertion), 'the facade rule covers it');
        self::assertTrue($this->resolver()->lookupRan($assertion));
    }

    // ---------------------------------------------------------------- the guards

    public function testATypoAgainstARealDependencyStillFlags(): void
    {
        $assertion = $this->assertion('Vendorish\Support\Str::slugg');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertFalse(
            $this->resolver()->lookupRan($assertion),
            'ownership alone must never settle a member — this is the silent-omission guard'
        );
        self::assertNull($this->resolver()->dependencyMemberDeclarationFor($assertion));
    }

    public function testAProjectMemberIsStillFetched(): void
    {
        $slices = $this->resolver()->resolve($this->assertion('App\Services\Registry::create'));

        self::assertCount(1, $slices);
        self::assertSame('app/Services/Registry.php', $slices[0]->path);
        self::assertSame('create', $slices[0]->member);
    }

    public function testAProjectClassWithAMemberNamedSlugIsNotFrameworkKnown(): void
    {
        // Same member name as the dependency case, different owner. The rule keys on ownership and
        // never on the name, so this must be fetched exactly as before.
        $assertion = $this->assertion('App\Support\Text::slug');
        $slices = $this->resolver()->resolve($assertion);

        self::assertCount(1, $slices, 'the project\'s own method is fetched');
        self::assertSame('app/Support/Text.php', $slices[0]->path);
        self::assertNull($this->resolver()->dependencyMemberDeclarationFor($assertion));
        self::assertNull($this->resolver()->frameworkDeclarationFor($assertion));
    }

    public function testAnUnknownMemberOnAProjectClassStillFlags(): void
    {
        $assertion = $this->assertion('App\Services\Registry::nope');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertFalse($this->resolver()->lookupRan($assertion));
    }

    public function testAnUnplaceableClassStillFlags(): void
    {
        $assertion = $this->assertion('Nowhere\At\All::thing');

        self::assertSame([], $this->resolver()->resolve($assertion));
        self::assertFalse($this->resolver()->lookupRan($assertion));
    }

    // ---------------------------------------------------------------- lexical controls

    /**
     * A member named only in prose or in data is not declared. `declaresFunction`-style evidence is
     * read from tokens, so a mention can never satisfy the rule.
     */
    #[DataProvider('mentionsThatAreNotDeclarations')]
    public function testAMentionInADependencyFileIsNotADeclaration(string $label, string $body): void
    {
        $files = $this->files();
        $files['vendor/vendorish/support/Mentions.php'] = "<?php\n\nnamespace Vendorish\Support;\n\n"
            . "class Mentions\n{\n" . $body . "}\n";

        $source = new FakeSourceRepository($files);
        $resolver = new NamedReferenceResolver(
            new ComposerPsr4ClassLocator($source),
            $source,
            new TokenizerMemberSlicer(),
            new LaravelFrameworkKnowledge()
        );

        $assertion = $this->assertion('Vendorish\Support\Mentions::ghost');

        self::assertNull($resolver->dependencyMemberDeclarationFor($assertion), $label);
        self::assertFalse($resolver->lookupRan($assertion), $label . ': and it still flags');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function mentionsThatAreNotDeclarations(): iterable
    {
        yield 'line comment' => ['a // comment', "    // public function ghost(): void {}\n"];
        yield 'docblock' => ['a docblock', "    /**\n     * @see ghost() and function ghost()\n     */\n"
            . "    public function other(): void\n    {\n    }\n"];
        yield 'string literal' => ['a string', "    public function names(): array\n    {\n"
            . "        return ['ghost', 'function ghost()'];\n    }\n"];
        yield '::class constant' => ['::class', "    public function which(): string\n    {\n"
            . "        return Mentions::class;\n    }\n"];
    }

    // ---------------------------------------------------------------- determinism

    public function testTheAnswerIsStableAcrossRuns(): void
    {
        $assertion = $this->assertion('Vendorish\Support\Str::slug');

        self::assertSame(
            $this->resolver()->dependencyMemberDeclarationFor($assertion),
            $this->resolver()->dependencyMemberDeclarationFor($assertion),
            'P8'
        );
        self::assertSame($this->resolver()->resolve($assertion), $this->resolver()->resolve($assertion));
    }

    // ---------------------------------------------------------------- fixtures

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

    /**
     * @return array<string, string>
     */
    private function files(): array
    {
        return [
            'composer.json' => (string) json_encode([
                'autoload' => [
                    'psr-4' => [
                        'App\\' => 'app/',
                        'Vendorish\\Support\\' => 'vendor/vendorish/support/',
                        'Vendorish\\Facades\\' => 'vendor/vendorish/facades/',
                    ],
                ],
            ]),

            // A dependency's real, declared static helpers — the `Str::slug` / `Arr::only` shape.
            'vendor/vendorish/support/Str.php' => "<?php\n\nnamespace Vendorish\Support;\n\n"
                . "class Str\n{\n    public static function slug(string \$title): string\n    {\n"
                . "        return strtolower(\$title);\n    }\n}\n",
            'vendor/vendorish/support/Arr.php' => "<?php\n\nnamespace Vendorish\Support;\n\n"
                . "class Arr\n{\n    public static function only(array \$array, array \$keys): array\n    {\n"
                . "        return array_intersect_key(\$array, array_flip(\$keys));\n    }\n}\n",

            // A dependency facade — declared by a tag, not by code (the `Log::info` shape).
            'vendor/vendorish/facades/Log.php' => "<?php\n\nnamespace Illuminate\Support\Facades;\n\n"
                . "/**\n * @method static void info(string \$message)\n */\n"
                . "class Log extends Facade\n{\n}\n",

            // A project facade — ownership says nothing, so only the facade rule reaches it.
            'app/Facades/Pay.php' => "<?php\n\nnamespace App\Facades;\n\n"
                . "use Illuminate\Support\Facades\Facade;\n\n"
                . "/**\n * @method static void charge(int \$cents)\n */\n"
                . "class Pay extends Facade\n{\n}\n",

            'app/Services/Registry.php' => "<?php\n\nnamespace App\Services;\n\n"
                . "final class Registry\n{\n    public static function create(array \$entries): self\n    {\n"
                . "        return new self();\n    }\n}\n",

            // The project's own `slug`, to prove the rule keys on ownership and not on the name.
            'app/Support/Text.php' => "<?php\n\nnamespace App\Support;\n\n"
                . "final class Text\n{\n    public static function slug(string \$title): string\n    {\n"
                . "        return trim(\$title);\n    }\n}\n",
        ];
    }

    private function assertion(string $subject): Assertion
    {
        return new Assertion(
            AssertionKind::NamedReference,
            $subject,
            'app/Repositories/PackageRepository.php',
            new ChangedRegion(1, 2, ['x'], []),
            'the region depends on ' . $subject . ', whose contract is defined in another file',
        );
    }
}
