<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use ContextDiscovery\Tests\Support\ReadsBundles;
use PHPUnit\Framework\TestCase;

/**
 * A `return` inside a callback is the callback's, not the method's.
 *
 * ```php
 * public function outer(): Collection
 * {
 *     return $this->items->map(function ($b) {
 *         return $b->relations->get();   // → first()
 *     });
 * }
 * ```
 *
 * `outer()` returns whatever `map()` returns — a Collection, before and after. Reading the callback's
 * finisher as the method's own produced the sentence *"the body of outer now returns a single value
 * or null"*, which is simply false, and sent `CallerResolver` after every caller of a method whose
 * contract never moved.
 *
 * Two shapes carry the same mistake and both are covered here: the callback with a body, where the
 * changed line sits in a nested scope, and the arrow function, where it sits on the method's own
 * line and only the *expression* nests.
 *
 * Diffs are produced by `git diff` so the hunk offsets are real, and each case asserts the whole
 * command's output rather than the extractor alone — a false claim that reached the bundle is what
 * mattered.
 */
final class ChangedReturnContractClosureScopeTest extends TestCase
{
    use ReadsBundles;

    private string $repo = '';

    protected function setUp(): void
    {
        $this->repo = sys_get_temp_dir() . '/m26-scope-' . bin2hex(random_bytes(6));

        mkdir($this->repo . '/app/Repositories', 0777, true);
        mkdir($this->repo . '/app/Actions', 0777, true);

        file_put_contents(
            $this->repo . '/composer.json',
            json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]])
        );

        // A caller for each member, so a wrong subject is visible as a wrongly fetched file.
        file_put_contents($this->repo . '/app/Actions/CallsOuter.php', <<<'PHP'
            <?php

            namespace App\Actions;

            class CallsOuter
            {
                public function execute(): Collection
                {
                    return $this->repository->outer();
                }
            }
            PHP);

        file_put_contents($this->repo . '/app/Actions/CallsDirect.php', <<<'PHP'
            <?php

            namespace App\Actions;

            class CallsDirect
            {
                public function execute(): Collection
                {
                    return $this->repository->direct();
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

    /**
     * The control. Without it, "no assertion" would be indistinguishable from a feature that stopped
     * working, and the honest way to disable a false positive is not to disable the move.
     */
    public function testAMethodsOwnReturnStillRaisesTheAssertion(): void
    {
        $this->commit(<<<'PHP'
                public function direct(): Collection
                {
                    return $this->query->get();
                }
            PHP);

        $this->rewrite('return $this->query->get();', 'return $this->query->first();');

        $bundle = $this->invoke($this->gitDiff());
        $items = $this->returnContractItems($bundle);

        self::assertNotSame([], $items, 'a method changing its own return is still caught');

        foreach ($items as $item) {
            self::assertStringContainsString('the body of direct ', $this->reasonOfItem($bundle, $item));
        }
    }

    public function testAReturnInsideACallbackRaisesNothingForTheSurroundingMethod(): void
    {
        $this->commit(<<<'PHP'
                public function outer(): Collection
                {
                    return $this->items->map(function ($branch) {
                        return $branch->relations->get();
                    });
                }
            PHP);

        $this->rewrite('return $branch->relations->get();', 'return $branch->relations->first();');

        self::assertSame(
            [],
            $this->returnContractItems($this->invoke($this->gitDiff())),
            'outer() still returns what map() returns, so nothing may be claimed about it'
        );
    }

    public function testAReturnTwoCallbacksDeepRaisesNothingEither(): void
    {
        $this->commit(<<<'PHP'
                public function outer(): Collection
                {
                    return $this->items->map(function ($branch) {
                        return collect()->each(function ($child) {
                            return $child->relations->get();
                        });
                    });
                }
            PHP);

        $this->rewrite('return $child->relations->get();', 'return $child->relations->first();');

        self::assertSame([], $this->returnContractItems($this->invoke($this->gitDiff())));
    }

    /**
     * The arrow function nests the *expression*, not the line: the changed text sits on the method's
     * own `return`. Only reading the terminal call at the top level of that expression separates
     * `map` — what actually leaves the method — from `first`.
     */
    public function testAnArrowFunctionCallbackOnTheMethodsOwnLineRaisesNothing(): void
    {
        $this->commit(<<<'PHP'
                public function outer(): Collection
                {
                    return $this->items->map(fn ($branch) => $branch->relations->get());
                }
            PHP);

        $this->rewrite(
            'return $this->items->map(fn ($branch) => $branch->relations->get());',
            'return $this->items->map(fn ($branch) => $branch->relations->first());'
        );

        self::assertSame([], $this->returnContractItems($this->invoke($this->gitDiff())));
    }

    /**
     * One hunk, two members: `alpha` loses its `return`, `beta` gains one. Before M28 this produced
     * *"the body of beta now returns a single value or null where it returned a collection"* —
     * false, because beta never returned a collection; alpha did. The removed return's member is a
     * fact the post-image tree cannot supply, so the parser now records where each removed line
     * sat and the extractor requires every recognised removed return to have left the member the
     * added one belongs to. It did not, so nothing is claimed (ADR-A023 limitations, M28).
     */
    public function testAReturnThatLeftAnotherMemberIsNotThisMembersChange(): void
    {
        $this->commit(<<<'PHP'
                public function outer(): Collection
                {
                    $this->log();

                    return $this->query->get();
                }

                public function direct(): mixed
                {
                    $this->log();
                }
            PHP);

        // alpha loses its return; beta gains one — five lines apart, one hunk at default context.
        $this->rewrite("        \$this->log();\n\n        return \$this->query->get();\n", "        \$this->log();\n");
        $this->rewrite("        \$this->log();\n    }\n}", "        \$this->log();\n\n        return \$this->query->first();\n    }\n}");

        $diff = $this->gitDiff();

        self::assertSame(1, substr_count($diff, "\n@@ "), 'the two members fall in one hunk');
        self::assertStringContainsString('-        return $this->query->get();', $diff);
        self::assertStringContainsString('+        return $this->query->first();', $diff);

        self::assertSame([], $this->returnContractItems($this->invoke($diff)), 'no false claim about direct()');
    }

    /**
     * A file holding both shapes: the changed return is `direct()`'s own, while `outer()` sitting
     * above it contains a callback whose text is nearly identical. Attribution must land on
     * `direct()` and fetch its caller, not `outer()`'s.
     */
    public function testWithBothShapesInOneFileTheOwningMethodIsNamed(): void
    {
        $this->commit(<<<'PHP'
                public function outer(): Collection
                {
                    return $this->items->map(function ($branch) {
                        return $branch->relations->get();
                    });
                }

                public function direct(): Collection
                {
                    return $this->query->get();
                }
            PHP);

        $this->rewrite('return $this->query->get();', 'return $this->query->first();');

        $bundle = $this->invoke($this->gitDiff());
        $items = $this->returnContractItems($bundle);

        self::assertNotSame([], $items, 'the method that did change its own return is caught');

        foreach ($items as $item) {
            self::assertStringContainsString('the body of direct ', $this->reasonOfItem($bundle, $item), 'named for direct');
            self::assertStringNotContainsString('the body of outer ', $this->reasonOfItem($bundle, $item), 'never for outer');
        }

        $paths = array_column(array_column($items, 'provenance'), 'path');

        self::assertContains('app/Actions/CallsDirect.php', $paths, "direct()'s caller is fetched");
        self::assertNotContains('app/Actions/CallsOuter.php', $paths, "outer()'s caller is not");
    }

    // ---------------------------------------------------------------- fixtures

    private function commit(string $members): void
    {
        file_put_contents($this->repo . '/app/Repositories/BranchRepository.php', <<<PHP
            <?php

            namespace App\\Repositories;

            class BranchRepository
            {
            {$members}
            }
            PHP);

        $this->git('init -q');
        $this->git('add -A');
        $this->git('-c user.email=t@example.com -c user.name=Test commit -qm base');
    }

    private function rewrite(string $from, string $to): void
    {
        $path = $this->repo . '/app/Repositories/BranchRepository.php';
        $text = (string) file_get_contents($path);

        self::assertStringContainsString($from, $text, 'the fixture still holds the line being changed');

        file_put_contents($path, str_replace($from, $to, $text));
    }

    /**
     * The working tree is left changed on purpose: `--repo` is the post-image tree
     * (`03-interfaces.md` §1, ADR-A023).
     */
    private function gitDiff(): string
    {
        return $this->git('diff');
    }

    /**
     * @param array<string, mixed> $bundle
     *
     * @return list<array<string, mixed>>
     */
    private function returnContractItems(array $bundle): array
    {
        return $this->itemsOfKind($bundle, 'changed_return_contract');
    }

    private function git(string $arguments): string
    {
        exec(sprintf('cd %s && git %s 2>&1', escapeshellarg($this->repo), $arguments), $output, $status);

        if ($status !== 0 && str_starts_with($arguments, 'init')) {
            self::markTestSkipped('git is not available in this environment');
        }

        self::assertSame(0, $status, sprintf('git %s failed: %s', $arguments, implode("\n", $output)));

        return implode("\n", $output) . "\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function invoke(string $diff): array
    {
        $root = dirname(__DIR__, 2);
        $path = $this->repo . '/change.diff';

        file_put_contents($path, $diff);

        $process = proc_open(
            [PHP_BINARY, $root . '/bin/context-discover', '--diff', $path, '--repo', $this->repo,
             '--budget', '8000', '--format', 'json'],
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
