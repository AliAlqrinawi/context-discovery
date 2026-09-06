<?php

declare(strict_types=1);

/**
 * M18 · corpus runner — an experiment artifact, not production code.
 *
 * Selection rule, fixed and reproducible: EVERY commit reachable from the corpus repository's HEAD
 * that has a parent. The repository has 47 commits and no merges, so this is the complete
 * population minus the root commit — there is no sampling and therefore no selection bias.
 *
 *   php run-corpus.php <corpus-repo> <cli-root> <out-dir>
 */

[$script, $repo, $cli, $out] = $argv;

@mkdir($out . '/outputs', 0777, true);

$shas = array_values(array_filter(explode("\n", trim(shell_exec(
    'git -C ' . escapeshellarg($repo) . ' rev-list --reverse HEAD'
)))));

$rows = [];

foreach ($shas as $sha) {
    $parents = trim(shell_exec('git -C ' . escapeshellarg($repo) . ' rev-list --parents -n 1 ' . $sha));
    $parentCount = count(explode(' ', $parents)) - 1;

    if ($parentCount === 0) {
        continue; // the root commit has no diff to review
    }

    $short   = substr($sha, 0, 7);
    $subject = trim(shell_exec('git -C ' . escapeshellarg($repo) . ' log -1 --format=%s ' . $sha));
    $diff    = $out . '/outputs/' . $short . '.diff';

    file_put_contents($diff, shell_exec(
        'git -C ' . escapeshellarg($repo) . ' diff ' . $sha . '^ ' . $sha
    ));

    $files = array_values(array_filter(explode("\n", trim(shell_exec(
        'git -C ' . escapeshellarg($repo) . ' diff --name-only ' . $sha . '^ ' . $sha
    )))));

    $lines = 0;
    foreach (file($diff) as $line) {
        if ((str_starts_with($line, '+') && !str_starts_with($line, '+++'))
            || (str_starts_with($line, '-') && !str_starts_with($line, '---'))) {
            $lines++;
        }
    }

    $created = (int) trim(shell_exec(
        'git -C ' . escapeshellarg($repo) . ' diff --diff-filter=A --name-only ' . $sha . '^ ' . $sha . ' | wc -l'
    ));

    // Run the unchanged binary. Output is recorded exactly as produced.
    $json = $out . '/outputs/' . $short . '.json';
    $md   = $out . '/outputs/' . $short . '.md';
    $err  = $out . '/outputs/' . $short . '.stderr';

    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($cli . '/bin/context-discover')
        . ' --diff=' . escapeshellarg($diff) . ' --repo=' . escapeshellarg($repo)
        . ' --budget=8000 --format=';

    shell_exec($cmd . 'json > ' . escapeshellarg($json) . ' 2> ' . escapeshellarg($err));
    shell_exec($cmd . 'markdown > ' . escapeshellarg($md) . ' 2>/dev/null');

    $bundle = json_decode((string) file_get_contents($json), true);
    $stderr = array_values(array_filter(explode("\n", trim((string) file_get_contents($err)))));

    $items = $bundle['items'] ?? [];

    $category = static function (string $path): string {
        if (str_starts_with($path, 'tests/')) return 'test';
        if (str_starts_with($path, 'database/migrations/')) return 'migration';
        if (str_starts_with($path, 'database/')) return 'database';
        if (str_starts_with($path, 'routes/')) return 'routes';
        if (str_starts_with($path, 'config/')) return 'config';
        if (str_contains($path, '/Http/Controllers/')) return 'controller';
        if (str_contains($path, '/Models/')) return 'model';
        if (str_contains($path, '/Actions/') || str_contains($path, '/Services/')) return 'action-service';
        if (str_starts_with($path, 'app/')) return 'app-other';
        if (!str_ends_with($path, '.php')) return 'non-php';
        return 'other';
    };

    $cats = [];
    $php  = 0;
    foreach ($files as $f) {
        $cats[$category($f)] = true;
        if (str_ends_with($f, '.php')) $php++;
    }
    ksort($cats);

    $rows[] = [
        'sha'          => $short,
        'full_sha'     => $sha,
        'subject'      => $subject,
        'is_merge'     => $parentCount > 1,
        'files'        => count($files),
        'php_files'    => $php,
        'created_files'=> $created,
        'changed_lines'=> $lines,
        'categories'   => array_keys($cats),
        'processed'    => is_array($bundle),
        'items'        => count($items),
        'fetched'      => count(array_filter($items, static fn (array $i): bool => $i['lever'] === 'fetched')),
        'flagged'      => count(array_filter($items, static fn (array $i): bool => $i['lever'] === 'flagged')),
        'tokens'       => $bundle['used_tokens'] ?? null,
        'dropped'      => count($bundle['dropped'] ?? []),
        'vendor_items' => count(array_filter($items, static fn (array $i): bool => str_contains($i['provenance']['path'], 'vendor/'))),
        'kinds'        => array_values(array_unique(array_map(static fn (array $i): string => $i['assertion_kind'], $items))),
        'diagnostics'  => $stderr,
        'empty'        => $items === [],
    ];
}

file_put_contents($out . '/analysis/per-commit.json', json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

printf("commits measured: %d\n", count($rows));
printf("non-empty: %d   empty: %d\n",
    count(array_filter($rows, static fn (array $r): bool => !$r['empty'])),
    count(array_filter($rows, static fn (array $r): bool => $r['empty'])));
