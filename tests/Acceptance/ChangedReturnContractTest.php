<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * The whole command, on a body-only change that breaks a return contract.
 *
 * The unit test proves the assertion is raised. This proves it survives the rest of the pipeline —
 * lever policy, `CallerResolver`, assembly, ordering, budget — and arrives as call-site context the
 * reviewer can act on. An assertion that never reaches a resolver would be worthless.
 *
 * The repository is built here rather than borrowed, so the test needs no private fixture and can
 * assert exactly which call sites should and should not appear.
 */
final class ChangedReturnContractTest extends TestCase
{
    private string $repo = '';

    protected function setUp(): void
    {
        $this->repo = sys_get_temp_dir() . '/m26-' . bin2hex(random_bytes(6));

        mkdir($this->repo . '/app/Repositories', 0777, true);
        mkdir($this->repo . '/app/Actions', 0777, true);
        mkdir($this->repo . '/app/Http/Controllers', 0777, true);

        file_put_contents($this->repo . '/composer.json', json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]]));

        // Each test writes its own post-image below: `--repo` is the tree as the diff leaves it, not
        // the tree before the change (`03-interfaces.md` §1, ADR-A023).
        $this->writeRepository("Branch::orderBy('order')", '$query->get()');

        // A caller of the changed member — this must appear in the bundle.
        file_put_contents($this->repo . '/app/Actions/GetBranchesAction.php', <<<'PHP'
            <?php

            namespace App\Actions;

            class GetBranchesAction
            {
                public function execute(?bool $showInFooter = null): Collection
                {
                    return $this->repository->getAll($showInFooter);
                }
            }
            PHP);

        // An unrelated member of the same name family — this must NOT appear.
        file_put_contents($this->repo . '/app/Http/Controllers/UnrelatedController.php', <<<'PHP'
            <?php

            namespace App\Http\Controllers;

            class UnrelatedController
            {
                public function index(): array
                {
                    return $this->service->fetchEverything();
                }
            }
            PHP);
    }

    /**
     * The repository file as the reviewed tree holds it. Line 11 is the return, which is the line
     * every hunk header below is written against.
     */
    private function writeRepository(string $query, string $return): void
    {
        file_put_contents($this->repo . '/app/Repositories/BranchRepository.php', <<<PHP
            <?php

            namespace App\Repositories;

            class BranchRepository
            {
                public function getAll(?bool \$showInFooter = null): Collection
                {
                    \$query = {$query};

                    return {$return};
                }
            }
            PHP);
    }

    protected function tearDown(): void
    {
        if ($this->repo !== '' && is_dir($this->repo)) {
            exec('rm -rf ' . escapeshellarg($this->repo));
        }
    }

    public function testACardinalityChangeReachesTheCallerResolverAndYieldsCallSites(): void
    {
        $this->writeRepository("Branch::orderBy('order')", '$query->first()');

        $bundle = $this->invoke($this->cardinalityDiff());

        $kinds = array_column($bundle['items'], 'assertion_kind');

        self::assertContains('changed_return_contract', $kinds, 'the assertion is raised');

        $callSites = array_values(array_filter(
            $bundle['items'],
            static fn (array $i): bool => $i['assertion_kind'] === 'changed_return_contract'
        ));

        self::assertNotSame([], $callSites, 'and it reached CallerResolver rather than stopping at extraction');

        $paths = array_column(array_column($callSites, 'provenance'), 'path');

        self::assertContains('app/Actions/GetBranchesAction.php', $paths, 'the real downstream caller is fetched');
        self::assertNotContains(
            'app/Http/Controllers/UnrelatedController.php',
            $paths,
            'and a file that never calls the member is not'
        );

        self::assertStringContainsString(
            'getAll',
            $callSites[0]['reason'],
            'the reason names the member whose contract moved'
        );
    }

    public function testAnOrdinaryBodyChangeProducesNoCallerSearch(): void
    {
        $this->writeRepository("Branch::orderBy('position')", '$query->get()');

        $bundle = $this->invoke($this->ordinaryDiff());

        self::assertNotContains(
            'changed_return_contract',
            array_column($bundle['items'], 'assertion_kind'),
            'renaming a helper call is not a contract change and must not trigger a caller search'
        );
    }

    public function testTheBudgetStillBounds(): void
    {
        $this->writeRepository("Branch::orderBy('order')", '$query->first()');

        // The whole bundle costs 25 tokens here, so 15 forces exactly one drop.
        $bundle = $this->invoke($this->cardinalityDiff(), 15);

        self::assertLessThanOrEqual(15, $bundle['used_tokens'], 'the budget is enforced for the new kind too');
        self::assertNotSame([], $bundle['dropped'], 'and the drop is recorded rather than silent');
        self::assertArrayHasKey('reason', $bundle['dropped'][0], 'carrying the reason the item was included');
    }

    // ---------------------------------------------------------------- fixtures

    private function cardinalityDiff(): string
    {
        return <<<'DIFF'
            diff --git a/app/Repositories/BranchRepository.php b/app/Repositories/BranchRepository.php
            --- a/app/Repositories/BranchRepository.php
            +++ b/app/Repositories/BranchRepository.php
            @@ -9,5 +9,5 @@ class BranchRepository
                     $query = Branch::orderBy('order');
             
            -        return $query->get();
            +        return $query->first();
                 }
             }
            DIFF;
    }

    private function ordinaryDiff(): string
    {
        return <<<'DIFF'
            diff --git a/app/Repositories/BranchRepository.php b/app/Repositories/BranchRepository.php
            --- a/app/Repositories/BranchRepository.php
            +++ b/app/Repositories/BranchRepository.php
            @@ -7,5 +7,5 @@ class BranchRepository
                 {
            -        $query = Branch::orderBy('order');
            +        $query = Branch::orderBy('position');
             
                     return $query->get();
                 }
            DIFF;
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
