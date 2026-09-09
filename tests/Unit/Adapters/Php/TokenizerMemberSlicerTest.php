<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Adapters\Php;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenizerMemberSlicer::class)]
final class TokenizerMemberSlicerTest extends TestCase
{
    private TokenizerMemberSlicer $slicer;

    protected function setUp(): void
    {
        $this->slicer = new TokenizerMemberSlicer();
    }

    // ---------------------------------------------------------------- use block

    public function testTheUseBlockCoversTheImportsAndNothingElse(): void
    {
        $slice = $this->slicer->useBlock($this->service());

        self::assertNotNull($slice);
        self::assertSame(7, $slice->firstLine);
        self::assertSame(10, $slice->lastLine);
        self::assertStringContainsString('use Illuminate\Support\Facades\Cache;', $slice->text);
        self::assertStringContainsString('use App\Models\{PlaidAccount, PlaidItem};', $slice->text);
        self::assertStringContainsString('use function array_map;', $slice->text);
        self::assertStringNotContainsString('SoftDeletes', $slice->text);
        self::assertStringNotContainsString('namespace', $slice->text);
    }

    public function testATraitUseInsideAClassBodyIsNotAnImport(): void
    {
        // `use SoftDeletes;` sits on line 14, inside the class body.
        $slice = $this->slicer->useBlock($this->service());

        self::assertNotNull($slice);
        self::assertLessThan(14, $slice->lastLine);
    }

    public function testAClosureUseClauseIsNotAnImport(): void
    {
        $slice = $this->slicer->useBlock($this->script());

        self::assertNotNull($slice);
        self::assertSame(3, $slice->firstLine);
        self::assertSame(3, $slice->lastLine);
        self::assertStringNotContainsString('function', $slice->text);
    }

    public function testAFileWithNoImportsHasNoUseBlock(): void
    {
        self::assertNull($this->slicer->useBlock("<?php\n\nclass Bare\n{\n}\n"));
    }

    // ---------------------------------------------------------------- members

    public function testAMethodSliceIsBraceBalancedAcrossClosuresStringsHeredocsAndMatchArms(): void
    {
        $slice = $this->slicer->member($this->service(), 'withBraces');

        self::assertNotNull($slice);
        self::assertSame('withBraces', $slice->member);
        self::assertSame(21, $slice->firstLine, 'the attribute line belongs to the member (ADR-A004)');
        self::assertSame(38, $slice->lastLine);

        self::assertStringStartsWith('    #[Deprecated]', $slice->text);
        self::assertStringEndsWith('    }', $slice->text);

        // Every brace hazard is inside the slice, and none of them ended it early.
        self::assertStringContainsString('use ($label)', $slice->text);
        self::assertStringContainsString('a { brace }', $slice->text);
        self::assertStringContainsString('{"k": 1}', $slice->text);
        self::assertStringContainsString('match (true)', $slice->text);

        // And the member after it is not swept in.
        self::assertStringNotContainsString('__construct', $slice->text);
    }

    public function testAPropertySliceCarriesItsDocblockAndStopsAtTheSemicolon(): void
    {
        $slice = $this->slicer->member($this->service(), 'fillable');

        self::assertNotNull($slice);
        self::assertSame(16, $slice->firstLine, 'the annotation belongs to the member (ADR-A004)');
        self::assertSame(17, $slice->lastLine);
        self::assertStringContainsString('@var list<string>', $slice->text);
        self::assertStringContainsString("protected array \$fillable = ['name', 'mask'];", $slice->text);
    }

    public function testAConstantSliceIsFound(): void
    {
        $slice = $this->slicer->member($this->service(), 'MODE');

        self::assertNotNull($slice);
        self::assertSame(19, $slice->firstLine);
        self::assertSame(19, $slice->lastLine);
    }

    public function testAConstructorWithPromotedPropertiesSlicesCleanly(): void
    {
        $slice = $this->slicer->member($this->service(), '__construct');

        self::assertNotNull($slice);
        self::assertSame(40, $slice->firstLine);
        self::assertSame(44, $slice->lastLine);
        // The default value is a closing brace inside a string; it must not end the member.
        self::assertStringContainsString("\$token = '}'", $slice->text);
    }

    public function testEnumCasesAreMembers(): void
    {
        $enum = $this->enum();

        self::assertSame(['ACTIVE', 'REVOKED', 'isInactive'], $this->slicer->memberNames($enum));

        $slice = $this->slicer->member($enum, 'REVOKED');

        self::assertNotNull($slice);
        self::assertSame(6, $slice->firstLine);
        self::assertSame(6, $slice->lastLine);
    }

    public function testAnInterfaceMethodEndsAtItsSemicolon(): void
    {
        $slice = $this->slicer->member($this->contract(), 'handle');

        self::assertNotNull($slice);
        self::assertSame(5, $slice->firstLine);
        self::assertSame(5, $slice->lastLine);
        self::assertStringNotContainsString('other', $slice->text);
    }

    public function testAnAbstractMethodEndsAtItsSemicolon(): void
    {
        $source = "<?php\n\nabstract class Base\n{\n    abstract protected function run(int \$a): void;\n\n"
            . "    public function go(): void\n    {\n    }\n}\n";

        $slice = $this->slicer->member($source, 'run');

        self::assertNotNull($slice);
        self::assertSame(5, $slice->firstLine);
        self::assertSame(5, $slice->lastLine);
    }

    public function testMemberNamesListsEveryDeclarationInSourceOrder(): void
    {
        self::assertSame(
            ['fillable', 'MODE', 'withBraces', '__construct', 'last'],
            $this->slicer->memberNames($this->service())
        );
    }

    public function testNeitherTheTraitUseNorAnythingInsideAMemberIsListed(): void
    {
        $names = $this->slicer->memberNames($this->service());

        self::assertNotContains('SoftDeletes', $names);
        self::assertNotContains('closure', $names, 'a closure inside a method is not a member');
        self::assertNotContains('item', $names, 'a promoted parameter is not listed as a member');
        self::assertNotContains('array_map', $names, '`use function` imports, it does not declare');
    }

    public function testATopLevelFunctionIsAMember(): void
    {
        self::assertSame(['topLevel'], $this->slicer->memberNames($this->script()));
    }

    #[DataProvider('unfindableMembers')]
    public function testAnUnfindableMemberReturnsNullRatherThanGuessing(string $name): void
    {
        self::assertNull($this->slicer->member($this->service(), $name));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unfindableMembers(): iterable
    {
        yield 'no such member' => ['doesNotExist'];
        yield 'a local variable, not a member' => ['closure'];
        yield 'a used trait, not a member' => ['SoftDeletes'];
        yield 'a promoted parameter' => ['item'];
    }

    public function testAnUnbalancedFileYieldsNullRatherThanAWrongSlice(): void
    {
        $truncated = "<?php\n\nclass Broken\n{\n    public function go(): void\n    {\n";

        self::assertNull($this->slicer->member($truncated, 'go'));
    }

    // ---------------------------------------------------------------- enclosing member

    #[DataProvider('linesAndTheirMembers')]
    public function testEnclosingMemberName(int $line, ?string $expected): void
    {
        self::assertSame($expected, $this->slicer->enclosingMemberName($this->service(), $line));
    }

    /**
     * @return iterable<string, array{int, string|null}>
     */
    public static function linesAndTheirMembers(): iterable
    {
        yield 'the namespace line is in no member' => [5, null];
        yield 'the import block is in no member' => [8, null];
        yield 'a property declaration' => [17, 'fillable'];
        yield 'the attribute above a method' => [21, 'withBraces'];
        yield 'deep inside a method body' => [31, 'withBraces'];
        yield 'the closing brace of a method' => [38, 'withBraces'];
        yield 'inside the constructor parameter list' => [41, '__construct'];
        yield 'the final member' => [47, 'last'];
        yield 'past the end of the file' => [999, null];
    }

    // ---------------------------------------------------------------- owning member

    /**
     * `memberOwningLine` answers a different question from `enclosingMemberName`: not *which member
     * am I reading?* but *whose statement is this?*. The two part company inside a closure, which is
     * exactly where attributing a `return` to the surrounding method states something false.
     */
    #[DataProvider('linesAndTheirOwningMembers')]
    public function testMemberOwningLine(int $line, ?string $expected): void
    {
        self::assertSame($expected, $this->slicer->memberOwningLine($this->service(), $line));
    }

    /**
     * @return iterable<string, array{int, string|null}>
     */
    public static function linesAndTheirOwningMembers(): iterable
    {
        // The fixture's closure occupies lines 24-26 inside `withBraces`.
        yield 'a statement inside a closure belongs to no member' => [25, null];
        yield 'the line the closure opens on is not the member\'s own either' => [24, null];

        // Everything below is the member's own, and must be unaffected.
        yield 'the attribute above a method' => [21, 'withBraces'];
        yield 'a heredoc line deep in the body, past the closure' => [31, 'withBraces'];
        yield 'a match arm holding a brace in a string' => [35, 'withBraces'];
        yield 'the closing brace of a method' => [38, 'withBraces'];
        yield 'a property declaration' => [17, 'fillable'];
        yield 'the constructor parameter list, default value `}`' => [41, '__construct'];
        yield 'the namespace line is in no member' => [5, null];
        yield 'past the end of the file' => [999, null];
    }

    public function testAClosureInsideAClosureIsStillNotTheMembersOwn(): void
    {
        $text = <<<'PHP'
        <?php

        class C
        {
            public function outer(): Collection
            {
                return $this->items->map(function ($b) {
                    return collect()->each(function ($c) {
                        return $c->rel->get();
                    });
                });
            }
        }
        PHP;

        self::assertSame('outer', $this->slicer->enclosingMemberName($text, 9), 'the line is read inside outer');
        self::assertNull($this->slicer->memberOwningLine($text, 9), 'but it is two closures deep, so it is not outer\'s');
    }

    public function testAMethodOfAnAnonymousClassIsNotTheSurroundingMembersOwn(): void
    {
        $text = <<<'PHP'
        <?php

        class C
        {
            public function outer(): object
            {
                return new class {
                    public function inner(): Collection
                    {
                        return $this->q->get();
                    }
                };
            }
        }
        PHP;

        self::assertNull($this->slicer->memberOwningLine($text, 10));
    }

    public function testAnUnbalancedFileOwnsNoLine(): void
    {
        // The scope cannot be established, so nothing is claimed rather than guessed (P10).
        $truncated = "<?php\n\nclass Broken\n{\n    public function go(): void\n    {\n        return \$q->get();\n";

        self::assertNull($this->slicer->memberOwningLine($truncated, 7));
    }

    // ---------------------------------------------------------------- fixtures

    /**
     * Line numbers are asserted above, so this fixture is load-bearing: keep it stable.
     */
    private function service(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Plaid;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\{PlaidAccount, PlaidItem};
use function array_map;

class PlaidAccountService
{
    use SoftDeletes;

    /** @var list<string> */
    protected array $fillable = ['name', 'mask'];

    public const MODE = 'update';

    #[Deprecated]
    public function withBraces(string $label): string
    {
        $closure = function (int $a) use ($label) {
            return $a + strlen('{');
        };

        $text = "a { brace } and {$label} interpolation";

        $sql = <<<SQL
            select * from t where j = '{"k": 1}'
            SQL;

        return match (true) {
            $label === 'x' => '{',
            default => $text . $sql . $closure(1),
        };
    }

    public function __construct(
        private readonly PlaidItem $item,
        private string $token = '}',
    ) {
    }

    private function last(): void
    {
    }
}
PHP;
    }

    private function script(): string
    {
        return <<<'PHP'
<?php

use App\Thing;

$fn = function (int $a) use ($x) {
    return $a;
};

function topLevel(int $a): int
{
    return $a;
}
PHP;
    }

    private function enum(): string
    {
        return <<<'PHP'
<?php

enum PlaidItemStatus: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';

    public function isInactive(): bool
    {
        return $this === self::REVOKED;
    }
}
PHP;
    }

    private function contract(): string
    {
        return <<<'PHP'
<?php

interface Thing
{
    public function handle(int $a): bool;

    public function other(): void;
}
PHP;
    }
}
