<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Adapters\Serialization;

use ContextDiscovery\Adapters\Serialization\JsonBundleWriter;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Tests\Fakes\BundleFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonBundleWriter::class)]
final class JsonBundleWriterTest extends TestCase
{
    private JsonBundleWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new JsonBundleWriter();
    }

    public function testTheEnvelopeMatchesTheSchema(): void
    {
        $decoded = $this->decode(BundleFixture::full());

        self::assertSame(1, $decoded['bundle_version']);
        self::assertSame(8000, $decoded['budget_tokens']);
        self::assertSame(236, $decoded['used_tokens']);
        self::assertCount(3, $decoded['items']);
        self::assertCount(2, $decoded['dropped']);
    }

    public function testAnItemCarriesLeverReasonKindProvenancePayloadAndTokens(): void
    {
        $item = $this->decode(BundleFixture::full())['items'][0];

        self::assertSame(
            ['lever', 'reason', 'assertion_kind', 'provenance', 'payload', 'tokens'],
            array_keys($item)
        );

        self::assertSame('fetched', $item['lever']);
        self::assertSame(
            'changed call site depends on PlaidAccount::forItem() and official_name',
            $item['reason']
        );
        self::assertSame('named_reference', $item['assertion_kind']);
        self::assertSame('app/Models/PlaidAccount.php', $item['provenance']['path']);
        self::assertSame('forItem', $item['provenance']['member']);
        self::assertSame([41, 58], $item['provenance']['lines']);
        self::assertSame(180, $item['tokens']);
    }

    public function testOptionalProvenanceFieldsAreOmittedRatherThanNulled(): void
    {
        $items = $this->decode(BundleFixture::full())['items'];

        self::assertSame(['path', 'member'], array_keys($items[1]['provenance']));
        self::assertArrayNotHasKey('lines', $items[1]['provenance']);

        self::assertSame(['path'], array_keys($items[2]['provenance']));
        self::assertArrayNotHasKey('member', $items[2]['provenance']);
    }

    public function testADropCarriesItsReasonNoteAndTokens(): void
    {
        $dropped = $this->decode(BundleFixture::full())['dropped'][0];

        self::assertSame(
            ['reason' => 'route wiring for reauth endpoint', 'note' => 'below budget priority', 'tokens' => 260],
            $dropped
        );
    }

    public function testItemOrderIsPreservedExactlyAsTheBundleHoldsIt(): void
    {
        $kinds = array_column($this->decode(BundleFixture::full())['items'], 'assertion_kind');

        self::assertSame(['named_reference', 'unverifiable_premise', 'same_file_symbol_absence'], $kinds);
    }

    public function testAnEmptyBundleIsValidAndKeepsBothCollections(): void
    {
        $json = $this->writer->write(BundleFixture::empty());
        $decoded = $this->decode(BundleFixture::empty());

        self::assertSame([], $decoded['items']);
        self::assertSame([], $decoded['dropped']);
        self::assertStringContainsString('"items": []', $json);
        self::assertStringContainsString('"dropped": []', $json, 'the drop list is never omitted (P7)');
    }

    public function testDiagnosticsNeverAppearInTheBundle(): void
    {
        // stderr carries them; every one also has a flag item (freeze review L2, ADR-A009).
        self::assertArrayNotHasKey('diagnostics', $this->decode(BundleFixture::full()));
    }

    public function testPathsAreNotEscapedSoProvenanceStaysReadable(): void
    {
        self::assertStringContainsString('"path": "app/Models/PlaidAccount.php"', $this->writer->write(BundleFixture::full()));
        self::assertStringNotContainsString('app\/Models', $this->writer->write(BundleFixture::full()));
    }

    public function testWritingTheSameBundleTwiceIsByteIdentical(): void
    {
        self::assertSame(
            $this->writer->write(BundleFixture::full()),
            $this->writer->write(BundleFixture::full())
        );
    }

    public function testOutputIsValidJsonAndEndsWithANewline(): void
    {
        $json = $this->writer->write(BundleFixture::full());

        self::assertNotNull(json_decode($json, true));
        self::assertStringEndsWith("\n", $json);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Bundle $bundle): array
    {
        $decoded = json_decode($this->writer->write($bundle), true);

        self::assertIsArray($decoded);

        return $decoded;
    }
}
