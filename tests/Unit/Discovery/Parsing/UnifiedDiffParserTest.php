<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Parsing;

use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnifiedDiffParser::class)]
final class UnifiedDiffParserTest extends TestCase
{
    private UnifiedDiffParser $parser;

    protected function setUp(): void
    {
        $this->parser = new UnifiedDiffParser();
    }

    public function testParsesMultipleFilesAndMultipleHunks(): void
    {
        $diff = $this->diff([
            'diff --git a/app/Services/Plaid/PlaidAccountService.php b/app/Services/Plaid/PlaidAccountService.php',
            'index 1111111..2222222 100644',
            '--- a/app/Services/Plaid/PlaidAccountService.php',
            '+++ b/app/Services/Plaid/PlaidAccountService.php',
            '@@ -10,3 +10,4 @@',
            ' $first = 1;',
            '-$removed = 2;',
            '+$added = 2;',
            '+$alsoAdded = 3;',
            ' $last = 4;',
            '@@ -40,2 +41,2 @@',
            '-$old = 5;',
            '+$new = 5;',
            ' $tail = 6;',
            'diff --git a/routes/api.php b/routes/api.php',
            '--- a/routes/api.php',
            '+++ b/routes/api.php',
            '@@ -1,1 +1,2 @@',
            ' <?php',
            "+Route::post('reauth-link-token', [PlaidController::class, 'reauth']);",
        ]);

        $result = $this->parser->parse($diff);

        self::assertCount(2, $result->files);
        self::assertSame('app/Services/Plaid/PlaidAccountService.php', $result->files[0]->path);
        self::assertSame('routes/api.php', $result->files[1]->path);

        self::assertCount(2, $result->files[0]->regions);
        self::assertSame(['$added = 2;', '$alsoAdded = 3;'], $result->files[0]->regions[0]->addedLines);
        self::assertSame(['$removed = 2;'], $result->files[0]->regions[0]->removedLines);
        self::assertSame(['$new = 5;'], $result->files[0]->regions[1]->addedLines);

        self::assertCount(1, $result->files[1]->regions);
    }

    public function testRegionSpanComesFromTheHunkHeadersNewSide(): void
    {
        $diff = $this->diff([
            '--- a/app/Foo.php',
            '+++ b/app/Foo.php',
            '@@ -10,7 +12,9 @@',
            ' a',
            ' b',
            ' c',
            ' d',
            ' e',
            ' f',
            '+g',
            '+h',
            ' i',
        ]);

        $region = $this->parser->parse($diff)->files[0]->regions[0];

        self::assertSame(12, $region->firstLine);
        self::assertSame(20, $region->lastLine);
    }

    public function testAHunkHeaderWithoutCountsMeansOneLineEachSide(): void
    {
        $diff = $this->diff([
            '--- a/app/Foo.php',
            '+++ b/app/Foo.php',
            '@@ -3 +3 @@',
            '-old',
            '+new',
        ]);

        $region = $this->parser->parse($diff)->files[0]->regions[0];

        self::assertSame(3, $region->firstLine);
        self::assertSame(3, $region->lastLine);
        self::assertSame(['new'], $region->addedLines);
        self::assertSame(['old'], $region->removedLines);
    }

    public function testANewFileTakesTheNewPath(): void
    {
        $diff = $this->diff([
            'diff --git a/app/Enums/PlaidItemStatus.php b/app/Enums/PlaidItemStatus.php',
            'new file mode 100644',
            '--- /dev/null',
            '+++ b/app/Enums/PlaidItemStatus.php',
            '@@ -0,0 +1,2 @@',
            '+<?php',
            '+enum PlaidItemStatus: string {}',
        ]);

        $result = $this->parser->parse($diff);

        self::assertSame('app/Enums/PlaidItemStatus.php', $result->files[0]->path);
        self::assertSame(1, $result->files[0]->regions[0]->firstLine);
        self::assertSame(2, $result->files[0]->regions[0]->lastLine);
    }

    public function testADeletedFileFallsBackToTheOldPathAndHasAnEmptySpan(): void
    {
        $diff = $this->diff([
            'diff --git a/app/Legacy.php b/app/Legacy.php',
            'deleted file mode 100644',
            '--- a/app/Legacy.php',
            '+++ /dev/null',
            '@@ -1,2 +0,0 @@',
            '-<?php',
            '-class Legacy {}',
        ]);

        $result = $this->parser->parse($diff);
        $region = $result->files[0]->regions[0];

        self::assertSame('app/Legacy.php', $result->files[0]->path);
        self::assertSame([], $region->addedLines);
        self::assertSame(['<?php', 'class Legacy {}'], $region->removedLines);
        self::assertLessThan(
            $region->firstLine,
            $region->lastLine,
            'A pure deletion occupies no lines in the current file, so the span is empty.'
        );
    }

    public function testAPureContextHunkYieldsARegionWithNoChanges(): void
    {
        $diff = $this->diff([
            '--- a/app/Foo.php',
            '+++ b/app/Foo.php',
            '@@ -1,2 +1,2 @@',
            ' untouched',
            ' also untouched',
        ]);

        $region = $this->parser->parse($diff)->files[0]->regions[0];

        self::assertSame([], $region->addedLines);
        self::assertSame([], $region->removedLines);
    }

    public function testAnEmptyContextLineDoesNotDesynchroniseTheCounts(): void
    {
        // Some transports strip the trailing space from an empty context line.
        $diff = $this->diff([
            '--- a/app/Foo.php',
            '+++ b/app/Foo.php',
            '@@ -1,3 +1,3 @@',
            ' first',
            '',
            '-old',
            '+new',
        ]);

        $region = $this->parser->parse($diff)->files[0]->regions[0];

        self::assertSame(['new'], $region->addedLines);
        self::assertSame(['old'], $region->removedLines);
    }

    public function testALineInsideAHunkThatLooksLikeAHeaderStaysInTheHunk(): void
    {
        $diff = $this->diff([
            '--- a/docs/example.md',
            '+++ b/docs/example.md',
            '@@ -1,3 +1,3 @@',
            '---- a heading underline',
            '+@@ not a hunk header',
            ' tail',
        ]);

        $region = $this->parser->parse($diff)->files[0]->regions[0];

        self::assertSame(['@@ not a hunk header'], $region->addedLines);
        self::assertSame(['--- a heading underline'], $region->removedLines);
    }

    public function testAChangedSignatureCarriesBothItsOldAndNewForm(): void
    {
        $diff = $this->diff([
            '--- a/app/Repositories/PlaidItemRepository.php',
            '+++ b/app/Repositories/PlaidItemRepository.php',
            '@@ -20,3 +20,3 @@',
            '-    public function reactivate(PlaidItem $item, array $data): void',
            '+    public function reactivate(PlaidItem $item, string $accessToken): void',
            '     {',
        ]);

        $members = $this->parser->parse($diff)->files[0]->members;

        self::assertCount(1, $members);
        self::assertSame('reactivate', $members[0]->name);
        self::assertSame(
            'public function reactivate(PlaidItem $item, array $data): void',
            $members[0]->oldSignature
        );
        self::assertSame(
            'public function reactivate(PlaidItem $item, string $accessToken): void',
            $members[0]->newSignature
        );
    }

    public function testAWrappedParameterListIsJoinedUntilTheParenthesesBalance(): void
    {
        $diff = $this->diff([
            '--- a/app/Repositories/PlaidItemRepository.php',
            '+++ b/app/Repositories/PlaidItemRepository.php',
            '@@ -20,1 +20,5 @@',
            '-    public function reactivate(PlaidItem $item, array $data): void',
            '+    public function reactivate(',
            '+        PlaidItem $item,',
            '+        string $accessToken,',
            '+        ?string $institutionId = null,',
            '+    ): void',
        ]);

        $members = $this->parser->parse($diff)->files[0]->members;

        self::assertSame(
            'public function reactivate( PlaidItem $item, string $accessToken, '
            . '?string $institutionId = null, ): void',
            $members[0]->newSignature
        );
    }

    public function testAnAddedMemberHasNoOldSignatureAndARenameIsTwoMembers(): void
    {
        $diff = $this->diff([
            '--- a/app/Services/Thing.php',
            '+++ b/app/Services/Thing.php',
            '@@ -5,1 +5,1 @@',
            '-    public function oldName(int $a): void',
            '+    public function newName(int $a): void',
        ]);

        $members = $this->parser->parse($diff)->files[0]->members;

        self::assertCount(2, $members, 'A rename is two members and is not tracked.');

        self::assertSame('oldName', $members[0]->name);
        self::assertNotNull($members[0]->oldSignature);
        self::assertNull($members[0]->newSignature);

        self::assertSame('newName', $members[1]->name);
        self::assertNull($members[1]->oldSignature);
        self::assertNotNull($members[1]->newSignature);
    }

    public function testTheWordFunctionOutsideADeclarationIsNotAMember(): void
    {
        $diff = $this->diff([
            '--- a/app/Services/Thing.php',
            '+++ b/app/Services/Thing.php',
            '@@ -1,0 +1,6 @@',
            '+use function array_map;',
            '+// this function is only a comment',
            "+\$note = 'a function in a string';",
            '+$closure = function (int $a) { return $a; };',
            '+$arrow = fn (int $a) => $a;',
            '+    public function theOnlyRealOne(): void',
        ]);

        $members = $this->parser->parse($diff)->files[0]->members;

        self::assertCount(1, $members);
        self::assertSame('theOnlyRealOne', $members[0]->name);
    }

    public function testAnAbstractOrInterfaceDeclarationStopsAtTheSemicolon(): void
    {
        $diff = $this->diff([
            '--- a/app/Contracts/Thing.php',
            '+++ b/app/Contracts/Thing.php',
            '@@ -3,0 +3,1 @@',
            '+    public function handle(int $a): bool;',
        ]);

        $members = $this->parser->parse($diff)->files[0]->members;

        self::assertSame('public function handle(int $a): bool', $members[0]->newSignature);
    }

    public function testANonPhpFileParsesButYieldsNoMembers(): void
    {
        $diff = $this->diff([
            '--- a/resources/js/app.js',
            '+++ b/resources/js/app.js',
            '@@ -1,0 +1,1 @@',
            '+function notAPhpMember(a) { return a; }',
        ]);

        $file = $this->parser->parse($diff)->files[0];

        self::assertSame('resources/js/app.js', $file->path);
        self::assertCount(1, $file->regions);
        self::assertSame([], $file->members);
    }

    public function testCrlfInputParsesIdenticallyToLfInput(): void
    {
        $lines = [
            '--- a/app/Foo.php',
            '+++ b/app/Foo.php',
            '@@ -1,1 +1,1 @@',
            '-    public function go(int $a): void',
            '+    public function go(string $a): void',
        ];

        self::assertEquals(
            $this->parser->parse(implode("\n", $lines) . "\n"),
            $this->parser->parse(implode("\r\n", $lines) . "\r\n")
        );
    }

    public function testNoNewlineMarkersBelongToNeitherSide(): void
    {
        $diff = $this->diff([
            '--- a/app/Foo.php',
            '+++ b/app/Foo.php',
            '@@ -1,1 +1,1 @@',
            '-old',
            '\\ No newline at end of file',
            '+new',
            '\\ No newline at end of file',
        ]);

        $region = $this->parser->parse($diff)->files[0]->regions[0];

        self::assertSame(['new'], $region->addedLines);
        self::assertSame(['old'], $region->removedLines);
    }

    #[DataProvider('structuralButContentlessDiffs')]
    public function testAChangeThisToolCannotReadIsNotAnError(string $diff): void
    {
        self::assertSame([], $this->parser->parse($diff)->files);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function structuralButContentlessDiffs(): iterable
    {
        yield 'binary file' => [
            "diff --git a/logo.png b/logo.png\n"
            . "index 1111111..2222222 100644\n"
            . "Binary files a/logo.png and b/logo.png differ\n",
        ];

        yield 'mode change only' => [
            "diff --git a/deploy.sh b/deploy.sh\nold mode 100644\nnew mode 100755\n",
        ];
    }

    #[DataProvider('emptyInputs')]
    public function testAnEmptyDiffIsAnEmptyResultNotAFailure(string $diff): void
    {
        self::assertSame([], $this->parser->parse($diff)->files);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyInputs(): iterable
    {
        yield 'empty string' => [''];
        yield 'newlines only' => ["\n\n"];
        yield 'whitespace only' => ["   \n\t\n"];
    }

    #[DataProvider('malformedInputs')]
    public function testMalformedInputIsRejectedRatherThanPartiallyParsed(string $diff): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Malformed diff');

        $this->parser->parse($diff);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedInputs(): iterable
    {
        yield 'not a diff at all' => ["hello world\nthis is prose\n"];

        yield 'hunk before any file header' => ["@@ -1,1 +1,1 @@\n-old\n+new\n"];

        yield 'unreadable hunk header' => [
            "--- a/app/Foo.php\n+++ b/app/Foo.php\n@@ nonsense @@\n",
        ];

        yield 'hunk truncated before its counts are satisfied' => [
            "--- a/app/Foo.php\n+++ b/app/Foo.php\n@@ -1,3 +1,3 @@\n one\n",
        ];

        yield 'unreadable line inside a hunk' => [
            "--- a/app/Foo.php\n+++ b/app/Foo.php\n@@ -1,1 +1,1 @@\n?corrupt\n",
        ];

        yield 'more lines than the header declares' => [
            "--- a/app/Foo.php\n+++ b/app/Foo.php\n@@ -1,1 +1,1 @@\n-a\n-b\n+c\n",
        ];
    }

    public function testParsingTheSameTextTwiceProducesTheSameDiff(): void
    {
        $diff = $this->diff([
            'diff --git a/app/Foo.php b/app/Foo.php',
            '--- a/app/Foo.php',
            '+++ b/app/Foo.php',
            '@@ -1,2 +1,3 @@',
            ' <?php',
            '+    public function added(): void',
            ' // tail',
        ]);

        self::assertEquals($this->parser->parse($diff), $this->parser->parse($diff));
    }

    public function testAFileWithoutAGitHeaderIsStillParsed(): void
    {
        // Plain `diff -u` output carries no "diff --git" line.
        $diff = $this->diff([
            "--- app/One.php\t2026-08-05 10:00:00",
            "+++ app/One.php\t2026-08-05 10:01:00",
            '@@ -1,1 +1,1 @@',
            '-a',
            '+b',
            '--- app/Two.php',
            '+++ app/Two.php',
            '@@ -1,1 +1,1 @@',
            '-c',
            '+d',
        ]);

        $result = $this->parser->parse($diff);

        self::assertCount(2, $result->files);
        self::assertSame('app/One.php', $result->files[0]->path);
        self::assertSame('app/Two.php', $result->files[1]->path);
    }

    /**
     * @param list<string> $lines
     */
    private function diff(array $lines): string
    {
        return implode("\n", $lines) . "\n";
    }
}
