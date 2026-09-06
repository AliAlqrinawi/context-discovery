<?php
declare(strict_types=1);
/**
 * M24 · mechanical application of the frozen control criterion.
 *   php classify.php <repo> <label> [sha ...]
 * With no shas, evaluates every non-merge commit with a parent.
 */
[$s, $repo, $label] = $argv;
$only = array_slice($argv, 3);

function git(string $repo, string $args): string {
    return (string) shell_exec('git -C ' . escapeshellarg($repo) . ' ' . $args . ' 2>/dev/null');
}

$substantive = static function (string $line): bool {
    $t = trim($line);
    if (strlen($t) < 15) return false;
    foreach (['<?php', 'use ', 'namespace ', '//', '/*', '*', '#'] as $p) if (str_starts_with($t, $p)) return false;
    if (preg_match('/^[{}\[\]\(\);,]+$/', $t)) return false;
    return true;
};

$shas = $only !== [] ? $only : array_values(array_filter(explode("\n", trim(git($repo, 'rev-list --no-merges HEAD')))));
$rows = [];

foreach ($shas as $sha) {
    $short = substr(trim($sha), 0, 7);
    if (trim(git($repo, 'rev-list --parents -n 1 ' . $short)) === '' ) continue;
    $parents = count(explode(' ', trim(git($repo, 'rev-list --parents -n 1 ' . $short)))) - 1;

    $diff = git($repo, 'diff ' . $short . '^ ' . $short . ' -- "*.php"');
    $all  = git($repo, 'diff ' . $short . '^ ' . $short);
    $changed = preg_match_all('/^[+-][^+-]/m', $all);

    if ($parents !== 1)      { $rows[] = ['sha'=>$short,'state'=>'UNUSABLE','why'=>'merge or root','e1'=>null,'e2'=>null,'added'=>0,'lost'=>0]; continue; }
    if ($changed > 500)      { $rows[] = ['sha'=>$short,'state'=>'UNUSABLE','why'=>'>500 changed lines','e1'=>null,'e2'=>null,'added'=>0,'lost'=>0]; continue; }

    $added = [];
    foreach (explode("\n", $diff) as $l) {
        if (!str_starts_with($l, '+') || str_starts_with($l, '+++')) continue;
        $t = substr($l, 1);
        if ($substantive($t)) $added[] = trim($t);
    }
    $added = array_values(array_unique($added));

    if ($added === []) { $rows[] = ['sha'=>$short,'state'=>'UNUSABLE','why'=>'no substantive PHP addition','e1'=>null,'e2'=>null,'added'=>0,'lost'=>0]; continue; }

    // ---- E1: does every substantive added line still exist at HEAD?
    $lost = [];
    foreach ($added as $line) {
        $hit = trim(git($repo, 'grep -F -l -- ' . escapeshellarg($line) . ' HEAD -- "*.php"'));
        if ($hit === '') $lost[] = $line;
    }
    $e1 = $lost === [];

    // ---- E2: does any test file at HEAD reference a symbol this commit added or changed?
    preg_match_all('/(?:class|function)\s+([A-Za-z_][A-Za-z0-9_]*)/', implode("\n", $added), $m);
    $syms = array_values(array_unique(array_filter($m[1] ?? [], static fn ($x) => strlen($x) > 3)));
    foreach (explode("\n", trim(git($repo, 'diff --name-only ' . $short . '^ ' . $short . ' -- "*.php"'))) as $f) {
        if ($f === '') continue;
        $b = basename($f, '.php');
        if (strlen($b) > 3) $syms[] = $b;
    }
    $syms = array_values(array_unique($syms));

    $e2 = false; $e2where = '';
    foreach ($syms as $sym) {
        $hit = trim(git($repo, 'grep -l -- ' . escapeshellarg($sym) . ' HEAD -- "tests/*" "spec/*" "Tests/*"'));
        if ($hit !== '') { $e2 = true; $e2where = explode("\n", $hit)[0] . ' (' . $sym . ')'; break; }
    }

    // ---- state
    if ($e1 && $e2)        { $state = 'CONTROL';   $why = 'E1 pass (all ' . count($added) . ' added lines survive) + E2 pass: ' . $e2where; }
    elseif ($e1 && !$e2)   { $state = 'CONTESTED'; $why = 'E1 pass but E2 fail — nothing under a test root asserts this behaviour'; }
    elseif (!$e1)          { $state = 'CONTESTED'; $why = 'E1 fail — ' . count($lost) . ' of ' . count($added) . ' added lines no longer exist at HEAD; supersession not yet classified as correction vs move'; }
    else                   { $state = 'CONTESTED'; $why = 'unclassified'; }

    $rows[] = ['sha'=>$short,'state'=>$state,'why'=>$why,'e1'=>$e1,'e2'=>$e2,'added'=>count($added),'lost'=>count($lost),
               'lost_examples'=>array_slice($lost,0,2),'subject'=>trim(git($repo,'log -1 --format=%s '.$short))];
}

file_put_contents(__DIR__ . '/analysis/' . $label . '.json', json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n");
$c = [];
foreach ($rows as $r) $c[$r['state']] = ($c[$r['state']] ?? 0) + 1;
ksort($c);
printf("%s: %d commits — ", $label, count($rows));
foreach ($c as $k => $v) printf("%s=%d ", $k, $v);
echo "\n";
