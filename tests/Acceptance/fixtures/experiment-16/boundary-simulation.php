<?php

declare(strict_types=1);

/**
 * M16 · boundary simulation — an experiment artifact, not production code.
 *
 * Reads a bundle the real binary produced and asks what each candidate identity rule WOULD keep.
 * Nothing here is wired into the CLI and no production file was edited to run it.
 *
 *   php tests/Acceptance/fixtures/experiment-16/boundary-simulation.php <bundle.json>
 *
 * Each boundary is expressed as the **key** it would collapse on. That is the whole question: not
 * "should duplicates go" but "what makes two items the same item".
 */

$bundle = json_decode((string) file_get_contents($argv[1]), true);
$items  = $bundle['items'];

$span = static fn (array $item): string => json_encode($item['provenance']['lines'] ?? null);

$boundaries = [
    'B0 current (no collapse)' => null,

    // B1 · the slice's location.
    'B1 (path, member, span)' => static fn (array $i): string => $i['provenance']['path']
        . '|' . ($i['provenance']['member'] ?? '')
        . '|' . $span($i),

    // B2 · the slice's text, wherever it came from.
    'B2 payload text' => static fn (array $i): string => $i['payload'],

    // B3 · the slice, keeping every provenance seen (needs somewhere to put them).
    'B3 slice, provenances merged' => static fn (array $i): string => $i['provenance']['path']
        . '|' . ($i['provenance']['member'] ?? '')
        . '|' . $span($i)
        . '|' . $i['payload'],

    // B4 · every field a reviewer can see.
    'B4 whole visible item' => static fn (array $i): string => $i['lever']
        . '|' . $i['reason']
        . '|' . $i['assertion_kind']
        . '|' . $i['provenance']['path']
        . '|' . ($i['provenance']['member'] ?? '')
        . '|' . $span($i)
        . '|' . $i['payload'],

    // B5 · keep whenever provenance differs (provenance alone is the identity).
    'B5 provenance only' => static fn (array $i): string => $i['provenance']['path']
        . '|' . ($i['provenance']['member'] ?? '')
        . '|' . $span($i),
];

/**
 * The rows each boundary must not break, expressed as pairs of items that MUST stay separate.
 *
 * @var array<string, array{0: callable(array): bool, 1: callable(array): bool}>
 */
$mustStaySeparate = [
    'R4 same path, different members' => [
        static fn (array $i): bool => $i['provenance']['path'] === 'app/Services/Calc.php' && ($i['provenance']['member'] ?? null) === 'total',
        static fn (array $i): bool => $i['provenance']['path'] === 'app/Services/Calc.php' && ($i['provenance']['member'] ?? null) === 'subtotal',
    ],
    'R5 different paths, same member name' => [
        static fn (array $i): bool => $i['provenance']['path'] === 'app/Services/Calc.php' && ($i['provenance']['member'] ?? null) === 'total',
        static fn (array $i): bool => $i['provenance']['path'] === 'app/Services/Formatter.php' && ($i['provenance']['member'] ?? null) === 'total',
    ],
    'R6 same span, different resolution route' => [
        static fn (array $i): bool => ($i['provenance']['member'] ?? null) === 'helper' && $i['assertion_kind'] === 'same_file_reference',
        static fn (array $i): bool => ($i['provenance']['member'] ?? null) === 'helper' && $i['assertion_kind'] === 'named_reference',
    ],
    'R7 identical text, different class' => [
        static fn (array $i): bool => $i['provenance']['path'] === 'app/Services/Alpha.php',
        static fn (array $i): bool => $i['provenance']['path'] === 'app/Services/Beta.php',
    ],
    'R8 same path, different spans' => [
        static fn (array $i): bool => $i['provenance']['path'] === 'app/Http/ControllerA.php' && ($i['provenance']['member'] ?? null) === null,
        static fn (array $i): bool => $i['provenance']['path'] === 'app/Http/ControllerA.php' && ($i['provenance']['member'] ?? null) === 'helper',
    ],
    'R10 two flags, two origins' => [
        static fn (array $i): bool => $i['lever'] === 'flagged' && $i['provenance']['path'] === 'app/Http/ControllerA.php',
        static fn (array $i): bool => $i['lever'] === 'flagged' && str_contains($i['provenance']['path'], 'ControllerB'),
    ],
];

$first = static function (array $items, callable $match): ?array {
    foreach ($items as $item) {
        if ($match($item)) {
            return $item;
        }
    }

    return null;
};

printf("%-30s %6s %8s %7s %8s  %s\n", 'boundary', 'items', 'fetched', 'flags', 'tokens', 'rows broken');

foreach ($boundaries as $label => $identity) {
    if ($identity === null) {
        $kept = $items;
    } else {
        $kept = [];
        $seen = [];

        foreach ($items as $item) {
            $key = $identity($item);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $kept[] = $item;
        }
    }

    $broken = [];

    foreach ($mustStaySeparate as $row => [$a, $b]) {
        $left  = $first($items, $a);
        $right = $first($items, $b);

        if ($left === null || $right === null) {
            $broken[] = $row . ' (NOT PRESENT)';

            continue;
        }

        if ($identity !== null && $identity($left) === $identity($right)) {
            $broken[] = $row;
        }
    }

    printf(
        "%-30s %6d %8d %7d %8d  %s\n",
        $label,
        count($kept),
        count(array_filter($kept, static fn (array $i): bool => $i['lever'] === 'fetched')),
        count(array_filter($kept, static fn (array $i): bool => $i['lever'] === 'flagged')),
        array_sum(array_map(static fn (array $i): int => $i['tokens'], $kept)),
        $broken === [] ? '—' : implode('; ', $broken)
    );
}
