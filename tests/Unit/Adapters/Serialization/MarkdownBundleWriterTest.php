<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Adapters\Serialization;

use ContextDiscovery\Adapters\Serialization\JsonBundleWriter;
use ContextDiscovery\Adapters\Serialization\MarkdownBundleWriter;
use ContextDiscovery\Tests\Fakes\BundleFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MarkdownBundleWriter::class)]
final class MarkdownBundleWriterTest extends TestCase
{
    private MarkdownBundleWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new MarkdownBundleWriter();
    }

    public function testTheHeaderCarriesTheBudgetLine(): void
    {
        self::assertStringContainsString(
            'bundle_version 1 · budget 8000 / used 236 tokens',
            $this->writer->write(BundleFixture::full())
        );
    }

    public function testEachItemHasItsOwnHeadingCarryingLeverAndKind(): void
    {
        $markdown = $this->writer->write(BundleFixture::full());

        self::assertStringContainsString('## fetched · named_reference', $markdown);
        self::assertStringContainsString('## flagged · unverifiable_premise', $markdown);
        self::assertStringContainsString('## fetched · same_file_symbol_absence', $markdown);
        self::assertSame(3, substr_count($markdown, "\n## fetched") + substr_count($markdown, "\n## flagged"));
    }

    public function testAnItemCarriesItsReasonProvenanceAndTokens(): void
    {
        $markdown = $this->writer->write(BundleFixture::full());

        self::assertStringContainsString(
            '**Reason:** changed call site depends on PlaidAccount::forItem() and official_name',
            $markdown
        );
        self::assertStringContainsString(
            '**Source:** `app/Models/PlaidAccount.php` :: `forItem` (lines 41-58)',
            $markdown
        );
        self::assertStringContainsString('**Tokens:** 180', $markdown);
    }

    public function testProvenanceDegradesGracefullyWhenMemberOrLinesAreAbsent(): void
    {
        $markdown = $this->writer->write(BundleFixture::full());

        self::assertStringContainsString(
            '**Source:** `app/Services/Plaid/PlaidAccountService.php` :: `syncFromResponse`' . "\n",
            $markdown
        );
        self::assertStringContainsString(
            '**Source:** `app/Services/Plaid/PlaidAccountService.php`' . "\n",
            $markdown
        );
    }

    public function testTheFenceWidensPastBackticksInsideThePayload(): void
    {
        $markdown = $this->writer->write(BundleFixture::full());

        // The own-file slice contains a ``` run, so its fence must be longer than three.
        self::assertStringContainsString("````php\n/** ```php */", $markdown);
        self::assertStringContainsString("use App\\Models\\PlaidAccount;\n````", $markdown);
    }

    public function testAFlaggedPayloadIsFencedAsTextAndAFetchedOneAsPhp(): void
    {
        $markdown = $this->writer->write(BundleFixture::full());

        self::assertStringContainsString(
            "```text\nASSUMPTION: this code assumes a surrounding transaction; caller not checked\n```",
            $markdown
        );
        self::assertStringContainsString("```php\npublic function forItem(", $markdown);
    }

    public function testTheDroppedSectionListsEveryDropWithItsReasonNoteAndTokens(): void
    {
        $markdown = $this->writer->write(BundleFixture::full());

        self::assertStringContainsString('## Dropped', $markdown);
        self::assertStringContainsString(
            '- **route wiring for reauth endpoint** — below budget priority (260 tokens)',
            $markdown
        );
        self::assertStringContainsString(
            '- **ExchangePublicTokenRequest validation rules** — below budget priority (310 tokens)',
            $markdown
        );
    }

    public function testAnEmptyBundleStillShowsTheDroppedSection(): void
    {
        $markdown = $this->writer->write(BundleFixture::empty());

        self::assertStringContainsString('## Dropped', $markdown);
        self::assertStringContainsString('Nothing was dropped.', $markdown);
        self::assertStringNotContainsString('## fetched', $markdown);
    }

    public function testItemOrderMatchesTheBundle(): void
    {
        $markdown = $this->writer->write(BundleFixture::full());

        self::assertLessThan(
            strpos($markdown, '## flagged · unverifiable_premise'),
            strpos($markdown, '## fetched · named_reference')
        );
        self::assertLessThan(
            strpos($markdown, '## fetched · same_file_symbol_absence'),
            strpos($markdown, '## flagged · unverifiable_premise')
        );
    }

    public function testWritingTheSameBundleTwiceIsByteIdentical(): void
    {
        self::assertSame(
            $this->writer->write(BundleFixture::full()),
            $this->writer->write(BundleFixture::full())
        );
    }

    /**
     * The Markdown is a projection of the JSON, never a different set of facts. Every scalar the
     * machine contract carries must be readable in the human one.
     */
    public function testEveryFactInTheJsonAppearsInTheMarkdown(): void
    {
        $markdown = $this->writer->write(BundleFixture::full());
        $decoded = json_decode((new JsonBundleWriter())->write(BundleFixture::full()), true);

        self::assertIsArray($decoded);

        foreach ($this->scalars($decoded) as $path => $value) {
            self::assertStringContainsString(
                (string) $value,
                $markdown,
                sprintf('the Markdown omits the JSON fact at %s', $path)
            );
        }
    }

    public function testTheMarkdownAddsNoFactTheJsonLacks(): void
    {
        $markdown = $this->writer->write(BundleFixture::full());
        $json = (new JsonBundleWriter())->write(BundleFixture::full());

        // Structural furniture aside, every quoted identifier in the Markdown is a provenance
        // path or member, and each must exist in the machine contract.
        preg_match_all('/`([a-zA-Z0-9_\/.]+\.php|[a-zA-Z_][a-zA-Z0-9_]*)`/', $markdown, $matches);

        self::assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $identifier) {
            self::assertStringContainsString(
                $identifier,
                $json,
                sprintf('the Markdown names "%s", which the JSON does not carry', $identifier)
            );
        }
    }

    public function testOutputEndsWithANewline(): void
    {
        self::assertStringEndsWith("\n", $this->writer->write(BundleFixture::full()));
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<string, scalar>
     */
    private function scalars(array $data, string $prefix = ''): array
    {
        $found = [];

        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $found += $this->scalars($value, $path);
                continue;
            }

            $found[$path] = $value;
        }

        return $found;
    }
}
