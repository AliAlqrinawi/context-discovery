<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * ADR-A028 / ADR-A029 · option B, end to end through the binary.
 *
 * The three outcomes of the fourth form, each on a synthetic repository shaped like the recorded
 * cases (E5.4, H.3, H.15):
 *
 * - **S1** — the member is declared in a project ancestor: one `flagged` item whose payload is
 *   the templated statement, a stderr line, and **no surface** of the origin file behind it.
 * - **S2** — the ancestry leaves project code first: a stderr line and **no item**.
 * - **null** — the ancestry ends in project code without the member: today's
 *   `unresolved-reference` flag, unchanged, because it is exactly true.
 *
 * Every diff is git's own, from a two-commit history, so the hunk is what the engine meets.
 */
final class InheritedMemberPipelineTest extends TestCase
{
    private string $repo = '';

    protected function setUp(): void
    {
        $this->repo = sys_get_temp_dir() . '/inherited-' . bin2hex(random_bytes(6));
        mkdir($this->repo . '/app/Http/Controllers', 0777, true);
        mkdir($this->repo . '/app/Traits', 0777, true);
        mkdir($this->repo . '/tests/Feature', 0777, true);
        mkdir($this->repo . '/vendor/acme/lib/src', 0777, true);

        file_put_contents($this->repo . '/composer.json', json_encode([
            'autoload' => ['psr-4' => ['App\\' => 'app/', 'Tests\\' => 'tests/']],
        ]));
        // Composer's generated map, so the dependency is *placeable*: a walk that entered
        // vendor/ would find the member. That it does not is the point of the S2 case.
        mkdir($this->repo . '/vendor/composer', 0777, true);
        file_put_contents($this->repo . '/vendor/composer/autoload_psr4.php', implode("\n", [
            '<?php',
            '',
            '$vendorDir = dirname(__DIR__);',
            '$baseDir = dirname($vendorDir);',
            '',
            'return array(',
            "    'Acme\\\\Lib\\\\' => array(\$vendorDir . '/acme/lib/src'),",
            ');',
            '',
        ]));

        file_put_contents($this->repo . '/app/Traits/ApiResponse.php', implode("\n", [
            '<?php',
            '',
            'namespace App\Traits;',
            '',
            'trait ApiResponse',
            '{',
            '    protected function success($data): array',
            '    {',
            '        return [\'ok\' => true, \'data\' => $data];',
            '    }',
            '}',
            '',
        ]));
        file_put_contents($this->repo . '/app/Http/Controllers/Controller.php', implode("\n", [
            '<?php',
            '',
            'namespace App\Http\Controllers;',
            '',
            'use App\Traits\ApiResponse;',
            '',
            'abstract class Controller',
            '{',
            '    use ApiResponse;',
            '}',
            '',
        ]));
        file_put_contents($this->repo . '/tests/TestCase.php', implode("\n", [
            '<?php',
            '',
            'namespace Tests;',
            '',
            'abstract class TestCase extends \Acme\Lib\BaseTestCase',
            '{',
            '}',
            '',
        ]));
        // The dependency is present on disk, so a walk that entered vendor/ would find the member.
        file_put_contents($this->repo . '/vendor/acme/lib/src/BaseTestCase.php', implode("\n", [
            '<?php',
            '',
            'namespace Acme\Lib;',
            '',
            'abstract class BaseTestCase',
            '{',
            '    public function getJson(string $uri): void',
            '    {',
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

    public function testS1OneHopATraitOnTheClassItself(): void
    {
        // E5.4's shape: the changed controller uses the trait directly.
        [$bundle, $stderr] = $this->discover('app/Http/Controllers/MenuPdfController.php', [
            '<?php',
            '',
            'namespace App\Http\Controllers;',
            '',
            'use App\Traits\ApiResponse;',
            '',
            'class MenuPdfController',
            '{',
            '    use ApiResponse;',
            '',
            '    public function unchanged(): int',
            '    {',
            '        return 1;',
            '    }',
            '}',
            '',
        ], "    public function show()\n    {\n        return \$this->success(['pdf' => true]);\n    }\n");

        $flagged = $this->items($bundle, 'flagged');

        self::assertCount(1, $flagged);
        self::assertSame(
            'ASSUMPTION: success() is not declared in MenuPdfController; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:7, used by the class itself; body not fetched, contract unverified',
            $flagged[0]['payload']
        );
        self::assertSame('App\Http\Controllers\MenuPdfController::success', $this->subjectOf($bundle, $flagged[0]));
        self::assertStringContainsString(
            'inherited member: App\Http\Controllers\MenuPdfController::success declared at app/Traits/ApiResponse.php:7 in trait App\Traits\ApiResponse; body not fetched',
            $stderr
        );
        self::assertSame([], $this->items($bundle, 'fetched'), 'no surface of the origin file, and no body of the trait');
        self::assertStringNotContainsString('could not be resolved on disk', $stderr . json_encode($bundle), 'the false sentence is gone');
    }

    public function testS1TwoHopsATraitOnTheParent(): void
    {
        // H.3's shape: the changed controller extends Controller, which uses the trait.
        [$bundle, $stderr] = $this->discover('app/Http/Controllers/MenuController.php', [
            '<?php',
            '',
            'namespace App\Http\Controllers;',
            '',
            'class MenuController extends Controller',
            '{',
            '    public function unchanged(): int',
            '    {',
            '        return 1;',
            '    }',
            '}',
            '',
        ], "    public function index()\n    {\n        return \$this->success([]);\n    }\n");

        $flagged = $this->items($bundle, 'flagged');

        self::assertCount(1, $flagged);
        self::assertSame(
            'ASSUMPTION: success() is not declared in MenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:7, used by that parent; body not fetched, contract unverified',
            $flagged[0]['payload']
        );
        self::assertSame([], $this->items($bundle, 'fetched'));
        self::assertStringContainsString('in trait App\Traits\ApiResponse; body not fetched', $stderr);
    }

    public function testS2TheAncestryLeavesProjectCodeYieldsNoItem(): void
    {
        // H.15's shape: a test case whose ancestry continues into a dependency that declares the
        // member. Settled negative: stderr only, nothing in the bundle, vendor/ never opened.
        [$bundle, $stderr] = $this->discover('tests/Feature/MenuPdfTest.php', [
            '<?php',
            '',
            'namespace Tests\Feature;',
            '',
            'use Tests\TestCase;',
            '',
            'class MenuPdfTest extends TestCase',
            '{',
            '    public function unchanged(): int',
            '    {',
            '        return 1;',
            '    }',
            '}',
            '',
        ], "    public function test_it_renders(): void\n    {\n        \$this->getJson('/menu.pdf');\n    }\n");

        self::assertSame([], $bundle['items'], 'S2 is a diagnostic, not an item (ADR-A028 §4)');
        self::assertSame([], $bundle['assertions'], 'settled on stderr, off the bundle - as ADR-A013 dependency members are');
        self::assertSame([], $bundle['diagnostics'], 'not mirrored into diagnostics[]: the enum is unchanged');
        self::assertStringContainsString(
            'inherited member unresolved: Tests\Feature\MenuPdfTest::getJson; walked Tests\TestCase; continues into a dependency, which was not walked (Acme\Lib\BaseTestCase)',
            $stderr
        );
    }

    public function testNullTheAncestryEndsInProjectCodeKeepsTodaysFlag(): void
    {
        // A typo, or a magic method: nothing in project code declares it and the ancestry does
        // not leave the project. The existing flag is exactly true and stays.
        [$bundle, $stderr] = $this->discover('app/Http/Controllers/PlainController.php', [
            '<?php',
            '',
            'namespace App\Http\Controllers;',
            '',
            'class PlainController extends Controller',
            '{',
            '    public function unchanged(): int',
            '    {',
            '        return 1;',
            '    }',
            '}',
            '',
        ], "    public function index()\n    {\n        return \$this->succes([]);\n    }\n");

        $flagged = $this->items($bundle, 'flagged');

        self::assertCount(1, $flagged);
        self::assertStringContainsString('could not be resolved on disk', $flagged[0]['payload']);
        self::assertStringContainsString('unresolved named_reference: App\Http\Controllers\PlainController::succes', $stderr);
        self::assertStringNotContainsString('inherited member', $stderr);
        self::assertSame([], $this->items($bundle, 'fetched'), 'the origin-file guard holds here too');
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @return list<array<string, mixed>>
     */
    private function items(array $bundle, string $lever): array
    {
        return array_values(array_filter($bundle['items'], static fn (array $i): bool => $i['lever'] === $lever));
    }

    private function subjectOf(array $bundle, array $item): string
    {
        foreach ($bundle['assertions'] as $assertion) {
            if ($assertion['id'] === $item['assertion_id']) {
                return (string) $assertion['subject'];
            }
        }

        return '';
    }

    /**
     * Commit the file without the method, then with it appended before the closing brace, and
     * take git's diff between the two. The checkout is left at the post-image.
     *
     * @param list<string> $withoutLines
     *
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function discover(string $path, array $withoutLines, string $method): array
    {
        $git = fn (string $args): string => (string) shell_exec('git -C ' . escapeshellarg($this->repo) . ' ' . $args . ' 2>&1');
        $without = implode("\n", $withoutLines);
        $with = preg_replace('/\}\n$/', "\n" . $method . "}\n", $without);

        $git('init -q');
        file_put_contents($this->repo . '/' . $path, $without);
        $git('add -A');
        $git('-c user.email=x -c user.name=x commit -qm before');
        file_put_contents($this->repo . '/' . $path, $with);
        $git('add -A');
        $git('-c user.email=x -c user.name=x commit -qm after');

        $diff = $git('diff HEAD~1 HEAD -- ' . escapeshellarg($path));
        $diffPath = $this->repo . '/change.diff';
        file_put_contents($diffPath, $diff);

        $root = dirname(__DIR__, 2);
        $process = proc_open(
            [PHP_BINARY, $root . '/bin/context-discover', '--diff', $diffPath, '--repo', $this->repo, '--budget', '8000', '--format', 'json'],
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
