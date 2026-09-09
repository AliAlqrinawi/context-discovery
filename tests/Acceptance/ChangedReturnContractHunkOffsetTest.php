<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * The member a return-contract assertion names must be the member the return is *in*.
 *
 * `ChangedRegion::firstLine` is where the **hunk** starts, and git opens a hunk with context lines.
 * Reading it as the changed line was wrong in two ways that a hand-written fixture cannot expose,
 * because a hand-written fixture chooses its own hunk header:
 *
 * - the hunk starts on the blank line *between* two members, no member encloses it, and the
 *   assertion is silently never raised — a false negative on the plainest form of the bug;
 * - the hunk starts inside the member *above* the one that changed, and the assertion names that
 *   member instead, sending `CallerResolver` after callers of an unrelated method.
 *
 * So both diffs here are produced by `git diff` itself. The hunk headers are git's, not the test's,
 * which is the whole point: the offsets are the ones a real review would carry.
 *
 * Each case asserts the subject twice over — the reason names the right member, and the call sites
 * fetched are that member's and not its neighbour's. The second assertion is what would have caught
 * the wrong-subject mode, since a wrong subject still produces a well-formed assertion.
 */
final class ChangedReturnContractHunkOffsetTest extends TestCase
{
    private string $repo = '';

    protected function setUp(): void
    {
        $this->repo = sys_get_temp_dir() . '/m26-offset-' . bin2hex(random_bytes(6));

        mkdir($this->repo . '/app/Repositories', 0777, true);
        mkdir($this->repo . '/app/Actions', 0777, true);

        file_put_contents(
            $this->repo . '/composer.json',
            json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]])
        );

        // Two adjacent members. Only `beta` will change; `alpha` is the neighbour that must never
        // be named, and it returns the same `get()` so nothing but the line number tells them apart.
        file_put_contents($this->repo . '/app/Repositories/BranchRepository.php', <<<'PHP'
            <?php

            namespace App\Repositories;

            class BranchRepository
            {
                public function alpha(): Collection
                {
                    return $query->get();
                }

                public function beta(): Collection
                {
                    return $query->get();
                }
            }
            PHP);

        file_put_contents($this->repo . '/app/Actions/CallsBeta.php', <<<'PHP'
            <?php

            namespace App\Actions;

            class CallsBeta
            {
                public function execute(): Collection
                {
                    return $this->repository->beta();
                }
            }
            PHP);

        file_put_contents($this->repo . '/app/Actions/CallsAlpha.php', <<<'PHP'
            <?php

            namespace App\Actions;

            class CallsAlpha
            {
                public function execute(): Collection
                {
                    return $this->repository->alpha();
                }
            }
            PHP);

        $this->git('init -q');
        $this->git('add -A');
        $this->git('-c user.email=t@example.com -c user.name=Test commit -qm base');
    }

    protected function tearDown(): void
    {
        if ($this->repo !== '' && is_dir($this->repo)) {
            exec('rm -rf ' . escapeshellarg($this->repo));
        }
    }

    /**
     * Test A. Nothing moves except `beta`'s return, so git's own hunk opens on the blank line
     * between the two members — a line no member encloses. Before the fix this produced nothing.
     */
    public function testAHunkStartingBetweenMembersStillNamesTheChangedMember(): void
    {
        $this->rewriteBetaReturn();

        $diff = $this->gitDiff();

        self::assertStringContainsString(
            '@@ -11,6 +11,6 @@',
            $diff,
            'git opens the hunk at line 11, the blank line between alpha and beta'
        );

        $this->assertNamesBeta($this->invoke($diff));
    }

    /**
     * Test B. A member inserted just above pulls the hunk's start up into `alpha`'s body, so the
     * first line of the hunk is enclosed by the wrong member. Before the fix `alpha` was named.
     */
    public function testAHunkStartingInsideThePreviousMemberStillNamesTheChangedMember(): void
    {
        $this->insertMemberAboveBeta();
        $this->rewriteBetaReturn();

        $diff = $this->gitDiff();

        self::assertStringContainsString(
            '@@ -9,8 +9,13 @@',
            $diff,
            "git opens the hunk at line 9, inside alpha's body"
        );

        $this->assertNamesBeta($this->invoke($diff));
    }

    // ---------------------------------------------------------------- assertions

    /**
     * @param array<string, mixed> $bundle
     */
    private function assertNamesBeta(array $bundle): void
    {
        $items = array_values(array_filter(
            $bundle['items'],
            static fn (array $i): bool => $i['assertion_kind'] === 'changed_return_contract'
        ));

        // Not zero: the between-members hunk used to produce nothing at all.
        self::assertNotSame([], $items, 'the assertion is raised');

        // One subject, whatever the call-site count: `CallerResolver` legitimately returns several
        // sites per assertion, but every one of them must belong to the member that changed.
        foreach ($items as $item) {
            self::assertStringContainsString('the body of beta ', $item['reason'], 'the subject is beta');
            self::assertStringNotContainsString(
                'the body of alpha ',
                $item['reason'],
                'and never the neighbouring member the hunk happens to start in'
            );
        }

        $paths = array_column(array_column($items, 'provenance'), 'path');

        self::assertContains('app/Actions/CallsBeta.php', $paths, "beta's caller is fetched");
        self::assertNotContains(
            'app/Actions/CallsAlpha.php',
            $paths,
            "and alpha's caller is not — a wrong subject would have fetched exactly this file"
        );
    }

    // ---------------------------------------------------------------- fixtures

    private function rewriteBetaReturn(): void
    {
        $path = $this->repo . '/app/Repositories/BranchRepository.php';
        $text = (string) file_get_contents($path);

        $needle = "public function beta(): Collection\n    {\n        return \$query->get();";
        $replacement = "public function beta(): Collection\n    {\n        return \$query->first();";

        self::assertStringContainsString($needle, $text, 'the fixture still holds beta as written');

        file_put_contents($path, str_replace($needle, $replacement, $text));
    }

    private function insertMemberAboveBeta(): void
    {
        $path = $this->repo . '/app/Repositories/BranchRepository.php';
        $text = (string) file_get_contents($path);

        $inserted = "    public function inserted(): void\n    {\n        \$noop = 1;\n    }\n\n"
            . "    public function beta(): Collection";

        file_put_contents($path, str_replace("    public function beta(): Collection", $inserted, $text));
    }

    private function gitDiff(): string
    {
        // The working tree is deliberately left changed: `--repo` must be the post-image tree
        // (`03-interfaces.md` §1, ADR-A023), which is what makes the added line's own number
        // recoverable.
        return $this->git('diff');
    }

    private function git(string $arguments): string
    {
        exec(
            sprintf('cd %s && git %s 2>&1', escapeshellarg($this->repo), $arguments),
            $output,
            $status
        );

        if ($status !== 0 && str_starts_with($arguments, 'init')) {
            self::markTestSkipped('git is not available in this environment');
        }

        self::assertSame(0, $status, sprintf('git %s failed: %s', $arguments, implode("\n", $output)));

        return implode("\n", $output) . "\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function invoke(string $diff, int $budget = 8000): array
    {
        $root = dirname(__DIR__, 2);
        $path = $this->repo . '/change.diff';

        file_put_contents($path, $diff);

        $process = proc_open(
            [PHP_BINARY, $root . '/bin/context-discover', '--diff', $path, '--repo', $this->repo,
             '--budget', (string) $budget, '--format', 'json'],
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

        return $bundle;
    }
}
