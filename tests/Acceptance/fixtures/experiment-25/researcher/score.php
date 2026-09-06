<?php
declare(strict_types=1);
/**
 * M25-B scorer. Run ONLY after the reviewer has completed and saved reviewer/responses.md.
 *   php score.php <experiment-25 dir>
 * Reads the frozen key and mapping; scores the SCORED stratum only. Filler rows are reported
 * separately and never enter the primary score (M24: no defensible CONTROL exists).
 */
[$s, $fx] = $argv;
$key = json_decode((string) file_get_contents("$fx/researcher/answer-key.json"), true);
$map = json_decode((string) file_get_contents("$fx/researcher/task-mapping.json"), true)['mapping'];
$raw = (string) file_get_contents("$fx/reviewer/responses.md");

$byInternal = [];
foreach ($key['tasks'] as $t) { $byInternal[$t['task']] = $t; }

$answers = [];
foreach (preg_split('/^## /m', $raw) as $block) {
    if (!preg_match('/^(H\d\d)/', $block, $m)) { continue; }
    $get = static function (string $q) use ($block): string {
        return preg_match('/\*\*' . $q . '\*\*[^:]*:\s*(.*)$/m', $block, $x) ? trim($x[1]) : '';
    };
    $answers[$m[1]] = ['Q1' => strtoupper($get('Q1')), 'Q2' => $get('Q2'), 'Q3' => $get('Q3'),
                       'Q4' => strtoupper($get('Q4')), 'Q5' => $get('Q5')];
}

$rows = [];
foreach ($map as $t) {
    $h = $t['reviewer_task'];
    $k = $byInternal[$t['internal']];
    $a = $answers[$h] ?? null;
    $rows[] = [
        'task' => $h, 'condition' => $t['condition'], 'scored' => $k['in_primary_score'],
        'ground_truth' => $k['ground_truth'], 'answered' => $a !== null,
        'Q1' => $a['Q1'] ?? null, 'Q4' => $a['Q4'] ?? null,
        'correct' => $a && $k['in_primary_score'] ? ($a['Q1'] === 'YES') : null,
        'expected_defect' => $k['expected_defect'],
        'Q2' => $a['Q2'] ?? null, 'Q3' => $a['Q3'] ?? null, 'Q5' => $a['Q5'] ?? null,
    ];
}

$sc = array_values(array_filter($rows, static fn ($r) => $r['scored']));
$f = static function (array $set, string $c, callable $p): int {
    return count(array_filter($set, static fn ($r) => $r['condition'] === $c && $p($r)));
};
printf("SCORED stratum (n=%d) — Q1=YES is the correct answer on every row\n", count($sc));
foreach (['A', 'B'] as $c) {
    $n = count(array_filter($sc, static fn ($r) => $r['condition'] === $c));
    printf("  %-1s  n=%d  YES=%d  CANNOT_TELL=%d  NO=%d  HIGH=%d\n", $c, $n,
        $f($sc, $c, static fn ($r) => $r['Q1'] === 'YES'),
        $f($sc, $c, static fn ($r) => $r['Q1'] === 'CANNOT_TELL'),
        $f($sc, $c, static fn ($r) => $r['Q1'] === 'NO'),
        $f($sc, $c, static fn ($r) => $r['Q4'] === 'HIGH'));
}
echo "\nQ2 must be read by hand against expected_defect: naming a DIFFERENT real defect is recorded\n";
echo "separately and is NOT counted as identification (M17-M23 precedent).\n\n";
echo "FILLER stratum is reported but excluded from the primary score.\n";
file_put_contents("$fx/researcher/scored.json", json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "\nwritten: researcher/scored.json\n";
