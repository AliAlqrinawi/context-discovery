<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * The process contract of `03-interfaces.md` §1, exercised as a process.
 *
 * `0` bundle produced (including an empty one) · `1` usage or input error · `2` repository
 * unreadable. This is the only test that drives `bin/context-discover` through argv and reads the
 * exit code, so it lives on its own. It used to sit inside the Phase 0 acceptance harness; when that
 * harness was retired (ADR-A025) this test was the part worth keeping, because it grades the process
 * contract against a throwaway repository rather than against an answer key nobody can supply.
 */
final class ProcessContractTest extends TestCase
{
    private string $repository = '';

    protected function setUp(): void
    {
        $this->repository = sys_get_temp_dir() . '/context-discovery-process-' . bin2hex(random_bytes(6));

        mkdir($this->repository . '/app/Services', 0o777, true);
        file_put_contents($this->repository . '/composer.json', '{"autoload":{"psr-4":{"App\\\\":"app/"}}}');
        file_put_contents(
            $this->repository . '/app/Services/Thing.php',
            "<?php\n\nnamespace App\\Services;\n\nclass Thing\n{\n    public function handle(): void\n    {\n    }\n}\n"
        );
        file_put_contents($this->repository . '/change.patch', implode("\n", [
            '--- a/app/Services/Thing.php',
            '+++ b/app/Services/Thing.php',
            '@@ -6,2 +6,3 @@',
            '    public function handle(): void',
            '    {',
            "+        Log::info('x');",
            // A second file the repository does not hold: the one input failure the tool works
            // around, reported on stderr as `unreadable path` (03-interfaces.md §1).
            '--- a/app/Services/Missing.php',
            '+++ b/app/Services/Missing.php',
            '@@ -1,0 +1,1 @@',
            '+<?php',
        ]) . "\n");
    }

    protected function tearDown(): void
    {
        if ($this->repository !== '' && is_dir($this->repository)) {
            exec('rm -rf ' . escapeshellarg($this->repository));
        }
    }

    public function testABundleProducedExitsZero(): void
    {
        self::assertSame(0, $this->invoke(['--diff', $this->diff(), '--repo', $this->repository, '--budget', '8000'])['exit']);
    }

    public function testAMissingBudgetIsAUsageErrorAndExitsOne(): void
    {
        // --budget is required and has no default (ADR-A008).
        self::assertSame(1, $this->invoke(['--diff', $this->diff(), '--repo', $this->repository])['exit']);
    }

    public function testAnUnreadableRepositoryRootExitsTwo(): void
    {
        self::assertSame(
            2,
            $this->invoke(['--diff', $this->diff(), '--repo', $this->repository . '/nowhere', '--budget', '8000'])['exit']
        );
    }

    public function testTheMarkdownProjectionAlsoExitsZero(): void
    {
        self::assertSame(
            0,
            $this->invoke(['--diff', $this->diff(), '--repo', $this->repository, '--budget', '8000', '--format', 'markdown'])['exit']
        );
    }

    public function testDiagnosticsGoToStderrAndTheBundleStaysParseable(): void
    {
        $run = $this->invoke(['--diff', $this->diff(), '--repo', $this->repository, '--budget', '8000']);

        self::assertSame(0, $run['exit'], 'an unreadable changed file is worked around, not fatal');
        self::assertIsArray(json_decode($run['stdout'], true), 'stdout is always a parseable bundle');
        self::assertStringContainsString('unreadable path: app/Services/Missing.php', $run['stderr']);
        self::assertStringNotContainsString('unreadable path', $run['stdout'], 'diagnostics never enter the bundle stream');
    }

    private function diff(): string
    {
        return $this->repository . '/change.patch';
    }

    /**
     * The real command, as a process — argv, wiring, both streams and the exit code.
     *
     * @param list<string> $arguments
     *
     * @return array{stdout:string,stderr:string,exit:int}
     */
    private function invoke(array $arguments): array
    {
        $root = dirname(__DIR__, 2);

        $process = proc_open(
            array_merge([PHP_BINARY, $root . '/bin/context-discover'], $arguments),
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root
        );

        self::assertIsResource($process, 'the command could not be started');

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['stdout' => $stdout, 'stderr' => $stderr, 'exit' => proc_close($process)];
    }
}
