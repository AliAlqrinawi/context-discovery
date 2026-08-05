<?php

declare(strict_types=1);

namespace ContextDiscovery\Adapters\Serialization;

use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Ports\BundleWriter;

/**
 * The artifact a human pastes alongside the diff.
 *
 * A projection of the JSON, never a different set of facts: every field the machine contract
 * carries appears here too, and nothing else does. Item order is the order the bundle already
 * holds, so the two renderings cannot disagree.
 */
final class MarkdownBundleWriter implements BundleWriter
{
    private const BUNDLE_VERSION = 1;

    public function write(Bundle $bundle): string
    {
        $lines = [
            '# Context bundle',
            '',
            sprintf(
                'bundle_version %d · budget %d / used %d tokens',
                self::BUNDLE_VERSION,
                $bundle->budgetTokens,
                $bundle->usedTokens,
            ),
        ];

        foreach ($bundle->items as $item) {
            $lines[] = '';
            array_push($lines, ...$this->item($item));
        }

        $lines[] = '';
        $lines[] = '## Dropped';
        $lines[] = '';

        if ($bundle->dropped === []) {
            $lines[] = 'Nothing was dropped.';
        }

        foreach ($bundle->dropped as $dropped) {
            $lines[] = sprintf(
                '- **%s** — %s (%d tokens)',
                $dropped->reason,
                $dropped->note,
                $dropped->tokens,
            );
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return list<string>
     */
    private function item(BundleItem $item): array
    {
        $lines = [
            sprintf('## %s · %s', $item->lever->value, $item->assertionKind->value),
            '',
            sprintf('**Reason:** %s', $item->reason),
            sprintf('**Source:** %s', $this->provenance($item)),
            sprintf('**Tokens:** %d', $item->tokens),
            '',
        ];

        $fence = $this->fence($item->payload);

        $lines[] = $fence . ($item->lever === Lever::Fetched ? 'php' : 'text');
        array_push($lines, ...explode("\n", $item->payload));
        $lines[] = $fence;

        return $lines;
    }

    private function provenance(BundleItem $item): string
    {
        $provenance = sprintf('`%s`', $item->provenance->path);

        if ($item->provenance->member !== null) {
            $provenance .= sprintf(' :: `%s`', $item->provenance->member);
        }

        if ($item->provenance->firstLine !== null && $item->provenance->lastLine !== null) {
            $provenance .= sprintf(' (lines %d-%d)', $item->provenance->firstLine, $item->provenance->lastLine);
        }

        return $provenance;
    }

    /**
     * A payload is source, and source can contain backticks. The fence has to be longer than the
     * longest run inside it or the document breaks where the slice was supposed to be read.
     */
    private function fence(string $payload): string
    {
        $longest = 0;

        if (preg_match_all('/`+/', $payload, $matches) > 0) {
            foreach ($matches[0] as $run) {
                $longest = max($longest, strlen($run));
            }
        }

        return str_repeat('`', max(3, $longest + 1));
    }
}
