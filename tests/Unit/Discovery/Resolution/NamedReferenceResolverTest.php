<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Resolution;

use ContextDiscovery\Discovery\Framework\LaravelFrameworkKnowledge;
use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Discovery\Resolution\NamedReferenceResolver;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Domain\Source\SourceSlice;
use ContextDiscovery\Ports\MemberSlicer;
use ContextDiscovery\Tests\Fakes\FakeClassLocator;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(NamedReferenceResolver::class)]
final class NamedReferenceResolverTest extends TestCase
{
    public function testANamedMemberResolvesToThatMemberAndOnlyThatMember(): void
    {
        $slices = $this->resolver()->resolve($this->assertion('App\Models\PlaidAccount::forItem'));

        self::assertCount(1, $slices);
        self::assertSame('app/Models/PlaidAccount.php', $slices[0]->path);
        self::assertSame('forItem', $slices[0]->member);
        self::assertStringContainsString('function forItem', $slices[0]->text);
        self::assertStringNotContainsString('function scopeActive', $slices[0]->text);
    }

    public function testABareClassResolvesToItsMemberSurface(): void
    {
        // Exp 4 calls the enum "one small class"; Exp 1 asks for the model surface.
        $slices = $this->resolver()->resolve($this->assertion('App\Enums\PlaidItemStatus'));

        self::assertSame(
            ['ACTIVE', 'REVOKED', 'isInactive'],
            array_map(static fn (SourceSlice $slice): ?string => $slice->member, $slices)
        );
    }

    public function testTheSurfaceIsMembersNotTheWholeFile(): void
    {
        // ADR-A005: the namespace, the use block and the class declaration stay out.
        foreach ($this->resolver()->resolve($this->assertion('App\Enums\PlaidItemStatus')) as $slice) {
            self::assertStringNotContainsString('namespace App\Enums;', $slice->text);
            self::assertStringNotContainsString('use App\Support\Describable;', $slice->text);
        }
    }

    #[DataProvider('unresolvableSubjects')]
    public function testAnythingUnresolvableReturnsNothingSoTheCallerFlagsIt(string $subject): void
    {
        self::assertSame([], $this->resolver()->resolve($this->assertion($subject)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unresolvableSubjects(): iterable
    {
        yield 'no PSR-4 entry' => ['Vendor\Absent\Thing::method'];
        yield 'mapped but the file is unreadable' => ['App\Models\Ghost::method'];
        yield 'located but the member is not there' => ['App\Models\PlaidAccount::noSuchMember'];
    }

    public function testAnAssertionOfAnotherKindIsNotThisResolversWork(): void
    {
        $assertion = new Assertion(
            AssertionKind::SameFileReference,
            'App\Models\PlaidAccount::forItem',
            'app/One.php',
            new ChangedRegion(1, 5, [], []),
            'a claim',
        );

        self::assertSame([], $this->resolver()->resolve($assertion));
    }

    // ------------------------------------------------------------ depth one

    public function testResolutionIsDepthOneAndProducesNoFurtherReferences(): void
    {
        // PlaidAccount's own file references PlaidItem and Carbon. Resolving PlaidAccount must
        // return slices from PlaidAccount and nothing about what it in turn names (P3, X2).
        $slices = $this->resolver()->resolve($this->assertion('App\Models\PlaidAccount'));

        self::assertNotSame([], $slices);

        foreach ($slices as $slice) {
            self::assertSame('app/Models/PlaidAccount.php', $slice->path);
        }
    }

    public function testTheResolvedFilesOwnUseBlockIsNeverRead(): void
    {
        // P4: no module reads a resolved file's imports. Forward-following is banned at the
        // architecture level, not merely omitted — so the port method is never called.
        $spy = new class (new TokenizerMemberSlicer()) implements MemberSlicer {
            public bool $useBlockWasRead = false;

            public function __construct(private readonly MemberSlicer $inner)
            {
            }

            public function useBlock(string $fileText): ?SourceSlice
            {
                $this->useBlockWasRead = true;

                return $this->inner->useBlock($fileText);
            }

            public function member(string $fileText, string $memberName): ?SourceSlice
            {
                return $this->inner->member($fileText, $memberName);
            }

            /**
             * @return list<string>
             */
            public function memberNames(string $fileText): array
            {
                return $this->inner->memberNames($fileText);
            }

            public function enclosingMemberName(string $fileText, int $line): ?string
            {
                return $this->inner->enclosingMemberName($fileText, $line);
            }
        };

        $resolver = new NamedReferenceResolver($this->locator(), $this->source(), $spy, new LaravelFrameworkKnowledge());

        $resolver->resolve($this->assertion('App\Models\PlaidAccount::forItem'));
        $resolver->resolve($this->assertion('App\Models\PlaidAccount'));

        self::assertFalse($spy->useBlockWasRead);
    }

    // ------------------------------------------------------------ subject encoding

    #[DataProvider('subjectsAndTheirParts')]
    public function testASubjectSplitsIntoAClassAndAnOptionalMember(
        string $subject,
        string $class,
        ?string $member,
    ): void {
        self::assertSame([$class, $member], NamedReferenceResolver::split($subject));
    }

    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function subjectsAndTheirParts(): iterable
    {
        yield 'bare class' => ['App\Models\PlaidAccount', 'App\Models\PlaidAccount', null];
        yield 'class and member' => [
            'App\Models\PlaidAccount::forItem',
            'App\Models\PlaidAccount',
            'forItem',
        ];
        yield 'a namespace separator is not a member separator' => [
            'App\Enums\PlaidItemStatus',
            'App\Enums\PlaidItemStatus',
            null,
        ];
    }

    public function testResolvingTwiceGivesTheSameSlices(): void
    {
        $assertion = $this->assertion('App\Models\PlaidAccount::forItem');

        self::assertEquals(
            $this->resolver()->resolve($assertion),
            $this->resolver()->resolve($assertion)
        );
    }

    // ------------------------------------------------------------ helpers

    private function resolver(): NamedReferenceResolver
    {
        return new NamedReferenceResolver($this->locator(), $this->source(), new TokenizerMemberSlicer(), new LaravelFrameworkKnowledge());
    }

    private function locator(): FakeClassLocator
    {
        return new FakeClassLocator([
            'App\Models\PlaidAccount' => 'app/Models/PlaidAccount.php',
            'App\Enums\PlaidItemStatus' => 'app/Enums/PlaidItemStatus.php',
            'App\Models\Ghost' => 'app/Models/Ghost.php',
        ]);
    }

    private function source(): FakeSourceRepository
    {
        return new FakeSourceRepository([
            'app/Models/PlaidAccount.php' => <<<'PHP'
<?php

namespace App\Models;

use App\Models\PlaidItem;
use Carbon\CarbonInterface;

class PlaidAccount
{
    protected $fillable = ['name', 'mask'];

    public function forItem(PlaidItem $item)
    {
        return static::query()->where('plaid_item_id', $item->id);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('official_name');
    }
}
PHP,
            'app/Enums/PlaidItemStatus.php' => <<<'PHP'
<?php

namespace App\Enums;

use App\Support\Describable;

enum PlaidItemStatus: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';

    public function isInactive(): bool
    {
        return $this === self::REVOKED;
    }
}
PHP,
        ]);
    }

    private function assertion(string $subject): Assertion
    {
        return new Assertion(
            AssertionKind::NamedReference,
            $subject,
            'app/Services/Plaid/PlaidAccountService.php',
            new ChangedRegion(12, 15, [], []),
            'a claim',
        );
    }
}
