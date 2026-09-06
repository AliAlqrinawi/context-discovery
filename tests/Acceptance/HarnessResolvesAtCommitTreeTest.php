<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * M20 · the experiment harness must resolve against the commit's own tree.
 *
 * **The defect this pins.** M18 and M19 generated every bundle with `--repo` pointing at the corpus
 * repository's *working tree at HEAD*, while `--diff` was a historical commit's diff. Source
 * resolution therefore used whatever the files look like today. For a commit whose files have since
 * moved, the tool read the wrong tree — or, when the path no longer existed at all, none: 6 of
 * M18's 46 commits carry an `unreadable path` diagnostic for that reason, and it compromised M19's
 * task K1 badly enough that K1 had to be excluded from the totals.
 *
 * **This is a harness defect, not a production one.** The tool did exactly what it was told; it was
 * told the wrong thing. `src/` and `bin/` are untouched by the correction, which is a detached
 * `git worktree` at the commit under review.
 *
 * The test is self-contained: it builds a throwaway git repository with a two-commit history in
 * which a class **moves**, and asserts that the corrected harness sees the old location and the
 * HEAD-pointing harness does not. It needs no corpus and no network.
 */
final class HarnessResolvesAtCommitTreeTest extends TestCase
{
    private string $repo = '';

    protected function setUp(): void
    {
        $this->repo = sys_get_temp_dir() . '/m20-harness-' . bin2hex(random_bytes(6));

        mkdir($this->repo . '/app/Services', 0777, true);
        mkdir($this->repo . '/app/Http', 0777, true);

        file_put_contents($this->repo . '/composer.json', json_encode([
            'autoload' => ['psr-4' => ['App\\' => 'app/']],
        ]));

        file_put_contents($this->repo . '/app/Services/Calc.php', "<?php\n\nnamespace App\Services;\n\n"
            . "final class Calc\n{\n    public static function total(array \$lines): int\n    {\n"
            . "        return array_sum(\$lines);\n    }\n}\n");

        // Commit 1: a consumer that names Calc::total, at app/Http/Old.php.
        file_put_contents($this->repo . '/app/Http/Old.php', "<?php\n\nnamespace App\Http;\n\nclass Old\n{\n}\n");

        $this->git('init -q');
        $this->git('add -A');
        $this->git('-c user.email=x -c user.name=x commit -qm base');

        file_put_contents($this->repo . '/app/Http/Old.php', "<?php\n\nnamespace App\Http;\n\n"
            . "use App\Services\Calc;\n\nclass Old\n{\n    public function run(array \$l): int\n    {\n"
            . "        return Calc::total(\$l);\n    }\n}\n");

        $this->git('add -A');
        $this->git('-c user.email=x -c user.name=x commit -qm "the commit under review"');

        // Commit 2: the file MOVES. This is what makes a HEAD-pointing harness read the wrong tree.
        rename($this->repo . '/app/Http/Old.php', $this->repo . '/app/Http/New.php');
        $this->git('add -A');
        $this->git('-c user.email=x -c user.name=x commit -qm "later refactor moves the file"');
    }

    protected function tearDown(): void
    {
        if ($this->repo !== '' && is_dir($this->repo)) {
            exec('rm -rf ' . escapeshellarg($this->repo));
        }
    }

    public function testTheOldHarnessCannotSeeAFileThatHasSinceMoved(): void
    {
        // Reproduces the M18/M19 defect: --repo is HEAD, where app/Http/Old.php no longer exists.
        $run = $this->bundle($this->repo, $this->reviewedSha());

        self::assertStringContainsString(
            'unreadable path: app/Http/Old.php',
            $run['stderr'],
            'the defect being pinned: at HEAD the reviewed file is gone, so nothing is extracted'
        );
        self::assertSame([], $run['bundle']['items'], 'and the bundle is empty for the wrong reason');
    }

    public function testTheCorrectedHarnessResolvesAgainstTheCommitsOwnTree(): void
    {
        $worktree = $this->repo . '-wt';

        $this->git('worktree add --detach --quiet ' . escapeshellarg($worktree) . ' ' . $this->reviewedSha());

        try {
            $run = $this->bundle($worktree, $this->reviewedSha());

            self::assertStringNotContainsString('unreadable path', $run['stderr'], 'the file is there');
            self::assertNotSame([], $run['bundle']['items'], 'and the reference resolves');

            $members = array_map(
                static fn (array $i): ?string => $i['provenance']['member'] ?? null,
                $run['bundle']['items']
            );

            self::assertContains('total', $members, 'Calc::total is fetched from the tree as it was');
        } finally {
            $this->git('worktree remove --force ' . escapeshellarg($worktree));
        }
    }

    public function testTheCorrectionLeavesTheRepositoryClean(): void
    {
        // A harness that dirties the corpus would be worse than the defect it fixes.
        $worktree = $this->repo . '-wt2';

        $this->git('worktree add --detach --quiet ' . escapeshellarg($worktree) . ' ' . $this->reviewedSha());
        $this->git('worktree remove --force ' . escapeshellarg($worktree));

        self::assertSame('', trim($this->git('status --porcelain')), 'the corpus repository is untouched');
        $worktrees = array_values(array_filter(explode("\n", trim($this->git('worktree list')))));

        self::assertCount(1, $worktrees, 'no worktree is left behind');
    }

    // ---------------------------------------------------------------- helpers

    private function reviewedSha(): string
    {
        return trim($this->git('rev-parse HEAD~1'));
    }

    /**
     * @return array{bundle: array<string, mixed>, stderr: string}
     */
    private function bundle(string $repoRoot, string $sha): array
    {
        $root = dirname(__DIR__, 2);
        $diff = $this->repo . '/diff.patch';

        file_put_contents($diff, $this->git('diff ' . $sha . '^ ' . $sha));

        $process = proc_open(
            [
                PHP_BINARY, $root . '/bin/context-discover',
                '--diff', $diff, '--repo', $repoRoot, '--budget', '8000', '--format', 'json',
            ],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root
        );

        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $bundle = json_decode($stdout, true);

        self::assertIsArray($bundle, $stderr);

        return ['bundle' => $bundle, 'stderr' => $stderr];
    }

    private function git(string $args): string
    {
        return (string) shell_exec('git -C ' . escapeshellarg($this->repo) . ' ' . $args . ' 2>&1');
    }
}
