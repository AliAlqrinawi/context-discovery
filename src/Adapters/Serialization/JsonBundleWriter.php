<?php

declare(strict_types=1);

namespace ContextDiscovery\Adapters\Serialization;

use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\DroppedItem;
use ContextDiscovery\Ports\BundleWriter;

/**
 * The stable machine contract: `bundle_version` 1, as specified in 03-interfaces.md §2.
 *
 * The writer emits facts and nothing else. Diagnostics belong on stderr — putting them here
 * would make the artifact larger than the `used_tokens` number that describes it, and that
 * number is the measurement (freeze review L2).
 *
 * Item order is decided upstream and preserved here, so the JSON and the Markdown are two
 * renderings of one ordering rather than two sorts that could drift apart.
 */
final class JsonBundleWriter implements BundleWriter
{
    private const BUNDLE_VERSION = 1;

    private const FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public function write(Bundle $bundle): string
    {
        $payload = [
            'bundle_version' => self::BUNDLE_VERSION,
            'budget_tokens' => $bundle->budgetTokens,
            'used_tokens' => $bundle->usedTokens,
            'items' => array_map($this->item(...), $bundle->items),
            // Never omitted, and empty rather than absent when nothing was dropped (P7).
            'dropped' => array_map($this->drop(...), $bundle->dropped),
        ];

        return json_encode($payload, self::FLAGS) . "\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function item(BundleItem $item): array
    {
        $provenance = ['path' => $item->provenance->path];

        if ($item->provenance->member !== null) {
            $provenance['member'] = $item->provenance->member;
        }

        if ($item->provenance->firstLine !== null && $item->provenance->lastLine !== null) {
            $provenance['lines'] = [$item->provenance->firstLine, $item->provenance->lastLine];
        }

        return [
            'lever' => $item->lever->value,
            'reason' => $item->reason,
            'assertion_kind' => $item->assertionKind->value,
            'provenance' => $provenance,
            'payload' => $item->payload,
            'tokens' => $item->tokens,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function drop(DroppedItem $dropped): array
    {
        return [
            'reason' => $dropped->reason,
            'note' => $dropped->note,
            'tokens' => $dropped->tokens,
        ];
    }
}
