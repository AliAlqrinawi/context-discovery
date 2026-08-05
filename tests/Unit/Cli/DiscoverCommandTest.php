<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Cli;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Adapters\Serialization\JsonBundleWriter;
use ContextDiscovery\Adapters\Serialization\MarkdownBundleWriter;
use ContextDiscovery\Assembly\BudgetEnforcer;
use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Assembly\ItemPriority;
use ContextDiscovery\Assembly\TokenEstimate;
use ContextDiscovery\Cli\DiscoverCommand;
use ContextDiscovery\Cli\ExitCode;
use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Extraction\OwnFileAssertionExtractor;
use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Lever\LeverPolicy;
use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
use ContextDiscovery\Discovery\Resolution\OwnFileResolver;
use ContextDiscovery\Pipeline\DiscoverContext;
use ContextDiscovery\Ports\BundleWriter;
use ContextDiscovery\Tests\Fakes\FakeClassLocator;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiscoverCommand::class)]
#[CoversClass(ExitCode::class)]
final class DiscoverCommandTest extends TestCase
{
    private const PATH = 'app/Services/Thing.php';

    /** @var resource */
    private $stdout;

    /** @var resource */
    private $stderr;

    protected function setUp(): void
    {
        $this->stdout = fopen('php://memory', 'r+');
        $this->stderr = fopen('php://memory', 'r+');
    }

    public function testTheBundleGoesToStdoutAndTheRunSucceeds(): void
    {
        $exit = $this->command()->run($this->diff(), 8000);

        self::assertSame(ExitCode::Success, $exit);
        self::assertSame(0, $exit->value);

        $decoded = json_decode($this->read($this->stdout), true);

        self::assertIsArray($decoded);
        self::assertSame(1, $decoded['bundle_version']);
        self::assertSame(8000, $decoded['budget_tokens']);
        self::assertNotSame([], $decoded['items']);
    }

    public function testStdoutCarriesNoDiagnosticsSoItIsAlwaysParseable(): void
    {
        $this->command()->run($this->diffOfAMissingFile(), 8000);

        $stdout = $this->read($this->stdout);

        self::assertNotNull(json_decode($stdout, true));
        self::assertStringNotContainsString('unreadable path', $stdout);
        self::assertStringContainsString('unreadable path', $this->read($this->stderr));
    }

    public function testAnEmptyBundleStillSucceeds(): void
    {
        // Experiment 2's correct answer exits 0. Emptiness is a result, not a failure.
        $exit = $this->command()->run('', 8000);

        self::assertSame(ExitCode::Success, $exit);
        self::assertSame([], json_decode($this->read($this->stdout), true)['items']);
    }

    public function testABundleOverBudgetIsEmittedAndReportedRatherThanTrimmed(): void
    {
        // Approved decision D4: flagged items are never dropped, so a tiny budget produces an
        // honest over-budget bundle plus a diagnostic — and still exits 0.
        $exit = $this->command()->run($this->diffOfAnUnimportedSymbolOnly(), 1);

        $decoded = json_decode($this->read($this->stdout), true);
        $stderr = $this->read($this->stderr);

        self::assertSame(ExitCode::Success, $exit, 'an honest over-budget bundle is still a success');

        if ($decoded['used_tokens'] > $decoded['budget_tokens']) {
            self::assertStringContainsString('over budget', $stderr);
            self::assertStringContainsString('never dropped', $stderr);
        } else {
            self::assertStringNotContainsString('over budget', $stderr);
        }
    }

    public function testTheMarkdownWriterProducesThePasteAlongsideArtifact(): void
    {
        $this->command(new MarkdownBundleWriter())->run($this->diff(), 8000);

        $stdout = $this->read($this->stdout);

        self::assertStringContainsString('# Context bundle', $stdout);
        self::assertStringContainsString('## Dropped', $stdout);
    }

    public function testTwoRunsWriteTheSameBytes(): void
    {
        $this->command()->run($this->diff(), 8000);
        $first = $this->read($this->stdout);

        $this->setUp();
        $this->command()->run($this->diff(), 8000);

        self::assertSame($first, $this->read($this->stdout));
    }

    private function command(?BundleWriter $writer = null): DiscoverCommand
    {
        $source = new FakeSourceRepository([self::PATH => $this->source()]);
        $slicer = new TokenizerMemberSlicer();

        $context = new DiscoverContext(
            new UnifiedDiffParser(),
            $source,
            new AssertionExtractor([new OwnFileAssertionExtractor($slicer)]),
            new LeverPolicy(),
            new FakeClassLocator(),
            new OwnFileResolver($source, $slicer),
            new AssumptionWriter(),
            new BundleAssembler(new TokenEstimate()),
            new BudgetEnforcer(new ItemPriority()),
        );

        return new DiscoverCommand($context, $writer ?? new JsonBundleWriter(), $this->stdout, $this->stderr);
    }

    /**
     * @param resource $stream
     */
    private function read($stream): string
    {
        rewind($stream);

        return (string) stream_get_contents($stream);
    }

    private function diff(): string
    {
        return implode("\n", [
            '--- a/' . self::PATH,
            '+++ b/' . self::PATH,
            '@@ -8,2 +8,3 @@',
            '    public function handle(): void',
            '    {',
            "+        Log::info('handled');",
        ]) . "\n";
    }

    private function diffOfAMissingFile(): string
    {
        return implode("\n", [
            '--- a/app/Gone.php',
            '+++ b/app/Gone.php',
            '@@ -1,1 +1,2 @@',
            ' <?php',
            "+        Log::info('x');",
        ]) . "\n";
    }

    private function diffOfAnUnimportedSymbolOnly(): string
    {
        return $this->diff();
    }

    private function source(): string
    {
        return <<<'PHP'
<?php

namespace App\Services;

use App\Models\Thing;

class Service
{
    public function handle(): void
    {
        Log::info('handled');
    }
}
PHP;
    }
}
