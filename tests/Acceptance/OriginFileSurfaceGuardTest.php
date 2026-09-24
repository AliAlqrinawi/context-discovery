<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * ADR-A020 addendum (2026-09-24) · the surface fallback never targets the assertion's own origin
 * file — end to end, through the binary.
 *
 * The synthetic case, because no recorded input contains it: a **modified** file that imports
 * its own fully-qualified name and, in the changed region, calls `Self::undeclared(`. Form 1
 * emits `App\Models\Self::undeclared`; the locator places it at the changed file; the member is
 * not declared; the flag fires (P10). Without the fourth condition the fallback would then fetch
 * every unchanged member of the same file — `fillable`, `casts`, `scopeActive` — as "surface".
 * A created file would be filtered by ADR-A019; this one is modified, so only the guard stands
 * between the flag and the file's own siblings.
 */
final class OriginFileSurfaceGuardTest extends TestCase
{
    private string $repo = '';

    protected function setUp(): void
    {
        $this->repo = sys_get_temp_dir() . '/origin-guard-' . bin2hex(random_bytes(6));
        mkdir($this->repo . '/app/Models', 0777, true);

        file_put_contents($this->repo . '/composer.json', json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]]));
        file_put_contents($this->repo . '/app/Models/Self.php', implode("\n", [
            '<?php',
            '',
            'namespace App\Models;',
            '',
            'use App\Models\Self;',
            '',
            'class Self',
            '{',
            '    protected $fillable = [\'name\'];',
            '',
            '    protected $casts = [\'active\' => \'bool\'];',
            '',
            '    public function scopeActive($query)',
            '    {',
            '        return $query->where(\'active\', true);',
            '    }',
            '',
            '    public function run(): void',
            '    {',
            '        Self::undeclared();',
            '    }',
            '}',
            '',
        ]));
    }

    protected function tearDown(): void
    {
        if ($this->repo !== '' && is_dir($this->repo)) {
            exec('rm -rf ' . escapeshellarg($this->repo));
        }
    }

    public function testTheFlagFiresAndNoSurfaceOfTheOriginFileFollows(): void
    {
        // A modified file: only run() is in the changed region; the three members above it are
        // exactly what the fallback would have fetched. The diff is git's own, from a two-commit
        // history, so the hunk is what the engine meets in practice.
        $diff = $this->gitDiffAddingRun();

        [$bundle, $stderr] = $this->discover($diff);

        $flagged = array_values(array_filter($bundle['items'], static fn (array $i): bool => $i['lever'] === 'flagged'));
        $fetched = array_values(array_filter($bundle['items'], static fn (array $i): bool => $i['lever'] === 'fetched'));

        self::assertCount(1, $flagged, 'the unresolved member is flagged (P10)');
        self::assertStringContainsString('could not be resolved on disk', $flagged[0]['payload']);
        self::assertSame('app/Models/Self.php', $flagged[0]['provenance']['path']);
        self::assertStringContainsString('unresolved named_reference: App\Models\Self::undeclared', $stderr);

        self::assertSame(
            [],
            array_map(static fn (array $i): string => (string) ($i['provenance']['member'] ?? ''), $fetched),
            'no surface of the origin file follows the flag - fillable, casts and scopeActive stay out'
        );
    }

    /**
     * Commit the file without run(), then with it, and take git's diff between the two - the
     * checkout is left at the post-image, as the contract requires (03-interfaces.md §1).
     */
    private function gitDiffAddingRun(): string
    {
        $git = fn (string $args): string => (string) shell_exec('git -C ' . escapeshellarg($this->repo) . ' ' . $args . ' 2>&1');
        $withRun = (string) file_get_contents($this->repo . '/app/Models/Self.php');
        $withoutRun = preg_replace('/\n    public function run\(\): void\n    \{\n        Self::undeclared\(\);\n    \}\n/', "\n", $withRun);

        $git('init -q');
        file_put_contents($this->repo . '/app/Models/Self.php', $withoutRun);
        $git('add -A');
        $git('-c user.email=x -c user.name=x commit -qm before');
        file_put_contents($this->repo . '/app/Models/Self.php', $withRun);
        $git('add -A');
        $git('-c user.email=x -c user.name=x commit -qm "adds run()"');

        return $git('diff HEAD~1 HEAD -- app/Models/Self.php');
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function discover(string $diff): array
    {
        $root = dirname(__DIR__, 2);
        $path = $this->repo . '/change.diff';
        file_put_contents($path, $diff);

        $process = proc_open(
            [PHP_BINARY, $root . '/bin/context-discover', '--diff', $path, '--repo', $this->repo, '--budget', '8000', '--format', 'json'],
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

        return [$bundle, $stderr];
    }
}
