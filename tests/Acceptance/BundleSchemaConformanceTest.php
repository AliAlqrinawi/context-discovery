<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\ContractVersion;
use ContextDiscovery\Domain\Bundle\Lever;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * `schema/bundle-v2.schema.json` is the contract, and this test is what makes that true.
 *
 * The lesson it encodes is `KIND_ORDER`: that list lived in `BundleAssembler`, in
 * `03-interfaces.md` and in `ExperimentKeyTest` with **nothing forcing agreement**, and it went
 * stale in one of the three for a whole commit without a single test noticing. So the closed sets
 * are asserted *both ways* here — every value the code can emit is in the schema, and every value
 * the schema permits exists in the code. Adding a kind in one place and not the other fails.
 *
 * Validation is a small purpose-built walker rather than a library: the runtime has zero
 * dependencies (ADR-A001), and the subset of JSON Schema this contract uses is narrow enough that
 * a validator is cheaper to read than a dependency is to justify.
 */
final class BundleSchemaConformanceTest extends TestCase
{
    // ---------------------------------------------------------------- the sets agree

    public function testTheSchemaAndTheEnumAgreeOnTheAssertionKinds(): void
    {
        $schema = $this->schema()['$defs']['assertion']['properties']['kind']['enum'];
        $code = array_map(static fn (AssertionKind $k): string => $k->value, AssertionKind::cases());

        sort($schema);
        sort($code);

        self::assertSame($code, $schema, 'every kind the code can emit must be in the schema, and vice versa');
    }

    public function testTheSchemaAndTheEnumAgreeOnTheLevers(): void
    {
        $schema = $this->schema()['$defs']['item']['properties']['lever']['enum'];
        $code = array_map(static fn (Lever $l): string => $l->value, Lever::cases());

        sort($schema);
        sort($code);

        self::assertSame($code, $schema);
    }

    /**
     * The schema's `kindOrder` is the source; `BundleAssembler::KIND_ORDER` is the copy. This is
     * the assertion that would have caught the stale copy in `ExperimentKeyTest`.
     */
    public function testTheSchemaAndTheAssemblerAgreeOnTheItemOrder(): void
    {
        $order = (new ReflectionClass(BundleAssembler::class))->getConstant('KIND_ORDER');

        self::assertIsArray($order);

        asort($order);

        self::assertSame(
            $this->schema()['$defs']['kindOrder']['const'],
            array_keys($order),
            'the fixed item order in the schema and in BundleAssembler have diverged'
        );
    }

    public function testTheSchemaAndTheCodeAgreeOnTheBundleVersion(): void
    {
        self::assertSame($this->schema()['properties']['bundle_version']['const'], ContractVersion::BUNDLE);
    }

    // ---------------------------------------------------------------- real output validates

    /**
     * Hermetic: the repository is built here, so this runs anywhere and needs no private corpus.
     */
    public function testLiveOutputSatisfiesTheSchema(): void
    {
        $repo = sys_get_temp_dir() . '/schema-conformance-' . bin2hex(random_bytes(6));

        mkdir($repo . '/app/Repositories', 0777, true);
        mkdir($repo . '/app/Actions', 0777, true);

        file_put_contents($repo . '/composer.json', json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]]));
        file_put_contents($repo . '/app/Repositories/BranchRepository.php', <<<'PHP'
            <?php

            namespace App\Repositories;

            use App\Models\Branch;

            class BranchRepository
            {
                public function getAll(): Collection
                {
                    return $this->query->first();
                }
            }
            PHP);
        file_put_contents($repo . '/app/Actions/GetBranchesAction.php', <<<'PHP'
            <?php

            namespace App\Actions;

            class GetBranchesAction
            {
                public function execute(): Collection
                {
                    return $this->repository->getAll();
                }
            }
            PHP);

        $diff = <<<'DIFF'
            diff --git a/app/Repositories/BranchRepository.php b/app/Repositories/BranchRepository.php
            --- a/app/Repositories/BranchRepository.php
            +++ b/app/Repositories/BranchRepository.php
            @@ -8,5 +8,5 @@ class BranchRepository
                 public function getAll(): Collection
                 {
            -        return $this->query->get();
            +        return $this->query->first();
                 }
             }
            DIFF;

        try {
            $bundle = $this->invoke($repo, $diff);

            self::assertNotSame([], $bundle['assertions'], 'the fixture must exercise a real assertion');
            self::assertNotSame([], $bundle['items'], 'and produce real items');
            self::assertSame([], $this->violations($bundle, $this->schema()));
        } finally {
            exec('rm -rf ' . escapeshellarg($repo));
        }
    }

    /**
     * The Markdown golden is the same run under `--format markdown` (ADR-B003 in the backend
     * checks the two renderings of every run against each other). Here: same items, same order,
     * same subjects, same payload bytes - read from the two committed files.
     */
    public function testTheGoldenMarkdownCarriesTheSameItemsInTheSameOrder(): void
    {
        $golden = json_decode((string) file_get_contents(__DIR__ . '/fixtures/golden/m26-bundle.v2.json'), true);
        $markdown = (string) file_get_contents(__DIR__ . '/fixtures/golden/m26-bundle.v2.md');

        $byId = [];

        foreach ($golden['assertions'] as $assertion) {
            $byId[$assertion['id']] = $assertion;
        }

        preg_match_all('/^## (\S+) · (\S+)\n\n\*\*Subject:\*\* (.*)$/m', $markdown, $blocks, PREG_SET_ORDER);

        self::assertCount(count($golden['items']), $blocks);

        foreach ($golden['items'] as $index => $item) {
            self::assertSame($item['lever'], $blocks[$index][1]);
            self::assertSame($byId[$item['assertion_id']]['kind'], $blocks[$index][2]);
            self::assertSame($byId[$item['assertion_id']]['subject'], $blocks[$index][3]);
            self::assertStringContainsString("\n" . $item['payload'] . "\n", $markdown, 'payload ' . $index . ' is in the Markdown byte for byte');
        }

        self::assertStringContainsString(sprintf('budget 8000 / used %d tokens', $golden['used_tokens']), $markdown);
        self::assertStringEndsWith("## Dropped\n\nNothing was dropped.\n", $markdown);
    }

    public function testTheGoldenM26BundleSatisfiesTheSchema(): void
    {
        $golden = json_decode((string) file_get_contents(
            __DIR__ . '/fixtures/golden/m26-bundle.v2.json'
        ), true);

        self::assertIsArray($golden, 'the golden fixture must be parseable');
        self::assertSame([], $this->violations($golden, $this->schema()));

        // The recorded shape of the M26 reproduction, so a change to it is a visible diff.
        // Regenerated under POLICY 3 (ADR-A029 §7): the M26 diff's `$this->success(` is the
        // fourth form, so a third assertion and its S1 flag (62 tokens) joined the 562-token,
        // 22-item bundle recorded under POLICY 2.
        self::assertSame(2, $golden['bundle_version']);
        self::assertSame('3', $golden['run']['policy_version']);
        self::assertSame(624, $golden['used_tokens']);
        self::assertCount(23, $golden['items']);
        self::assertCount(3, $golden['assertions']);
        self::assertCount(1, $golden['diagnostics']);
        self::assertSame('call_sites_truncated', $golden['diagnostics'][0]['type']);
    }

    public function testEveryItemInTheGoldenBundleNamesAnAssertionItCarries(): void
    {
        $golden = json_decode((string) file_get_contents(
            __DIR__ . '/fixtures/golden/m26-bundle.v2.json'
        ), true);

        $ids = array_column($golden['assertions'], 'id');

        foreach ($golden['items'] as $index => $item) {
            self::assertContains($item['assertion_id'], $ids, 'orphan item at index ' . $index);
        }

        foreach ($golden['diagnostics'] as $diagnostic) {
            if ($diagnostic['assertion_id'] !== '') {
                self::assertContains($diagnostic['assertion_id'], $ids);
            }
        }
    }

    public function testDiagnosticsAreExcludedFromTheTokenTotal(): void
    {
        $golden = json_decode((string) file_get_contents(
            __DIR__ . '/fixtures/golden/m26-bundle.v2.json'
        ), true);

        // Freeze review L2's objection to diagnostics in the bundle was that the artifact would
        // outgrow the number describing it. used_tokens sums the items and nothing else.
        self::assertSame(
            array_sum(array_column($golden['items'], 'tokens')),
            $golden['used_tokens']
        );
    }

    // ---------------------------------------------------------------- the validator

    /**
     * @param array<string, mixed> $schema
     *
     * @return list<string> One line per violation; empty when the document conforms.
     */
    private function violations(mixed $value, array $schema, string $path = '$'): array
    {
        if (isset($schema['$ref'])) {
            return $this->violations($value, $this->resolve($schema['$ref']), $path);
        }

        $problems = [];

        if (array_key_exists('const', $schema) && $value !== $schema['const']) {
            $problems[] = sprintf('%s: expected %s', $path, json_encode($schema['const']));
        }

        if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) {
            $problems[] = sprintf('%s: %s is not one of %s', $path, json_encode($value), json_encode($schema['enum']));
        }

        if (isset($schema['type']) && !$this->matchesType($value, (array) $schema['type'])) {
            $problems[] = sprintf('%s: %s is not %s', $path, json_encode($value), implode('|', (array) $schema['type']));

            return $problems; // no point checking the shape of the wrong type
        }

        if (is_string($value) && isset($schema['minLength']) && strlen($value) < $schema['minLength']) {
            $problems[] = sprintf('%s: shorter than %d', $path, $schema['minLength']);
        }

        if (is_string($value) && isset($schema['pattern'])
            && preg_match('/' . str_replace('/', '\/', $schema['pattern']) . '/', $value) !== 1) {
            $problems[] = sprintf('%s: %s does not match %s', $path, $value, $schema['pattern']);
        }

        if (is_int($value) && isset($schema['minimum']) && $value < $schema['minimum']) {
            $problems[] = sprintf('%s: below %d', $path, $schema['minimum']);
        }

        if (is_array($value) && array_is_list($value)) {
            if (isset($schema['minItems']) && count($value) < $schema['minItems']) {
                $problems[] = sprintf('%s: fewer than %d entries', $path, $schema['minItems']);
            }

            if (isset($schema['maxItems']) && count($value) > $schema['maxItems']) {
                $problems[] = sprintf('%s: more than %d entries', $path, $schema['maxItems']);
            }

            foreach ($value as $index => $entry) {
                if (isset($schema['items'])) {
                    array_push($problems, ...$this->violations($entry, $schema['items'], $path . '[' . $index . ']'));
                }
            }

            return $problems;
        }

        if (!is_array($value)) {
            return $problems;
        }

        foreach ($schema['required'] ?? [] as $required) {
            if (!array_key_exists($required, $value)) {
                $problems[] = sprintf('%s: missing required "%s"', $path, $required);
            }
        }

        foreach ($value as $key => $entry) {
            $child = $path . '.' . $key;

            if (isset($schema['properties'][$key])) {
                array_push($problems, ...$this->violations($entry, $schema['properties'][$key], $child));

                continue;
            }

            if (isset($schema['additionalProperties']) && is_array($schema['additionalProperties'])) {
                array_push($problems, ...$this->violations($entry, $schema['additionalProperties'], $child));

                continue;
            }

            if (($schema['additionalProperties'] ?? true) === false) {
                $problems[] = sprintf('%s: unexpected property "%s"', $path, $key);
            }
        }

        return $problems;
    }

    /**
     * @param list<string> $types
     */
    private function matchesType(mixed $value, array $types): bool
    {
        foreach ($types as $type) {
            $ok = match ($type) {
                'object' => is_array($value) && !array_is_list($value),
                'array' => is_array($value) && array_is_list($value),
                'string' => is_string($value),
                'integer' => is_int($value),
                'null' => $value === null,
                default => false,
            };

            // An empty PHP array is both [] and {} after json_decode; accept it for either.
            if ($ok || ($value === [] && ($type === 'object' || $type === 'array'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolve(string $ref): array
    {
        $node = $this->schema();

        foreach (array_slice(explode('/', $ref), 1) as $segment) {
            $node = $node[$segment];
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        $schema = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/schema/bundle-v2.schema.json'), true);

        self::assertIsArray($schema, 'schema/bundle-v2.schema.json must be parseable');

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function invoke(string $repo, string $diff): array
    {
        $root = dirname(__DIR__, 2);
        $path = $repo . '/change.diff';

        file_put_contents($path, $diff);

        $process = proc_open(
            [PHP_BINARY, $root . '/bin/context-discover', '--diff', $path, '--repo', $repo,
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
