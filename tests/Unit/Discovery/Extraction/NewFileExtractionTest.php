<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Extraction;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Extraction\NamedReferenceAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\OwnFileAssertionExtractor;
use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\TestCase;

/**
 * M11 · a created file is input, not context (ADR-A018).
 *
 * The own-file move was earned by Experiment 1's `upsertFromPlaid` — a sibling in a **modified**
 * file, whose body the diff did not show. When the diff *creates* the file, every line of it is an
 * added line: its `use` block, its enclosing member and its siblings are already in front of the
 * reviewer, and fetching them hands the diff back as context. ADR-A005 is explicit that the diff is
 * input and must not be duplicated into the bundle.
 *
 * Three facts are asserted here, because all three were wrong before:
 *
 * 1. a created file yields no own-file assertions, while its **external** references still do;
 * 2. a **modified** file is untouched — the narrowing is to new files only;
 * 3. `<?php` is not a class name. Tokenising `'<?php ' . $regionText` when the text already opens
 *    with `<?php` re-reads the literal as `<`, `?`, T_STRING(php), and the `?` then satisfies the
 *    `?Name` nullable-type rule.
 */
final class NewFileExtractionTest extends TestCase
{
    private const NEW_PATH = 'app/Services/NewService.php';

    private const OLD_PATH = 'app/Support/Existing.php';

    // ---------------------------------------------------------------- a created file

    public function testACreatedFileYieldsNoOwnFileAssertions(): void
    {
        $kinds = array_map(
            static fn ($a): string => $a->kind->value,
            $this->extract($this->newFileDiff())
        );

        self::assertNotContains(AssertionKind::SameFileSymbolAbsence->value, $kinds);
        self::assertNotContains(AssertionKind::SameFileReference->value, $kinds);
    }

    public function testACreatedFileStillYieldsItsExternalReferences(): void
    {
        // The point of the narrowing: a new file may certainly pull context, just not its own.
        $subjects = array_map(static fn ($a): string => $a->subject, $this->extract($this->newFileDiff()));

        self::assertContains('App\Models\Package', $subjects, 'the collaborator it names is still reached');
    }

    public function testTheOpenTagIsNeverAClassName(): void
    {
        foreach ($this->extract($this->newFileDiff()) as $assertion) {
            self::assertNotSame('php', $assertion->subject, 'the `<?php` open tag is not a symbol');
        }
    }

    /**
     * D1 at the token layer, independent of the new-file gate: even when the own-file extractor is
     * handed region text that opens with `<?php`, it must not read the tag as a name.
     */
    public function testTheOwnFileExtractorItselfIgnoresAnOpenTagInTheRegion(): void
    {
        $parser = new UnifiedDiffParser();
        $diff = $parser->parse($this->newFileDiffText());
        $file = $diff->files[0];
        $extractor = new OwnFileAssertionExtractor(new TokenizerMemberSlicer());

        foreach ($file->regions as $region) {
            foreach ($extractor->forRegion($file, $region, $this->newFileText()) as $assertion) {
                self::assertNotSame('php', $assertion->subject);
            }
        }
    }

    // ---------------------------------------------------------------- a modified file

    public function testAModifiedFileKeepsItsOwnFileAssertions(): void
    {
        $kinds = array_map(
            static fn ($a): string => $a->kind->value,
            $this->extract($this->modifiedFileDiff())
        );

        self::assertContains(
            AssertionKind::SameFileReference->value,
            $kinds,
            'Experiment 1\'s move survives — the narrowing is to created files only'
        );
    }

    // ---------------------------------------------------------------- a file that is not PHP

    public function testAFileThatIsNotPhpIsNotReadAsPhp(): void
    {
        // `menu:\n  label: Menu` tokenises into identifiers like any other text, and `: Menu` sits
        // in what the extractor reads as a return-type position.
        self::assertSame([], $this->extract($this->yamlDiff()), 'no assertions from a .yml file');
    }

    // ---------------------------------------------------------------- the parser's own state

    public function testTheParserRecordsWhichFilesWereCreated(): void
    {
        $parser = new UnifiedDiffParser();

        self::assertTrue($parser->parse($this->newFileDiffText())->files[0]->isNew, '--- /dev/null');
        self::assertFalse($parser->parse($this->modifiedFileDiffText())->files[0]->isNew);
    }

    public function testTheCreatedFlagDoesNotLeakToTheNextFileInTheSameDiff(): void
    {
        $diff = (new UnifiedDiffParser())->parse($this->newFileDiffText() . $this->modifiedFileDiffText());

        self::assertCount(2, $diff->files);
        self::assertTrue($diff->files[0]->isNew, 'the created one');
        self::assertFalse($diff->files[1]->isNew, 'and the modified one that follows it');
    }

    // ---------------------------------------------------------------- fixtures

    /**
     * @return list<\ContextDiscovery\Domain\Assertion\Assertion>
     */
    private function extract(string $diffText): array
    {
        $slicer = new TokenizerMemberSlicer();
        $extractor = new AssertionExtractor([
            new OwnFileAssertionExtractor($slicer),
            new NamedReferenceAssertionExtractor($slicer),
        ]);

        return $extractor->extract(
            (new UnifiedDiffParser())->parse($diffText),
            new FakeSourceRepository([
                self::NEW_PATH => $this->newFileText(),
                self::OLD_PATH => $this->modifiedFileText(),
                'config/thing.yml' => "menu:\n  label: Menu\n",
            ])
        );
    }

    private function newFileText(): string
    {
        return "<?php\n\nnamespace App\Services;\n\nuse App\Models\Package;\n\n"
            . "class NewService\n{\n    public function store(Package \$package): void\n    {\n"
            . "        \$this->audit(\$package);\n    }\n\n"
            . "    private function audit(Package \$package): void\n    {\n    }\n}\n";
    }

    private function modifiedFileText(): string
    {
        return "<?php\n\nnamespace App\Support;\n\nuse App\Models\Package;\n\n"
            . "class Existing\n{\n    public function helper(Package \$package): void\n    {\n    }\n\n"
            . "    public function extra(Package \$package): void\n    {\n"
            . "        \$this->helper(\$package);\n    }\n}\n";
    }

    private function newFileDiffText(): string
    {
        $lines = explode("\n", rtrim($this->newFileText(), "\n"));
        $out = ['diff --git a/' . self::NEW_PATH . ' b/' . self::NEW_PATH, 'new file mode 100644',
            '--- /dev/null', '+++ b/' . self::NEW_PATH, '@@ -0,0 +1,' . count($lines) . ' @@'];

        foreach ($lines as $line) {
            $out[] = '+' . $line;
        }

        return implode("\n", $out) . "\n";
    }

    private function modifiedFileDiffText(): string
    {
        return implode("\n", [
            'diff --git a/' . self::OLD_PATH . ' b/' . self::OLD_PATH,
            '--- a/' . self::OLD_PATH,
            '+++ b/' . self::OLD_PATH,
            '@@ -12,2 +12,5 @@',
            '     public function extra(Package $package): void',
            '     {',
            '+        $this->helper($package);',
            '+    }',
            '+',
        ]) . "\n";
    }

    private function newFileDiff(): string
    {
        return $this->newFileDiffText();
    }

    private function modifiedFileDiff(): string
    {
        return $this->modifiedFileDiffText();
    }

    private function yamlDiff(): string
    {
        return implode("\n", [
            'diff --git a/config/thing.yml b/config/thing.yml',
            'new file mode 100644',
            '--- /dev/null',
            '+++ b/config/thing.yml',
            '@@ -0,0 +1,2 @@',
            '+menu:',
            '+  label: Menu',
        ]) . "\n";
    }
}
