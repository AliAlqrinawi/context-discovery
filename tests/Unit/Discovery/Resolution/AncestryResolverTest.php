<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Resolution;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Discovery\Resolution\AncestryResolver;
use ContextDiscovery\Domain\Source\AncestorDeclaration;
use ContextDiscovery\Domain\Source\AncestryBoundary;
use ContextDiscovery\Tests\Fakes\FakeClassLocator;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * ADR-A028 §6 — the D2 walk, and every condition under which it does **not** produce S1.
 *
 * One test per row of the fail-closed table, plus the positive cases E5.4 (trait, one hop) and
 * H.3 (parent's trait, two hops) are built from. The walk cites; it never fetches, and the only
 * thing it returns about a declaration is its location.
 */
#[CoversClass(AncestryResolver::class)]
final class AncestryResolverTest extends TestCase
{
    // ---------------------------------------------------------------- S1: found in project code

    public function testAMemberDeclaredByTheClassOwnTraitIsCitedAtOneHop(): void
    {
        // E5.4's shape: `MenuPdfController uses ApiResponse; $this->success(`.
        $found = $this->walk([
            'app/Http/Controllers/MenuPdfController.php' => $this->type('App\Http\Controllers', 'MenuPdfController', traits: ['App\Traits\ApiResponse']),
            'app/Traits/ApiResponse.php' => $this->trait('App\Traits', 'ApiResponse', ['success']),
        ], 'App\Http\Controllers\MenuPdfController', 'success');

        self::assertInstanceOf(AncestorDeclaration::class, $found);
        self::assertSame('App\Traits\ApiResponse', $found->declaringClass);
        self::assertSame('app/Traits/ApiResponse.php', $found->path);
        self::assertSame(7, $found->line, 'the line of the declaration, read from the file');
        self::assertTrue($found->viaTrait);
        self::assertSame('App\Http\Controllers\MenuPdfController', $found->appliedBy, 'the class itself uses the trait');
        self::assertSame([], $found->walkedParents, 'no parent was walked, so the statement has no "not in its parent" clause');
    }

    public function testAMemberDeclaredByTheParentTraitIsCitedAtTwoHops(): void
    {
        // H.3's shape: `MenuController extends Controller; Controller uses ApiResponse`.
        $found = $this->walk([
            'app/Http/Controllers/MenuController.php' => $this->type('App\Http\Controllers', 'MenuController', parent: 'Controller'),
            'app/Http/Controllers/Controller.php' => $this->type('App\Http\Controllers', 'Controller', traits: ['App\Traits\ApiResponse']),
            'app/Traits/ApiResponse.php' => $this->trait('App\Traits', 'ApiResponse', ['success']),
        ], 'App\Http\Controllers\MenuController', 'success');

        self::assertInstanceOf(AncestorDeclaration::class, $found);
        self::assertSame('App\Traits\ApiResponse', $found->declaringClass);
        self::assertTrue($found->viaTrait);
        self::assertSame('App\Http\Controllers\Controller', $found->appliedBy, 'the parent uses the trait');
        self::assertSame(['App\Http\Controllers\Controller'], $found->walkedParents);
    }

    public function testAMemberDeclaredDirectlyByTheParentIsCited(): void
    {
        $found = $this->walk([
            'app/A.php' => $this->type('App', 'A', parent: 'B'),
            'app/B.php' => $this->type('App', 'B', members: ['helper']),
        ], 'App\A', 'helper');

        self::assertInstanceOf(AncestorDeclaration::class, $found);
        self::assertSame('App\B', $found->declaringClass);
        self::assertFalse($found->viaTrait);
        self::assertNull($found->appliedBy);
        self::assertSame('app/B.php', $found->path);
    }

    public function testAnAbstractDeclarationIsADeclaration(): void
    {
        // §6: "an abstract declaration *is* the declared contract, and the citation lands on it".
        $found = $this->walk([
            'app/A.php' => $this->type('App', 'A', parent: 'B'),
            'app/B.php' => "<?php\n\nnamespace App;\n\nabstract class B\n{\n    abstract protected function helper(): void;\n}\n",
        ], 'App\A', 'helper');

        self::assertInstanceOf(AncestorDeclaration::class, $found);
        self::assertSame('App\B', $found->declaringClass);
        self::assertSame(7, $found->line);
    }

    public function testATraitOwnTraitsAreWalkedLikeAType(): void
    {
        $found = $this->walk([
            'app/A.php' => $this->type('App', 'A', traits: ['App\Outer']),
            'app/Outer.php' => "<?php\n\nnamespace App;\n\ntrait Outer\n{\n    use Inner;\n}\n",
            'app/Inner.php' => $this->trait('App', 'Inner', ['helper']),
        ], 'App\A', 'helper');

        self::assertInstanceOf(AncestorDeclaration::class, $found);
        self::assertSame('App\Inner', $found->declaringClass);
        self::assertSame('App\Outer', $found->appliedBy);
    }

    public function testATraitMemberTakesPrecedenceOverTheParentMember(): void
    {
        // PHP's rule, applied without inference: the trait's declaration is the one in force.
        $found = $this->walk([
            'app/A.php' => $this->type('App', 'A', parent: 'B', traits: ['App\T']),
            'app/B.php' => $this->type('App', 'B', members: ['helper']),
            'app/T.php' => $this->trait('App', 'T', ['helper']),
        ], 'App\A', 'helper');

        self::assertInstanceOf(AncestorDeclaration::class, $found);
        self::assertSame('App\T', $found->declaringClass);
    }

    // ---------------------------------------------------------------- S2: fail-closed boundaries

    public function testTheWalkStopsAtADependencyParentWithoutOpeningIt(): void
    {
        // H.15 / E5.12's shape: `MenuPdfTest extends Tests\TestCase extends Illuminate\...\TestCase`.
        $files = [
            'tests/Feature/MenuPdfTest.php' => $this->type('Tests\Feature', 'MenuPdfTest', parent: 'Tests\TestCase'),
            'tests/TestCase.php' => $this->type('Tests', 'TestCase', parent: 'Illuminate\Foundation\Testing\TestCase'),
            'vendor/laravel/framework/src/Illuminate/Foundation/Testing/TestCase.php' => $this->type('Illuminate\Foundation\Testing', 'TestCase', members: ['getJson']),
        ];

        $found = $this->walk($files, 'Tests\Feature\MenuPdfTest', 'getJson');

        self::assertInstanceOf(AncestryBoundary::class, $found);
        self::assertSame(AncestryBoundary::DEPENDENCY, $found->reason);
        self::assertSame('Illuminate\Foundation\Testing\TestCase', $found->at, 'named by FQCN from the extends clause alone');
        self::assertSame(['Tests\TestCase'], $found->walked, 'the project ancestor was walked; the dependency was not');
    }

    public function testTheWalkStopsAtADependencyTraitEvenWhenAProjectParentMightDeclareTheMember(): void
    {
        // A trait member overrides an inherited one; which wins cannot be known without opening
        // the dependency. Fail-closed.
        $found = $this->walk([
            'app/A.php' => $this->type('App', 'A', parent: 'B', traits: ['Illuminate\Foundation\Auth\Access\AuthorizesRequests']),
            'app/B.php' => $this->type('App', 'B', members: ['authorize']),
            'vendor/laravel/framework/src/Illuminate/Foundation/Auth/Access/AuthorizesRequests.php' => $this->trait('Illuminate\Foundation\Auth\Access', 'AuthorizesRequests', ['authorize']),
        ], 'App\A', 'authorize');

        self::assertInstanceOf(AncestryBoundary::class, $found);
        self::assertSame(AncestryBoundary::DEPENDENCY, $found->reason);
        self::assertSame('Illuminate\Foundation\Auth\Access\AuthorizesRequests', $found->at);
    }

    public function testAParentTheMapCannotPlaceIsABoundaryNeverS1(): void
    {
        $found = $this->walk([
            'app/A.php' => $this->type('App', 'A', parent: 'Missing'),
        ], 'App\A', 'helper');

        self::assertInstanceOf(AncestryBoundary::class, $found);
        self::assertSame(AncestryBoundary::UNPLACEABLE, $found->reason);
        self::assertSame('App\Missing', $found->at, 'qualified through the file namespace, as the language would');
    }

    public function testAnUnreadableFileOnTheChainIsABoundary(): void
    {
        $locator = new FakeClassLocator(['App\B' => 'app/B.php']);
        $source = new FakeSourceRepository(['app/A.php' => $this->type('App', 'A', parent: 'B')]);

        $found = (new AncestryResolver($locator, $source, new TokenizerMemberSlicer()))->declarationOf('App\A', 'app/A.php', 'helper');

        self::assertInstanceOf(AncestryBoundary::class, $found);
        self::assertSame(AncestryBoundary::UNREADABLE, $found->reason);
        self::assertSame('app/B.php', $found->at);
    }

    public function testTwoTraitsOfOneClassBothDeclaringTheMemberIsABoundary(): void
    {
        // PHP settles this with `insteadof`; reproducing that is inference (P6).
        $found = $this->walk([
            'app/A.php' => $this->type('App', 'A', traits: ['App\T1', 'App\T2']),
            'app/T1.php' => $this->trait('App', 'T1', ['helper']),
            'app/T2.php' => $this->trait('App', 'T2', ['helper']),
        ], 'App\A', 'helper');

        self::assertInstanceOf(AncestryBoundary::class, $found);
        self::assertSame(AncestryBoundary::AMBIGUOUS, $found->reason);
        self::assertSame('App\A', $found->at);
    }

    public function testATraitUseWithAConflictBlockIsABoundary(): void
    {
        $found = $this->walk([
            'app/A.php' => "<?php\n\nnamespace App;\n\nclass A\n{\n    use T1, T2 {\n        T1::helper insteadof T2;\n    }\n}\n",
            'app/T1.php' => $this->trait('App', 'T1', ['helper']),
            'app/T2.php' => $this->trait('App', 'T2', ['helper']),
        ], 'App\A', 'helper');

        self::assertInstanceOf(AncestryBoundary::class, $found);
        self::assertSame(AncestryBoundary::CONFLICT_BLOCK, $found->reason);
    }

    public function testACycleStopsTheWalk(): void
    {
        // Impossible in valid PHP, possible in a malformed tree: a visited type is a boundary.
        $found = $this->walk([
            'app/A.php' => $this->type('App', 'A', parent: 'B'),
            'app/B.php' => $this->type('App', 'B', parent: 'A'),
        ], 'App\A', 'helper');

        self::assertInstanceOf(AncestryBoundary::class, $found);
        self::assertSame(AncestryBoundary::CYCLE, $found->reason);
        self::assertSame('App\A', $found->at);
    }

    // ---------------------------------------------------------------- null: the existing flag is true

    public function testAncestryThatEndsInProjectCodeWithoutTheMemberIsNull(): void
    {
        // Walked to the end; nowhere declares it. The existing `unresolved-reference` flag is
        // exactly true and the pipeline keeps it.
        self::assertNull($this->walk([
            'app/A.php' => $this->type('App', 'A', parent: 'B', traits: ['App\T']),
            'app/B.php' => $this->type('App', 'B', members: ['other']),
            'app/T.php' => $this->trait('App', 'T', ['another']),
        ], 'App\A', 'helper'));
    }

    public function testAClassWithNoAncestryIsNull(): void
    {
        self::assertNull($this->walk(['app/A.php' => $this->type('App', 'A')], 'App\A', 'helper'));
    }

    public function testAnUnreadableOriginIsNull(): void
    {
        self::assertNull($this->walk([], 'App\A', 'helper'));
    }

    public function testInterfacesAreNotWalked(): void
    {
        // `implements` carries no body to cite; an interface method is not a declaration here.
        self::assertNull($this->walk([
            'app/A.php' => "<?php\n\nnamespace App;\n\nclass A implements Contract\n{\n}\n",
            'app/Contract.php' => "<?php\n\nnamespace App;\n\ninterface Contract\n{\n    public function helper(): void;\n}\n",
        ], 'App\A', 'helper'));
    }

    // ---------------------------------------------------------------- name resolution is the language's

    public function testNamesAreQualifiedThroughImportsAliasesAndTheNamespace(): void
    {
        $found = $this->walk([
            'app/Http/A.php' => "<?php\n\nnamespace App\\Http;\n\nuse App\\Support\\Base as Parent_;\n\nclass A extends Parent_\n{\n    use \\App\\Traits\\T;\n}\n",
            'app/Support/Base.php' => $this->type('App\Support', 'Base', members: ['fromParent']),
            'app/Traits/T.php' => $this->trait('App\Traits', 'T', ['fromTrait']),
        ], 'App\Http\A', 'fromParent');

        self::assertInstanceOf(AncestorDeclaration::class, $found);
        self::assertSame('App\Support\Base', $found->declaringClass, 'the alias resolved through the import');
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @param array<string, string> $files
     */
    private function walk(array $files, string $callingClass, string $member): AncestorDeclaration|AncestryBoundary|null
    {
        $map = [];

        foreach ($files as $path => $text) {
            if (preg_match('/namespace ([^;]+);/', $text, $ns) && preg_match('/(?:class|trait|interface) (\w+)/', $text, $name)) {
                $map[$ns[1] . '\\' . $name[1]] = $path;
            }
        }

        return (new AncestryResolver(new FakeClassLocator($map), new FakeSourceRepository($files), new TokenizerMemberSlicer()))
            ->declarationOf($callingClass, array_key_first($files) ?? 'app/Missing.php', $member);
    }

    /**
     * @param list<string> $traits
     * @param list<string> $members
     */
    private function type(string $namespace, string $name, ?string $parent = null, array $traits = [], array $members = []): string
    {
        $body = '';

        if ($traits !== []) {
            $body .= '    use ' . implode(', ', array_map(static fn (string $t): string => '\\' . $t, $traits)) . ";\n";
        }

        foreach ($members as $member) {
            $body .= "\n    public function {$member}(): void\n    {\n    }\n";
        }

        // A qualified parent is written fully qualified, as real code would import or prefix it.
        $extends = $parent === null ? '' : ' extends ' . (str_contains($parent, '\\') ? '\\' . $parent : $parent);

        return "<?php\n\nnamespace {$namespace};\n\nclass {$name}{$extends}\n{\n{$body}}\n";
    }

    /**
     * @param list<string> $members
     */
    private function trait(string $namespace, string $name, array $members): string
    {
        $body = '';

        foreach ($members as $member) {
            $body .= "    protected function {$member}(): void\n    {\n    }\n";
        }

        return "<?php\n\nnamespace {$namespace};\n\ntrait {$name}\n{\n{$body}}\n";
    }
}
