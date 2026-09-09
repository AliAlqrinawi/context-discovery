<?php

declare(strict_types=1);

namespace ContextDiscovery\Adapters\Serialization;

use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleAssertion;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\ContractVersion;
use ContextDiscovery\Domain\Bundle\Diagnostic;
use ContextDiscovery\Domain\Bundle\DroppedItem;
use ContextDiscovery\Ports\BundleWriter;

/**
 * The stable machine contract: `bundle_version` 2, as specified in 03-interfaces.md §2 and frozen
 * by `schema/bundle-v2.schema.json`.
 *
 * The claim is emitted once in `assertions`; `items` are the evidence, each naming the assertion it
 * belongs to. Nothing is duplicated and nothing has to be recovered from prose.
 *
 * `diagnostics` is a **mirror** of stderr, not a relocation: the stream still carries every line,
 * byte for byte, because a harness uses it to tell "searched, found none" from "never searched"
 * (freeze review 05). Diagnostics carry no tokens and are excluded from `used_tokens`, which
 * answers freeze review L2's objection exactly — the number still describes the items it measures.
 *
 * Item order is decided upstream and preserved here, so the JSON and the Markdown are two
 * renderings of one ordering rather than two sorts that could drift apart.
 */
final class JsonBundleWriter implements BundleWriter
{
    private const FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public function write(Bundle $bundle): string
    {
        $payload = [
            'bundle_version' => ContractVersion::BUNDLE,
            'run' => [
                'engine_version' => $bundle->run->engineVersion,
                'policy_version' => $bundle->run->policyVersion,
                'framework_table_version' => $bundle->run->frameworkTableVersion,
                'budget_tokens' => $bundle->run->budgetTokens,
                // Explicitly null rather than absent: "not supplied" is a fact worth stating.
                'diff_sha' => $bundle->run->diffSha,
                'repo_sha' => $bundle->run->repoSha,
            ],
            'used_tokens' => $bundle->usedTokens,
            'assertions' => array_map($this->assertion(...), $bundle->assertions),
            'items' => array_map($this->item(...), $bundle->items),
            // Never omitted, and empty rather than absent when there is nothing to report.
            'diagnostics' => array_map($this->diagnostic(...), $bundle->diagnostics),
            // Never omitted, and empty rather than absent when nothing was dropped (P7).
            'dropped' => array_map($this->drop(...), $bundle->dropped),
        ];

        return json_encode($payload, self::FLAGS) . "\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function assertion(BundleAssertion $assertion): array
    {
        $origin = ['path' => $assertion->originPath];

        if ($assertion->originFirstLine !== null && $assertion->originLastLine !== null) {
            $origin['lines'] = [$assertion->originFirstLine, $assertion->originLastLine];
        }

        return [
            'id' => $assertion->id,
            'kind' => $assertion->kind->value,
            'subject' => $assertion->subject,
            'reason' => $assertion->reason,
            'origin' => $origin,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(BundleItem $item): array
    {
        $provenance = ['path' => $item->provenance->path];

        // Optional by contract. Present when the slice *is* a member — the enclosing member, a
        // named reference's declaration, a flag whose assertion names one. Absent for a `use`
        // block and for every call site, because a call site is a line, not a member.
        if ($item->provenance->member !== null) {
            $provenance['member'] = $item->provenance->member;
        }

        if ($item->provenance->firstLine !== null && $item->provenance->lastLine !== null) {
            $provenance['lines'] = [$item->provenance->firstLine, $item->provenance->lastLine];
        }

        return [
            'assertion_id' => $item->assertionId,
            'lever' => $item->lever->value,
            'provenance' => $provenance,
            'payload' => $item->payload,
            'tokens' => $item->tokens,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function diagnostic(Diagnostic $diagnostic): array
    {
        return [
            'type' => $diagnostic->type,
            'assertion_id' => $diagnostic->assertionId,
            'detail' => $diagnostic->detail,
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
