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

        self::assertSame(
            ['bundle_version', 'run', 'used_tokens', 'assertions', 'items', 'diagnostics', 'dropped'],
            array_keys($decoded)
        );

        self::assertSame(2, $decoded['bundle_version']);
        self::assertSame(8000, $decoded['run']['budget_tokens']);
        self::assertSame(236, $decoded['used_tokens']);
        self::assertCount(3, $decoded['assertions']);
        self::assertCount(3, $decoded['items']);
        self::assertCount(1, $decoded['diagnostics']);
        self::assertCount(2, $decoded['dropped']);

        // repo_sha is null rather than absent, so "not supplied" is stated (ADR-A024).
        self::assertArrayHasKey('repo_sha', $decoded['run']);
        self::assertNull($decoded['run']['repo_sha']);
    }

    public function testAnItemNamesItsAssertionAndCarriesOnlyEvidence(): void
    {
        $decoded = $this->decode(BundleFixture::full());
        $item = $decoded['items'][0];
        $claim = $decoded['assertions'][0];

        self::assertSame(
            ['assertion_id', 'lever', 'provenance', 'payload', 'tokens'],
            array_keys($item),
            'the reason and the kind live on the assertion now, not on every item'
        );

        self::assertSame(['id', 'kind', 'subject', 'reason', 'origin'], array_keys($claim));
        self::assertSame($claim['id'], $item['assertion_id']);

        self::assertSame('fetched', $item['lever']);
        self::assertSame(
            'changed call site depends on PlaidAccount::forItem() and official_name',
            $claim['reason']
        );
        self::assertSame('named_reference', $claim['kind']);
        self::assertSame('App\Models\PlaidAccount::forItem', $claim['subject'], 'structured, not parsed from prose');
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
        $decoded = $this->decode(BundleFixture::full());
        $byId = array_column($decoded['assertions'], 'kind', 'id');
        $kinds = array_map(static fn (array $i): string => $byId[$i['assertion_id']], $decoded['items']);

        self::assertSame(['named_reference', 'unverifiable_premise', 'same_file_symbol_absence'], $kinds);
    }

    public function testAnEmptyBundleIsValidAndKeepsBothCollections(): void
    {
        $json = $this->writer->write(BundleFixture::empty());
        $decoded = $this->decode(BundleFixture::empty());

        self::assertSame([], $decoded['assertions']);
        self::assertSame([], $decoded['items']);
        self::assertSame([], $decoded['diagnostics']);
        self::assertSame([], $decoded['dropped']);
        self::assertStringContainsString('"items": []', $json);
        self::assertStringContainsString('"dropped": []', $json, 'the drop list is never omitted (P7)');
    }

    public function testDiagnosticsAreMirroredWithoutCostingTokens(): void
    {
        // v2 reversed freeze review L2's exclusion, but only on its own terms: stderr still carries
        // every line, and the mirror is free. The objection was that the artifact would outgrow the
        // number describing it, so used_tokens still sums the items alone (ADR-A024).
        $decoded = $this->decode(BundleFixture::full());

        self::assertSame(
            [['type' => 'call_sites_truncated', 'assertion_id' => BundleFixture::NAMED_REFERENCE_ID,
              'detail' => ['limit' => 20, 'subject' => 'forItem', 'scope' => 'app/']]],
            $decoded['diagnostics']
        );

        self::assertSame(
            array_sum(array_column($decoded['items'], 'tokens')),
            $decoded['used_tokens'],
            'the diagnostic costs nothing'
        );
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
