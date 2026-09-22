<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * M29 · the experiment harness must give the tool a `vendor/` it can read — and this test runs
 * the harness script itself, not a re-enactment of it.
 *
 * **The defect this pins.** From M20 through M23 `bundle-at-commit.sh` symlinked the corpus
 * checkout's `vendor/` into the detached worktree. `LocalSourceRepository` resolves every read
 * through `realpath()` and refuses one that lands outside `--repo`, so the link was refused whole:
 * the tool ran as though no dependency were installed, every dependency class it met became a
 * `missing PSR-4 entry` diagnostic and a `could not be resolved on disk` flag, and nothing said so.
 * A symlinked run is byte-identical — bundle and stderr — to a run with no `vendor/` at all
 * (`fixtures/experiment-29`, 7 of 7 commits). The harness now copies the directory (ADR-A026).
 *
 * **Why a second harness test.** `HarnessResolvesAtCommitTreeTest` adds its own worktree and never
 * executes the script, so it pinned the *idea* of resolving at the commit's tree and left the
 * script free to be wrong about anything else. Four milestones ran through the link without a
 * test noticing. This one invokes `bundle-at-commit.sh` as the milestones did and reads what it
 * wrote.
 *
 * The test is self-contained: a throwaway repository whose `composer.json` maps `App\`, whose
 * gitignored `vendor/` holds Composer's generated map and one PSR-4 package, and whose reviewed
 * commit names a member of that package. With a readable `vendor/` the reference is **settled** as a
 * dependency member (ADR-A013): a cited diagnostic, no flag. Without one it is flagged. The two
 * outcomes differ on stderr and in the bundle, and the test asserts both.
 */
final class HarnessPlacesAReadableVendorTest extends TestCase
{
    private const HARNESS = 'tests/Acceptance/fixtures/experiment-20/harness/bundle-at-commit.sh';

    private const FLAG = 'could not be resolved on disk';

    private string $repo = '';

    private string $out = '';

    protected function setUp(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $this->repo = sys_get_temp_dir() . '/m29-harness-' . $suffix;
        $this->out = sys_get_temp_dir() . '/m29-out-' . $suffix;

        mkdir($this->repo . '/app/Http', 0777, true);
        mkdir($this->repo . '/vendor/composer', 0777, true);
        mkdir($this->repo . '/vendor/acme/lib/src', 0777, true);
        mkdir($this->out);

        file_put_contents($this->repo . '/composer.json', json_encode([
            'autoload' => ['psr-4' => ['App\\' => 'app/']],
        ]));

        // vendor/ is not committed, as in every real corpus: the worktree will not have it unless
        // the harness puts it there.
        file_put_contents($this->repo . '/.gitignore', "/vendor/\n");

        // The shape Composer writes and ComposerPsr4ClassLocator parses (ADR-A014): string
        // literals, $vendorDir and $baseDir, nothing else.
        file_put_contents($this->repo . '/vendor/composer/autoload_psr4.php', "<?php\n\n"
            . "\$vendorDir = dirname(__DIR__);\n\$baseDir = dirname(\$vendorDir);\n\n"
            . "return array(\n"
            . "    'Acme\\\\Lib\\\\' => array(\$vendorDir . '/acme/lib/src'),\n"
            . "    'App\\\\' => array(\$baseDir . '/app'),\n"
            . ");\n");

        file_put_contents($this->repo . '/vendor/acme/lib/src/Thing.php', "<?php\n\nnamespace Acme\\Lib;\n\n"
            . "final class Thing\n{\n    public static function make(array \$lines): int\n    {\n"
            . "        return array_sum(\$lines);\n    }\n}\n");

        file_put_contents($this->repo . '/app/Http/Consumer.php', "<?php\n\nnamespace App\\Http;\n\nclass Consumer\n{\n}\n");

        $this->git('init -q');
        $this->git('add -A');
        $this->git('-c user.email=x -c user.name=x commit -qm base');

        // The commit under review names a member of the installed package.
        file_put_contents($this->repo . '/app/Http/Consumer.php', "<?php\n\nnamespace App\\Http;\n\n"
            . "use Acme\\Lib\\Thing;\n\nclass Consumer\n{\n    public function run(array \$l): int\n    {\n"
            . "        return Thing::make(\$l);\n    }\n}\n");

        $this->git('add -A');
        $this->git('-c user.email=x -c user.name=x commit -qm "the commit under review"');
    }

    protected function tearDown(): void
    {
        foreach ([$this->repo, $this->out] as $directory) {
            if ($directory !== '' && is_dir($directory)) {
                exec('rm -rf ' . escapeshellarg($directory));
            }
        }
    }

    public function testTheHarnessGivesTheToolAVendorItCanRead(): void
    {
        $run = $this->runHarness();

        self::assertSame('', trim($this->git('status --porcelain')), 'the corpus repository is untouched');
        self::assertCount(1, $this->worktrees(), 'no worktree — and no copied vendor/ — is left behind');

        self::assertStringNotContainsString('missing PSR-4 entry', $run['stderr'], 'the package was placed');
        self::assertStringContainsString(
            'Acme\Lib\Thing::make declared at vendor/acme/lib/src/Thing.php',
            $run['stderr'],
            'the reference is settled against the copied vendor/, with its location cited (ADR-A013)'
        );

        self::assertSame(
            [],
            $this->flaggedPayloads($run['bundle']),
            'a readable vendor/ produces no "could not be resolved on disk" flag. If this fails, the '
            . 'harness has stopped placing a real directory — a symlink is refused whole.'
        );
    }

    public function testASymlinkedVendorIsRefusedWholeWhichIsWhyTheHarnessMustCopy(): void
    {
        // The inverse, done by hand: the M20–M23 condition. It pins the failure mode the harness
        // test above guards against, so a green suite shows both what the script does and why.
        $worktree = $this->repo . '-wt';

        $this->git('worktree add --detach --quiet ' . escapeshellarg($worktree) . ' HEAD');
        symlink($this->repo . '/vendor', $worktree . '/vendor');

        try {
            $run = $this->discover($worktree);

            self::assertStringContainsString('missing PSR-4 entry: Acme\Lib\Thing::make', $run['stderr']);
            self::assertCount(1, $this->flaggedPayloads($run['bundle']), 'the link is invisible to the tool');
        } finally {
            $this->git('worktree remove --force ' . escapeshellarg($worktree));
        }
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Invokes `bundle-at-commit.sh` exactly as the milestones did, and reads the files it wrote.
     *
     * @return array{bundle: array<string, mixed>, stderr: string}
     */
    private function runHarness(): array
    {
        $root = dirname(__DIR__, 2);
        $prefix = $this->out . '/reviewed';

        $process = proc_open(
            ['sh', $root . '/' . self::HARNESS, $this->repo, trim($this->git('rev-parse HEAD')), $root, $prefix],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root
        );

        self::assertIsResource($process);

        $noise = (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), 'the harness completed: ' . $noise);
        self::assertFileExists($prefix . '.bundle.json');
        self::assertFileExists($prefix . '.stderr');

        $bundle = json_decode((string) file_get_contents($prefix . '.bundle.json'), true);

        self::assertIsArray($bundle);

        return ['bundle' => $bundle, 'stderr' => (string) file_get_contents($prefix . '.stderr')];
    }

    /**
     * @return array{bundle: array<string, mixed>, stderr: string}
     */
    private function discover(string $repoRoot): array
    {
        $root = dirname(__DIR__, 2);
        $diff = $this->out . '/diff.patch';

        file_put_contents($diff, $this->git('diff HEAD^ HEAD'));

        $process = proc_open(
            [PHP_BINARY, $root . '/bin/context-discover', '--diff', $diff, '--repo', $repoRoot, '--budget', '8000'],
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

    /**
     * @param array<string, mixed> $bundle
     *
     * @return list<string>
     */
    private function flaggedPayloads(array $bundle): array
    {
        $payloads = [];

        foreach ($bundle['items'] ?? [] as $item) {
            if (str_contains((string) $item['payload'], self::FLAG)) {
                $payloads[] = (string) $item['payload'];
            }
        }

        return $payloads;
    }

    /**
     * @return list<string>
     */
    private function worktrees(): array
    {
        return array_values(array_filter(explode("\n", trim($this->git('worktree list')))));
    }

    private function git(string $args): string
    {
        return (string) shell_exec('git -C ' . escapeshellarg($this->repo) . ' ' . $args . ' 2>&1');
    }
}
