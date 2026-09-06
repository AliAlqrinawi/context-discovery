<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Framework;

use ContextDiscovery\Discovery\Framework\LaravelFrameworkKnowledge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The two rules M2 unblocked, and the much longer list of things that must not trigger them.
 *
 * Precision is the point of this class, not recall: M1 measured that naive framework fetching
 * turns three cheap false flags into 117 fetched items and a 224x token increase, so every rule
 * here is written to fail closed. A rule that does not fire leaves today's flag in place, which is
 * a known, recorded outcome; a rule that fires wrongly puts a false statement in front of a
 * reviewer or silently swallows application code.
 *
 * Both methods are handed **one file's text** and nothing else. There is no path, no repository and
 * no locator to hand them, so no test here can even express a second-file lookup — which is
 * ADR-A010's bound made structural.
 */
final class LaravelFrameworkKnowledgeTest extends TestCase
{
    // ---------------------------------------------------------------- the facade rule

    #[DataProvider('facadeShapes')]
    public function testAFacadeDeclaresItsProxiedMemberThroughAMethodStaticTag(string $label, string $text): void
    {
        $declaration = $this->knowledge()->declarationOf('ping', $text);

        self::assertNotNull($declaration, $label);
        self::assertStringContainsString('@method static', $declaration->evidence, $label);
        self::assertStringContainsString('ping(', $declaration->evidence, $label);
        self::assertGreaterThan(0, $declaration->line, $label . ': the citation names a real line');
    }

    /**
     * Every way PHP can name the facade base, since the rule resolves the parent the way PHP does —
     * never by the bare name.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function facadeShapes(): iterable
    {
        yield 'same namespace, no import' => ['same namespace', self::facade(
            'namespace Illuminate\Support\Facades;',
            'Facade'
        )];

        yield 'imported' => ['imported', self::facade(
            "namespace App\Facades;\n\nuse Illuminate\Support\Facades\Facade;",
            'Facade'
        )];

        yield 'aliased import' => ['aliased', self::facade(
            "namespace App\Facades;\n\nuse Illuminate\Support\Facades\Facade as Base;",
            'Base'
        )];

        yield 'fully qualified' => ['fully qualified', self::facade(
            'namespace App\Facades;',
            '\Illuminate\Support\Facades\Facade'
        )];
    }

    public function testTheCitationNamesTheLineTheTagSitsOn(): void
    {
        $text = "<?php\n\nnamespace App\Facades;\n\nuse Illuminate\Support\Facades\Facade;\n\n"
            . "/**\n * @method static void first()\n * @method static void ping(int \$times)\n */\n"
            . "class Thing extends Facade\n{\n}\n";

        $declaration = $this->knowledge()->declarationOf('ping', $text);

        self::assertNotNull($declaration);
        self::assertSame(9, $declaration->line, 'the second tag is on line 9');
        self::assertSame('@method static void ping(int $times)', $declaration->evidence);
    }

    #[DataProvider('nonFacades')]
    public function testAClassThatIsNotALaravelFacadeDeclaresNothing(string $label, string $text): void
    {
        self::assertNull($this->knowledge()->declarationOf('ping', $text), $label);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nonFacades(): iterable
    {
        // The sharpest one: a different class that happens to be called `Facade`. Matching the bare
        // parent name would claim this, and it is ordinary application code.
        yield 'a different class named Facade' => ['unrelated Facade', self::facade(
            "namespace App\Support;\n\nuse App\Support\Legacy\Facade;",
            'Facade'
        )];

        yield 'an Eloquent model' => ['a model', "<?php\n\nnamespace App\Models;\n\n"
            . "use Illuminate\Database\Eloquent\Model;\n\n"
            . "/**\n * @method static void ping()\n */\n"
            . "class Package extends Model\n{\n}\n"];

        yield 'extends nothing' => ['no parent', "<?php\n\nnamespace App;\n\n"
            . "/**\n * @method static void ping()\n */\n"
            . "class Thing\n{\n}\n"];
    }

    public function testAFacadeWithoutAMatchingTagDeclaresNothing(): void
    {
        $text = self::facade('namespace Illuminate\Support\Facades;', 'Facade');

        self::assertNull($this->knowledge()->declarationOf('pong', $text), 'a member with no tag');
        self::assertNull($this->knowledge()->declarationOf('', $text), 'an empty member name');
    }

    public function testANonStaticMethodTagIsNotAFacadeSurface(): void
    {
        $text = "<?php\n\nnamespace Illuminate\Support\Facades;\n\n"
            . "/**\n * @method void ping()\n */\n"
            . "class Thing extends Facade\n{\n}\n";

        self::assertNull(
            $this->knowledge()->declarationOf('ping', $text),
            'a facade proxies statically; a non-static tag documents something else'
        );
    }

    public function testATagBelowTheClassDeclarationIsNotTheFacadeSurface(): void
    {
        $text = "<?php\n\nnamespace Illuminate\Support\Facades;\n\n"
            . "class Thing extends Facade\n{\n"
            . "    /**\n     * @method static void ping()\n     */\n"
            . "    public function other(): void\n    {\n    }\n}\n";

        self::assertNull(
            $this->knowledge()->declarationOf('ping', $text),
            'a docblock on a member documents that member, not the class surface'
        );
    }

    // ---------------------------------------------------------------- the local scope rule

    public function testALocalScopeResolvesToTheScopeMethodInTheSameFile(): void
    {
        self::assertSame('scopeActive', $this->knowledge()->sameFileMemberFor('active', self::model('scopeActive')));
    }

    public function testOnlyTheFirstLetterIsCapitalised(): void
    {
        self::assertSame(
            'scopeActiveAndPaid',
            $this->knowledge()->sameFileMemberFor('activeAndPaid', self::model('scopeActiveAndPaid')),
            'Laravel builds the name with ucfirst(), not with a case conversion'
        );
    }

    #[DataProvider('scopeMisses')]
    public function testTheScopeConventionDoesNotFire(string $label, string $member, string $text): void
    {
        self::assertNull($this->knowledge()->sameFileMemberFor($member, $text), $label);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function scopeMisses(): iterable
    {
        yield 'no scope method at all' => ['absent', 'active', self::model('features')];

        yield 'wrong capitalisation' => ['scopeactive', 'active', self::model('scopeactive')];

        // `Package::scopeActive()` is a direct call to the method. If it existed the slicer would
        // already have found it; inventing `scopeScopeActive` would be a guess.
        yield 'the member is already a scope' => ['scope prefix', 'scopeActive', self::model('scopeActive')];

        yield 'empty member name' => ['empty', '', self::model('scopeActive')];

        yield 'declared on a facade rather than a model' => ['facade', 'active', self::facade(
            'namespace Illuminate\Support\Facades;',
            'Facade'
        )];
    }

    // ---------------------------------------------------------------- text that is not code

    /**
     * The negative controls M1 built scenario S09 for. A member name mentioned in prose or in data
     * is not a declaration, and neither rule may read one as evidence.
     */
    #[DataProvider('mentionsThatAreNotDeclarations')]
    public function testAMentionIsNotADeclaration(string $label, string $text): void
    {
        self::assertNull($this->knowledge()->declarationOf('ping', $text), $label . ': facade rule');
        self::assertNull($this->knowledge()->sameFileMemberFor('active', $text), $label . ': scope rule');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function mentionsThatAreNotDeclarations(): iterable
    {
        $head = "<?php\n\nnamespace Illuminate\Support\Facades;\n\nclass Thing extends Facade\n{\n";

        yield 'line comment' => ['a // comment', $head
            . "    // @method static void ping() and function scopeActive() used to live here.\n}\n"];

        yield 'docblock prose on a member' => ['member docblock', $head
            . "    /**\n     * Historically @method static void ping(), via function scopeActive().\n     */\n"
            . "    public function other(): void\n    {\n    }\n}\n"];

        yield 'string literals' => ['string literals', $head
            . "    public function names(): array\n    {\n"
            . "        return ['@method static void ping()', 'function scopeActive()', 'scopeActive'];\n"
            . "    }\n}\n"];

        yield '::class constant' => ['::class', $head
            . "    public function which(): string\n    {\n        return Thing::class;\n    }\n}\n"];
    }

    // ---------------------------------------------------------------- determinism

    public function testTheSameTextAlwaysGivesTheSameAnswer(): void
    {
        $facade = self::facade('namespace Illuminate\Support\Facades;', 'Facade');
        $model = self::model('scopeActive');
        $knowledge = $this->knowledge();

        self::assertEquals(
            $knowledge->declarationOf('ping', $facade),
            $knowledge->declarationOf('ping', $facade),
            'P8'
        );
        self::assertSame(
            $knowledge->sameFileMemberFor('active', $model),
            $knowledge->sameFileMemberFor('active', $model),
            'P8'
        );
    }

    // ---------------------------------------------------------------- helpers

    private function knowledge(): LaravelFrameworkKnowledge
    {
        return new LaravelFrameworkKnowledge();
    }

    private static function facade(string $header, string $parent): string
    {
        return "<?php\n\n" . $header . "\n\n"
            . "/**\n * @method static void ping(int \$times)\n */\n"
            . 'class Thing extends ' . $parent . "\n{\n}\n";
    }

    private static function model(string $member): string
    {
        return "<?php\n\nnamespace App\Models;\n\n"
            . "use Illuminate\Database\Eloquent\Model;\n\n"
            . "class Package extends Model\n{\n"
            . '    public function ' . $member . "(): void\n    {\n    }\n}\n";
    }
}
