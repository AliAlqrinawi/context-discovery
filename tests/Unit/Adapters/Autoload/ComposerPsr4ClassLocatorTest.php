<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Adapters\Autoload;

use ContextDiscovery\Adapters\Autoload\ComposerPsr4ClassLocator;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComposerPsr4ClassLocator::class)]
final class ComposerPsr4ClassLocatorTest extends TestCase
{
    public function testAClassNameResolvesThroughThePsr4Map(): void
    {
        self::assertSame(
            'app/Models/PlaidAccount.php',
            $this->locator()->pathFor('App\Models\PlaidAccount')
        );
    }

    public function testALeadingSeparatorIsIgnored(): void
    {
        self::assertSame(
            'app/Models/PlaidAccount.php',
            $this->locator()->pathFor('\App\Models\PlaidAccount')
        );
    }

    public function testAutoloadDevIsReadAsWellAsAutoload(): void
    {
        // A changed file may reference either.
        self::assertSame('tests/Support/Helper.php', $this->locator()->pathFor('App\Tests\Support\Helper'));
    }

    public function testTheLongestMatchingPrefixWins(): void
    {
        // `App\Services\` is more specific than `App\`, so it decides — as PSR-4 requires.
        self::assertSame(
            'src/services/Plaid/Client.php',
            $this->locator()->pathFor('App\Services\Plaid\Client')
        );
    }

    public function testSeveralDirectoriesForOnePrefixAreTriedInDeclaredOrder(): void
    {
        self::assertSame('app/Second/Thing.php', $this->locator()->pathFor('Multi\Thing'));
    }

    #[DataProvider('unplaceableNames')]
    public function testANameTheMapCannotPlaceResolvesToNull(string $class): void
    {
        // The lever policy then flags it: the contract is stated as unverified, never hunted (P10).
        self::assertNull($this->locator()->pathFor($class));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unplaceableNames(): iterable
    {
        yield 'no matching prefix' => ['Vendor\Package\Thing'];
        yield 'prefix matches but the file is absent' => ['App\Models\NotOnDisk'];
        yield 'empty name' => [''];
        yield 'separator only' => ['\\'];
    }

    #[DataProvider('unusableManifests')]
    public function testAnUnusableManifestPlacesNothingRatherThanGuessing(array $files): void
    {
        // "Missing map ⇒ every NamedReference becomes a flag, plus one diagnostic."
        $locator = new ComposerPsr4ClassLocator(new FakeSourceRepository($files));

        self::assertNull($locator->pathFor('App\Models\PlaidAccount'));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function unusableManifests(): iterable
    {
        yield 'no composer.json at all' => [['app/Models/PlaidAccount.php' => '<?php']];

        yield 'composer.json is not valid json' => [[
            'composer.json' => '{ not json',
            'app/Models/PlaidAccount.php' => '<?php',
        ]];

        yield 'no autoload section' => [[
            'composer.json' => '{"name":"acme/app"}',
            'app/Models/PlaidAccount.php' => '<?php',
        ]];

        yield 'autoload has no psr-4 map' => [[
            'composer.json' => '{"autoload":{"classmap":["app/"]}}',
            'app/Models/PlaidAccount.php' => '<?php',
        ]];
    }

    public function testAnEmptyPrefixMapsEveryName(): void
    {
        $locator = new ComposerPsr4ClassLocator(new FakeSourceRepository([
            'composer.json' => '{"autoload":{"psr-4":{"":"lib/"}}}',
            'lib/Thing.php' => '<?php',
        ]));

        self::assertSame('lib/Thing.php', $locator->pathFor('Thing'));
    }

    public function testResolvingTwiceGivesTheSameAnswer(): void
    {
        $locator = $this->locator();

        self::assertSame(
            $locator->pathFor('App\Models\PlaidAccount'),
            $locator->pathFor('App\Models\PlaidAccount')
        );
    }

    private function locator(): ComposerPsr4ClassLocator
    {
        return new ComposerPsr4ClassLocator(new FakeSourceRepository([
            'composer.json' => json_encode([
                'autoload' => [
                    'psr-4' => [
                        'App\\' => 'app/',
                        'App\\Services\\' => 'src/services/',
                        'Multi\\' => ['app/First/', 'app/Second/'],
                    ],
                ],
                'autoload-dev' => [
                    'psr-4' => ['App\\Tests\\' => 'tests/'],
                ],
            ], JSON_THROW_ON_ERROR),
            'app/Models/PlaidAccount.php' => '<?php',
            'src/services/Plaid/Client.php' => '<?php',
            'app/Second/Thing.php' => '<?php',
            'tests/Support/Helper.php' => '<?php',
        ]));
    }
}
