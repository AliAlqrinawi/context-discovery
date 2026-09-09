<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Support;

/**
 * Reads a decoded v2 bundle the way a consumer does.
 *
 * v2 states each claim once and has the items point at it, so "the kind of this item" is a join
 * rather than a field. Tests that used to read `$item['assertion_kind']` join here instead, which
 * keeps them asserting behaviour rather than layout (ADR-A024).
 */
trait ReadsBundles
{
    /**
     * @param array<string, mixed> $bundle
     * @param array<string, mixed> $item
     */
    protected function kindOfItem(array $bundle, array $item): string
    {
        return $this->claimOf($bundle, $item)['kind'] ?? '';
    }

    /**
     * @param array<string, mixed> $bundle
     * @param array<string, mixed> $item
     */
    protected function reasonOfItem(array $bundle, array $item): string
    {
        return $this->claimOf($bundle, $item)['reason'] ?? '';
    }

    /**
     * @param array<string, mixed> $bundle
     *
     * @return list<string> One kind per item, in item order.
     */
    protected function itemKinds(array $bundle): array
    {
        return array_map(fn (array $i): string => $this->kindOfItem($bundle, $i), $bundle['items']);
    }

    /**
     * @param array<string, mixed> $bundle
     *
     * @return list<array<string, mixed>>
     */
    protected function itemsOfKind(array $bundle, string $kind): array
    {
        return array_values(array_filter(
            $bundle['items'],
            fn (array $i): bool => $this->kindOfItem($bundle, $i) === $kind
        ));
    }

    /**
     * @param array<string, mixed> $bundle
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    protected function claimOf(array $bundle, array $item): array
    {
        foreach ($bundle['assertions'] as $assertion) {
            if ($assertion['id'] === $item['assertion_id']) {
                return $assertion;
            }
        }

        return [];
    }
}
