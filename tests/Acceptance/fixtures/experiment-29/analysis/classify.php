<?php
// Heuristic, for v1 captures that cannot be re-run: an "unresolved on disk" named_reference flag
// whose subject is outside the project's own namespaces is a dependency class, i.e. one the
// vendor map would have placed. Project namespaces: App\, Database\, Tests\.
$b = json_decode(file_get_contents($argv[1]), true);
$items = $b['items']; $n = count($items); $tok = $b['used_tokens'] ?? array_sum(array_column($items, 'tokens'));
$sp = 0; $spTok = 0; $flag = 0;
foreach ($items as $i) {
    if (($i['lever'] ?? '') === 'flagged') { $flag++; }
    if (!str_contains($i['payload'], 'could not be resolved on disk')) { continue; }
    $subject = ltrim((string) ($i['provenance']['member'] ?? ''), '\\');
    if ($subject !== '' && preg_match('/^(App|Database|Tests)\\\\/', $subject) !== 1 && str_contains($subject, '\\')) { $sp++; $spTok += $i['tokens']; }
}
printf("%d\t%d\t%d\t%d\t%d\t%d\t%d", $n, $tok, $flag, $sp, $spTok, $n - $sp, $tok - $spTok);
