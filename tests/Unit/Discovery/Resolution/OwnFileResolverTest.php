<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Resolution;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Discovery\Resolution\OwnFileResolver;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OwnFileResolver::class)]
final class OwnFileResolverTest extends TestCase
{
    private const PATH = 'app/Services/Plaid/PlaidAccountService.php';

    public function testTheEnclosingMemberIsFoundEvenWhenTheHunkStartsAboveIt(): void
    {
        // A real `git diff` carries three lines of leading context, so a hunk routinely starts on
        // the class's opening brace. Reading only the region's first line would find no member.
        $slices = $this->resolver()->resolve(
            new Assertion(
                AssertionKind::SameFileSymbolAbsence,
                'Log',
                self::PATH,
                new ChangedRegion(10, 16, [], []),
                'a claim',
            )
        );

        self::assertCount(2, $slices);
        self::assertSame('syncFromResponse', $slices[1]->member);
    }

    public function testAnAbsenceResolvesToTheUseBlockAndTheEnclosingMember(): void
    {
        // Experiment 1's minimum context: "the `use` block plus the full syncFromResponse body".
        $slices = $this->resolver()->resolve($this->assertion(AssertionKind::SameFileSymbolAbsence, 'Log'));

        self::assertCount(2, $slices);

        self::assertNull($slices[0]->member);
        self::assertStringContainsString('use App\Models\PlaidAccount;', $slices[0]->text);
        self::assertStringNotContainsString('class PlaidAccountService', $slices[0]->text);

        self::assertSame('syncFromResponse', $slices[1]->member);
        self::assertStringContainsString('upsertFromPlaid($item, $accounts)', $slices[1]->text);
    }

    public function testASameFileReferenceResolvesToTheNamedSibling(): void
    {
        $slices = $this->resolver()->resolve(
            $this->assertion(AssertionKind::SameFileReference, 'upsertFromPlaid')
        );

        self::assertCount(1, $slices);
        self::assertSame('upsertFromPlaid', $slices[0]->member);
        self::assertSame(17, $slices[0]->firstLine);
        self::assertSame(19, $slices[0]->lastLine);
    }

    public function testEverySliceCarriesThePathTheSlicerCouldNotKnow(): void
    {
        foreach ($this->resolver()->resolve($this->assertion(AssertionKind::SameFileSymbolAbsence, 'Log')) as $slice) {
            self::assertSame(self::PATH, $slice->path);
        }
    }

    public function testNothingElseFromTheChangedFileIsEmitted(): void
    {
        // ADR-A005: the full text is an analysis input, never payload. A whole-file slice would
        // fail Experiment 2's near-empty expectation by construction.
        $slices = $this->resolver()->resolve($this->assertion(AssertionKind::SameFileSymbolAbsence, 'Log'));

        foreach ($slices as $slice) {
            self::assertNotSame($this->source(), $slice->text);
            self::assertStringNotContainsString('declare(strict_types=1);', $slice->text);
        }
    }

    public function testAnUnreadableFileResolvesToNothingSoTheCallerFlagsIt(): void
    {
        $resolver = new OwnFileResolver(new FakeSourceRepository(), new TokenizerMemberSlicer());

        self::assertSame([], $resolver->resolve($this->assertion(AssertionKind::SameFileSymbolAbsence, 'Log')));
    }

    public function testASiblingThatCannotBeSlicedResolvesToNothing(): void
    {
        self::assertSame(
            [],
            $this->resolver()->resolve($this->assertion(AssertionKind::SameFileReference, 'noSuchMember'))
        );
    }

    public function testResolvingTwiceGivesTheSameSlices(): void
    {
        $assertion = $this->assertion(AssertionKind::SameFileSymbolAbsence, 'Log');

        self::assertEquals(
            $this->resolver()->resolve($assertion),
            $this->resolver()->resolve($assertion)
        );
    }

    private function resolver(): OwnFileResolver
    {
        return new OwnFileResolver(
            new FakeSourceRepository([self::PATH => $this->source()]),
            new TokenizerMemberSlicer(),
        );
    }

    private function assertion(AssertionKind $kind, string $subject): Assertion
    {
        return new Assertion($kind, $subject, self::PATH, new ChangedRegion(12, 15, [], []), 'a claim');
    }

    private function source(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Plaid;

use App\Models\PlaidAccount;
use App\Repositories\PlaidAccountRepository;

class PlaidAccountService
{
    public function syncFromResponse(PlaidItem $item, array $accounts): void
    {
        $this->upsertFromPlaid($item, $accounts);
    }

    public function upsertFromPlaid(PlaidItem $item, array $accounts): void
    {
    }
}
PHP;
    }
}
