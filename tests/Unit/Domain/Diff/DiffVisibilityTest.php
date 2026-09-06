<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Domain\Diff;

use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Domain\Diff\Diff;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * M12 · `Diff::showsEntirely()` — is this span already in front of the reader? (ADR-A019)
 *
 * The whole safety of the milestone rests here. A fetch is suppressed only when the diff shows the
 * declaration **in full**, and the two ways that can be true are different evidence: a created file
 * shows everything, a modified file shows only its hunks.
 *
 * The row this file exists to protect is `testAPathAppearingInTheDiffIsNeverEnoughOnItsOwn`: a
 * modified file whose declaration sits away from the hunk. A rule keyed on "the path appears in the
 * diff" would answer true there and delete context the reviewer never saw.
 */
final class DiffVisibilityTest extends TestCase
{
    // ---------------------------------------------------------------- a created file shows everything

    public function testACreatedFileShowsEveryLineItHas(): void
    {
        $diff = $this->diff([new ChangedFile('app/New.php', [$this->region(1, 13)], [], true)]);

        self::assertTrue($diff->showsEntirely('app/New.php', 1, 13), 'the whole file');
        self::assertTrue($diff->showsEntirely('app/New.php', 5, 9), 'a member inside it');
        self::assertTrue(
            $diff->showsEntirely('app/New.php', 400, 500),
            'and any span at all — every line of a created file is an added line, so region '
                . 'bookkeeping is not what makes it visible'
        );
    }

    // ---------------------------------------------------------------- a modified file shows its hunks

    #[DataProvider('spansAgainstAHunk')]
    public function testAModifiedFileShowsOnlyWhatItsRegionsContain(
        string $label,
        int $first,
        int $last,
        bool $expected,
    ): void {
        $diff = $this->diff([new ChangedFile('app/Mod.php', [$this->region(11, 16)], [], false)]);

        self::assertSame($expected, $diff->showsEntirely('app/Mod.php', $first, $last), $label);
    }

    /**
     * @return iterable<string, array{string, int, int, bool}>
     */
    public static function spansAgainstAHunk(): iterable
    {
        yield 'wholly inside' => ['12-15 inside 11-16', 12, 15, true];
        yield 'exactly the region' => ['11-16', 11, 16, true];
        yield 'wholly before' => ['5-9, the M12.3 case', 5, 9, false];
        yield 'wholly after' => ['20-24', 20, 24, false];
        yield 'overlapping the start' => ['9-12 — half shown is not shown', 9, 12, false];
        yield 'overlapping the end' => ['15-20 — half shown is not shown', 15, 20, false];
        yield 'straddling the region' => ['10-17 — larger than the hunk', 10, 17, false];
    }

    public function testTheDeclarationMustBeContainedByOneRegionNotSpreadAcrossTwo(): void
    {
        $diff = $this->diff([
            new ChangedFile('app/Mod.php', [$this->region(10, 12), $this->region(14, 16)], [], false),
        ]);

        self::assertFalse(
            $diff->showsEntirely('app/Mod.php', 10, 16),
            'line 13 is shown by neither hunk, so the span is not shown in full'
        );
        self::assertTrue($diff->showsEntirely('app/Mod.php', 14, 16), 'but the second hunk alone is');
    }

    // ---------------------------------------------------------------- the safety boundary

    public function testAPathAppearingInTheDiffIsNeverEnoughOnItsOwn(): void
    {
        $diff = $this->diff([new ChangedFile('app/Mod.php', [$this->region(26, 31)], [], false)]);

        self::assertFalse(
            $diff->showsEntirely('app/Mod.php', 7, 10),
            'M12.3 — the file is changed, the declaration is not. Suppressing here would lose real '
                . 'context, which is the one failure this rule must never produce'
        );
    }

    public function testAFileTheDiffDoesNotTouchShowsNothing(): void
    {
        $diff = $this->diff([new ChangedFile('app/Other.php', [$this->region(1, 99)], [], true)]);

        self::assertFalse($diff->showsEntirely('app/Untouched.php', 1, 2));
    }

    public function testAnEmptyDiffShowsNothing(): void
    {
        self::assertFalse($this->diff([])->showsEntirely('app/Anything.php', 1, 2));
    }

    public function testAnEmptySpanIsNeverConsideredShown(): void
    {
        // A deleted file parses with lastLine < firstLine. Nothing is being asked for, so nothing is
        // answered — and certainly not "yes".
        $diff = $this->diff([new ChangedFile('app/Gone.php', [new ChangedRegion(0, -1, [], ['x'])], [], false)]);

        self::assertFalse($diff->showsEntirely('app/Gone.php', 0, -1));
    }

    public function testTheAnswerIsStable(): void
    {
        $diff = $this->diff([new ChangedFile('app/Mod.php', [$this->region(11, 16)], [], false)]);

        self::assertSame(
            $diff->showsEntirely('app/Mod.php', 12, 15),
            $diff->showsEntirely('app/Mod.php', 12, 15),
            'P8'
        );
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @param list<ChangedFile> $files
     */
    private function diff(array $files): Diff
    {
        return new Diff($files);
    }

    private function region(int $first, int $last): ChangedRegion
    {
        return new ChangedRegion($first, $last, ['x'], []);
    }
}
