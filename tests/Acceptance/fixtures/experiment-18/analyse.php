<?php

declare(strict_types=1);

/** M18 · analysis — an experiment artifact. Reads per-commit.json, writes the aggregates. */

$out  = $argv[1];
$rows = json_decode((string) file_get_contents($out . '/analysis/per-commit.json'), true);

$median = static function (array $v): float {
    if ($v === []) return 0.0;
    sort($v);
    $n = count($v);
    return $n % 2 ? (float) $v[intdiv($n, 2)] : ($v[$n / 2 - 1] + $v[$n / 2]) / 2;
};
$mean = static fn (array $v): float => $v === [] ? 0.0 : array_sum($v) / count($v);

$nonEmpty = array_values(array_filter($rows, static fn ($r) => !$r['empty']));
$empty    = array_values(array_filter($rows, static fn ($r) => $r['empty']));

// ---------------------------------------------------------------- pre-registered taxonomy
$classify = static function (array $r): array {
    $seen = [];

    foreach ($r['diagnostics'] as $d) {
        if (str_starts_with($d, 'framework reference:') || str_starts_with($d, 'dependency member:')) $seen['E1'] = true;
        if (str_starts_with($d, 'dependency class:'))                                                 $seen['E2'] = true;
        if (str_starts_with($d, 'new file:'))                                                         $seen['E3'] = true;
        if (str_starts_with($d, 'already in the diff:'))                                              $seen['E4'] = true;
    }

    if ($r['dropped'] > 0) $seen['E5'] = true;
    if ($seen === [])      $seen['E0'] = true;

    // primary = first of E5,E4,E3,E2,E1,E0 that applies (pre-registered order)
    foreach (['E5', 'E4', 'E3', 'E2', 'E1', 'E0'] as $c) {
        if (isset($seen[$c])) { $primary = $c; break; }
    }

    return ['primary' => $primary, 'observed' => array_keys($seen)];
};

$byReason = [];
$emptyDetail = [];

foreach ($empty as $r) {
    $c = $classify($r);
    $byReason[$c['primary']] = ($byReason[$c['primary']] ?? 0) + 1;
    $emptyDetail[] = [
        'sha' => $r['sha'], 'subject' => $r['subject'],
        'primary' => $c['primary'], 'observed' => $c['observed'],
        'diagnostics' => $r['diagnostics'],
        'files' => $r['files'], 'changed_lines' => $r['changed_lines'], 'categories' => $r['categories'],
    ];
}
ksort($byReason);

// ---------------------------------------------------------------- buckets
$bucket = static function (int $v, array $edges): string {
    foreach ($edges as $label => [$lo, $hi]) {
        if ($v >= $lo && ($hi === null || $v <= $hi)) return (string) $label;
    }
    return '?';
};
$itemEdges  = ['0' => [0, 0], '1-2' => [1, 2], '3-5' => [3, 5], '6-10' => [6, 10], '11+' => [11, null]];
$tokenEdges = ['0' => [0, 0], '1-100' => [1, 100], '101-300' => [101, 300], '301-600' => [301, 600], '601-1000' => [601, 1000], '1001+' => [1001, null]];

$itemHist = array_fill_keys(array_keys($itemEdges), 0);
$tokHist  = array_fill_keys(array_keys($tokenEdges), 0);
foreach ($rows as $r) {
    $itemHist[$bucket($r['items'], $itemEdges)]++;
    $tokHist[$bucket((int) $r['tokens'], $tokenEdges)]++;
}

// ---------------------------------------------------------------- shapes (descriptive only)
$hasStatic = static function (array $r) use ($out): bool {
    $d = (string) file_get_contents($out . '/outputs/' . $r['sha'] . '.diff');
    foreach (explode("\n", $d) as $l) {
        if (str_starts_with($l, '+') && !str_starts_with($l, '+++') && preg_match('/\b[A-Z][A-Za-z0-9_]*::[a-zA-Z_]/', $l)) return true;
    }
    return false;
};

$shapes = [
    'has a static Name::member on an added line' => static fn ($r) => $hasStatic($r),
    'no static Name::member on any added line'   => static fn ($r) => !$hasStatic($r),
    'creates at least one file'                  => static fn ($r) => $r['created_files'] > 0,
    'modifies only'                              => static fn ($r) => $r['created_files'] === 0,
    'touches a model'                            => static fn ($r) => in_array('model', $r['categories'], true),
    'touches a controller'                       => static fn ($r) => in_array('controller', $r['categories'], true),
    'touches an action/service'                  => static fn ($r) => in_array('action-service', $r['categories'], true),
    'touches tests'                              => static fn ($r) => in_array('test', $r['categories'], true),
    'touches a migration'                        => static fn ($r) => in_array('migration', $r['categories'], true),
    'touches routes'                             => static fn ($r) => in_array('routes', $r['categories'], true),
    'touches config'                             => static fn ($r) => in_array('config', $r['categories'], true),
    'contains a non-PHP file'                    => static fn ($r) => in_array('non-php', $r['categories'], true),
    'no PHP file at all'                         => static fn ($r) => $r['php_files'] === 0,
];

$shapeRows = [];
foreach ($shapes as $label => $test) {
    $g = array_values(array_filter($rows, $test));
    if ($g === []) { $shapeRows[] = [$label, 0, '-', '-', '-']; continue; }
    $ne = array_values(array_filter($g, static fn ($r) => !$r['empty']));
    $shapeRows[] = [
        $label, count($g),
        sprintf('%d/%d (%.0f%%)', count($ne), count($g), 100 * count($ne) / count($g)),
        $median(array_map(static fn ($r) => $r['items'], $g)),
        $median(array_map(static fn ($r) => (int) $r['tokens'], $g)),
    ];
}

// ---------------------------------------------------------------- output
$report = [
    'head_measured'      => trim((string) shell_exec('git -C ' . escapeshellarg(dirname($out, 4)) . ' rev-parse HEAD')),
    'commits'            => count($rows),
    'non_empty'          => count($nonEmpty),
    'empty'              => count($empty),
    'pct_non_empty'      => round(100 * count($nonEmpty) / count($rows), 1),
    'pct_empty'          => round(100 * count($empty) / count($rows), 1),
    'mean_items'         => round($mean(array_map(static fn ($r) => $r['items'], $rows)), 2),
    'median_items'       => $median(array_map(static fn ($r) => $r['items'], $rows)),
    'mean_tokens'        => round($mean(array_map(static fn ($r) => (int) $r['tokens'], $rows)), 1),
    'median_tokens'      => $median(array_map(static fn ($r) => (int) $r['tokens'], $rows)),
    'max_items'          => max(array_map(static fn ($r) => $r['items'], $rows)),
    'max_tokens'         => max(array_map(static fn ($r) => (int) $r['tokens'], $rows)),
    'mean_fetched'       => round($mean(array_map(static fn ($r) => $r['fetched'], $rows)), 2),
    'mean_flags'         => round($mean(array_map(static fn ($r) => $r['flagged'], $rows)), 2),
    'flags_only'         => count(array_filter($rows, static fn ($r) => $r['items'] > 0 && $r['fetched'] === 0)),
    'fetched_context'    => count(array_filter($rows, static fn ($r) => $r['fetched'] > 0)),
    'both'               => count(array_filter($rows, static fn ($r) => $r['fetched'] > 0 && $r['flagged'] > 0)),
    'non_empty_only'     => [
        'mean_items'   => round($mean(array_map(static fn ($r) => $r['items'], $nonEmpty)), 2),
        'median_items' => $median(array_map(static fn ($r) => $r['items'], $nonEmpty)),
        'mean_tokens'  => round($mean(array_map(static fn ($r) => (int) $r['tokens'], $nonEmpty)), 1),
        'median_tokens'=> $median(array_map(static fn ($r) => (int) $r['tokens'], $nonEmpty)),
    ],
    'item_histogram'     => $itemHist,
    'token_histogram'    => $tokHist,
    'empty_by_reason'    => $byReason,
    'empty_detail'       => $emptyDetail,
    'shapes'             => $shapeRows,
    'total_vendor_items' => array_sum(array_map(static fn ($r) => $r['vendor_items'], $rows)),
    'unprocessed'        => count(array_filter($rows, static fn ($r) => !$r['processed'])),
];

file_put_contents($out . '/analysis/aggregate.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

printf("commits=%d  non-empty=%d (%.1f%%)  empty=%d (%.1f%%)\n", $report['commits'], $report['non_empty'], $report['pct_non_empty'], $report['empty'], $report['pct_empty']);
printf("items  mean=%.2f median=%s max=%d | tokens mean=%.1f median=%s max=%d\n", $report['mean_items'], $report['median_items'], $report['max_items'], $report['mean_tokens'], $report['median_tokens'], $report['max_tokens']);
printf("non-empty only: median items=%s median tokens=%s\n", $report['non_empty_only']['median_items'], $report['non_empty_only']['median_tokens']);
printf("flags-only=%d  with fetched=%d  both=%d  vendor items=%d  unprocessed=%d\n", $report['flags_only'], $report['fetched_context'], $report['both'], $report['total_vendor_items'], $report['unprocessed']);
echo "items: "; foreach ($itemHist as $k => $v) printf("%s=%d ", $k, $v); echo "\n";
echo "tokens: "; foreach ($tokHist as $k => $v) printf("%s=%d ", $k, $v); echo "\n";
echo "empty by primary reason: "; foreach ($byReason as $k => $v) printf("%s=%d ", $k, $v); echo "\n";
