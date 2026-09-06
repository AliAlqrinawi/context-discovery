<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Adapters\Autoload;

use ContextDiscovery\Adapters\Autoload\ComposerPsr4ClassLocator;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * M10 · the two states `pathFor()` collapses into one null (ADR-A017).
 *
 * A class that cannot be placed is in one of two situations, and they are not the same thing to a
 * reader: the project declares **no prefix** that covers the name, or it declares one and there is
 * **no file** at the end of it. The first is a mapping to add; the second is a file to find.
 *
 * `hasMappingFor()` separates them from the map already in memory. It opens no file — a class whose
 * prefix is declared but whose file is missing must answer `true`, which is only possible if the
 * answer never depends on the file existing.
 */
final class ClassLocatorMappingTest extends TestCase
{
    #[DataProvider('coveredNames')]
    public function testAPrefixThatCoversTheNameIsReportedRegardlessOfTheFile(string $label, string $class): void
    {
        self::assertTrue($this->locator()->hasMappingFor($class), $label);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function coveredNames(): iterable
    {
        yield 'mapped and present' => ['App\Models\Package', 'App\Models\Package'];
        yield 'mapped, file absent' => ['App\Ghost\Missing', 'App\Ghost\Missing'];
        yield 'deeply nested under a mapped prefix' => ['App\Foo\Bar\Deep', 'App\Foo\Bar\Deep'];
        yield 'a second, longer prefix' => ['Acme\Lib\Thing', 'Acme\Lib\Thing'];
        yield 'leading separator is tolerated' => ['\App\Ghost\Missing', '\App\Ghost\Missing'];
    }

    #[DataProvider('uncoveredNames')]
    public function testANameNoPrefixCoversIsReportedAsUnmapped(string $label, string $class): void
    {
        self::assertFalse($this->locator()->hasMappingFor($class), $label);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function uncoveredNames(): iterable
    {
        yield 'no prefix at all' => ['MissingNamespace\Ghost', 'MissingNamespace\Ghost'];
        yield 'a global class' => ['Closure', 'Closure'];
        yield 'a near-miss on a real prefix' => ['Application\Models\Package', 'Application\Models\Package'];
        yield 'the empty name' => ['(empty)', ''];
    }

    /**
     * The distinguishing property: the answer must not depend on the file being there, or the very
     * case this exists for — mapped prefix, missing file — would report "unmapped" again.
     */
    public function testTheAnswerDoesNotDependOnTheFileExisting(): void
    {
        $locator = $this->locator();

        self::assertTrue($locator->hasMappingFor('App\Ghost\Missing'), 'mapped');
        self::assertNull($locator->pathFor('App\Ghost\Missing'), 'and unplaceable — both at once');
    }

    public function testAProjectWithNoManifestMapsNothing(): void
    {
        $locator = new ComposerPsr4ClassLocator(new FakeSourceRepository([]));

        self::assertFalse($locator->hasMappingFor('App\Models\Package'), 'no manifest, no mapping');
        self::assertNull($locator->pathFor('App\Models\Package'));
    }

    public function testAnEmptyPrefixCoversEverything(): void
    {
        // A project may map the root namespace. `pathFor()` already treats '' as matching any name,
        // so this must agree with it rather than inventing a second rule.
        $locator = new ComposerPsr4ClassLocator(new FakeSourceRepository([
            'composer.json' => (string) json_encode(['autoload' => ['psr-4' => ['' => 'src/']]]),
        ]));

        self::assertTrue($locator->hasMappingFor('Anything\At\All'));
    }

    public function testTheAnswerIsStable(): void
    {
        self::assertSame(
            $this->locator()->hasMappingFor('App\Ghost\Missing'),
            $this->locator()->hasMappingFor('App\Ghost\Missing'),
            'P8'
        );
    }

    private function locator(): ComposerPsr4ClassLocator
    {
        return new ComposerPsr4ClassLocator(new FakeSourceRepository([
            'composer.json' => (string) json_encode([
                'autoload' => ['psr-4' => ['App\\' => 'app/', 'Acme\\Lib\\' => 'lib/']],
            ]),
            'app/Models/Package.php' => '<?php',
        ]));
    }
}
