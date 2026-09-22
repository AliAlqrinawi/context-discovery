<?php
// Items present in one bundle and not the other, keyed on every visible field (ADR-A021).
[$_, $aPath, $bPath] = $argv;
$load = function (string $p): array {
    $b = json_decode(file_get_contents($p), true);
    $as = array_column($b['assertions'], null, 'id');
    $out = [];
    foreach ($b['items'] as $i) {
        $a = $as[$i['assertion_id']];
        $key = sha1(json_encode([$a['kind'], $a['reason'], $i['lever'], $i['provenance'], $i['payload']]));
        $out[$key] = [$a['kind'], $i['lever'], $a['subject'], $i['provenance']['path'], $i['tokens'], substr(str_replace("\n", ' ', $i['payload']), 0, 110)];
    }
    return $out;
};
$a = $load($aPath); $b = $load($bPath);
$onlyA = array_diff_key($a, $b); $onlyB = array_diff_key($b, $a);
printf("only in A (symlink): %d   only in B (real vendor): %d   shared: %d\n", count($onlyA), count($onlyB), count(array_intersect_key($a, $b)));
$tally = [];
foreach ($onlyA as $r) { $tally["{$r[0]} / {$r[1]}"] = ($tally["{$r[0]} / {$r[1]}"] ?? 0) + 1; }
foreach ($tally as $k => $n) { echo "  A-only  $n × $k\n"; }
foreach ($onlyB as $r) { echo "  B-only  {$r[0]} / {$r[1]}  {$r[2]}\n"; }
if (($argv[3] ?? '') === '-v') { foreach ($onlyA as $r) { printf("    - [%s] subject=%s  at %s  (%d tok)\n      %s\n", $r[1], $r[2], $r[3], $r[4], $r[5]); } }
