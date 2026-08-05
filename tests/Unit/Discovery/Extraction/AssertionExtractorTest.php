<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Extraction;

use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Extraction\RegionAssertionExtractor;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Domain\Diff\Diff;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssertionExtractor::class)]
final class AssertionExtractorTest extends TestCase
{
    public function testEveryExtractorRunsOverEveryRegionInTheOrderWiringGaveThem(): void
    {
        $extractor = new AssertionExtractor([
            $this->extractorEmitting(AssertionKind::SameFileSymbolAbsence, 'first'),
            $this->extractorEmitting(AssertionKind::SameFileReference, 'second'),
        ]);

        $assertions = $extractor->extract($this->diff(2), $this->source());

        self::assertSame(
            ['first', 'second', 'first', 'second'],
            array_map(static fn (Assertion $a): string => $a->subject, $assertions),
            'extractor order is fixed, and both run over both regions'
        );
    }

    public function testTheSameAssertionFromTwoExtractorsIsReportedOnce(): void
    {
        $extractor = new AssertionExtractor([
            $this->extractorEmitting(AssertionKind::SameFileReference, 'upsertFromPlaid'),
            $this->extractorEmitting(AssertionKind::SameFileReference, 'upsertFromPlaid'),
        ]);

        self::assertCount(1, $extractor->extract($this->diff(1), $this->source()));
    }

    public function testTheSameSubjectInADifferentRegionIsADistinctAssertion(): void
    {
        $extractor = new AssertionExtractor([
            $this->extractorEmitting(AssertionKind::SameFileReference, 'upsertFromPlaid'),
        ]);

        self::assertCount(2, $extractor->extract($this->diff(2), $this->source()));
    }

    public function testAFileWhoseTextCannotBeReadYieldsNothing(): void
    {
        // A deleted file has no current text; that is correct, not missing. The pipeline reports
        // the unreadable path on stderr.
        $extractor = new AssertionExtractor([
            $this->extractorEmitting(AssertionKind::SameFileSymbolAbsence, 'Log'),
        ]);

        self::assertSame([], $extractor->extract($this->diff(1), new FakeSourceRepository()));
    }

    public function testNoExtractorsMeansNoAssertions(): void
    {
        self::assertSame([], (new AssertionExtractor([]))->extract($this->diff(1), $this->source()));
    }

    public function testExtractingTwiceGivesTheSameList(): void
    {
        $extractor = new AssertionExtractor([
            $this->extractorEmitting(AssertionKind::SameFileSymbolAbsence, 'Log'),
        ]);

        self::assertEquals(
            $extractor->extract($this->diff(2), $this->source()),
            $extractor->extract($this->diff(2), $this->source())
        );
    }

    private function extractorEmitting(AssertionKind $kind, string $subject): RegionAssertionExtractor
    {
        return new class ($kind, $subject) implements RegionAssertionExtractor {
            public function __construct(
                private readonly AssertionKind $kind,
                private readonly string $subject,
            ) {
            }

            /**
             * @return list<Assertion>
             */
            public function forRegion(ChangedFile $file, ChangedRegion $region, string $fileText): array
            {
                return [new Assertion($this->kind, $this->subject, $file->path, $region, 'a claim')];
            }
        };
    }

    private function diff(int $regions): Diff
    {
        $changedRegions = [];

        for ($i = 0; $i < $regions; $i++) {
            $changedRegions[] = new ChangedRegion(10 + ($i * 20), 15 + ($i * 20), ['+x'], []);
        }

        return new Diff([new ChangedFile('app/One.php', $changedRegions, [])]);
    }

    private function source(): FakeSourceRepository
    {
        return new FakeSourceRepository(['app/One.php' => "<?php\nclass One {}\n"]);
    }
}
