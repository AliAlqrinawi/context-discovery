<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Cli;

use ContextDiscovery\Cli\Options;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Options::class)]
final class OptionsTest extends TestCase
{
    public function testTheThreeRequiredOptionsAreRead(): void
    {
        $options = Options::fromArgv([
            'context-discover', '--diff', 'commit.patch', '--repo', '/srv/app', '--budget', '8000',
        ]);

        self::assertSame('commit.patch', $options->diffPath);
        self::assertSame('/srv/app', $options->repositoryRoot);
        self::assertSame(8000, $options->budgetTokens);
    }

    public function testTheDefaultsAreTheOnesTheContractStates(): void
    {
        $options = $this->minimal();

        self::assertSame(Options::FORMAT_JSON, $options->format, 'JSON is the stable machine contract');
        self::assertSame('app/', $options->callerScope, "Experiment 4's minimum context");
        self::assertSame(20, $options->maxCallSites, 'architectural assumption AA1');
    }

    public function testOmittingTheBudgetIsAUsageErrorThatSaysWhyThereIsNoDefault(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('--budget is required and has no default');

        Options::fromArgv(['context-discover', '--diff', 'commit.patch', '--repo', '/srv/app']);
    }

    public function testTheBudgetErrorExplainsTheReasoningRatherThanJustRefusing(): void
    {
        try {
            Options::fromArgv(['context-discover', '--diff', 'd', '--repo', 'r']);
            self::fail('expected a usage error');
        } catch (InvalidArgumentException $error) {
            self::assertStringContainsString('the research fixes no absolute number', $error->getMessage());
            self::assertStringContainsString('dramatically less', $error->getMessage());
        }
    }

    #[DataProvider('bothOptionForms')]
    public function testAnOptionMayBeSpacedOrEqualsJoined(string ...$argv): void
    {
        self::assertSame(1234, Options::fromArgv(['context-discover', ...$argv])->budgetTokens);
    }

    /**
     * @return iterable<string, list<string>>
     */
    public static function bothOptionForms(): iterable
    {
        yield 'spaced' => ['--diff', 'd', '--repo', 'r', '--budget', '1234'];
        yield 'equals-joined' => ['--diff=d', '--repo=r', '--budget=1234'];
    }

    public function testStdinIsRequestedWithADash(): void
    {
        $options = Options::fromArgv(['context-discover', '--diff', '-', '--repo', 'r', '--budget', '10']);

        self::assertTrue($options->readsStdin());
        self::assertFalse($this->minimal()->readsStdin());
    }

    public function testMarkdownIsAccepted(): void
    {
        $options = Options::fromArgv([
            'context-discover', '--diff', 'd', '--repo', 'r', '--budget', '10', '--format', 'markdown',
        ]);

        self::assertSame(Options::FORMAT_MARKDOWN, $options->format);
    }

    public function testTheCallerScopeAndBoundCanBeOverridden(): void
    {
        $options = Options::fromArgv([
            'context-discover', '--diff', 'd', '--repo', 'r', '--budget', '10',
            '--caller-scope', 'src/', '--max-call-sites', '5',
        ]);

        self::assertSame('src/', $options->callerScope);
        self::assertSame(5, $options->maxCallSites);
    }

    #[DataProvider('usageErrors')]
    public function testAMalformedCommandLineIsAUsageError(array $argv, string $expected): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expected);

        Options::fromArgv($argv);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function usageErrors(): iterable
    {
        yield 'no diff' => [
            ['context-discover', '--repo', 'r', '--budget', '10'],
            '--diff is required',
        ];

        yield 'no repo' => [
            ['context-discover', '--diff', 'd', '--budget', '10'],
            '--repo is required',
        ];

        yield 'unknown option' => [
            ['context-discover', '--diff', 'd', '--repo', 'r', '--budget', '10', '--out', 'x'],
            'Unknown option --out',
        ];

        yield 'positional argument' => [
            ['context-discover', 'commit.patch'],
            'Unexpected argument',
        ];

        yield 'option with no value' => [
            ['context-discover', '--diff', '--repo', 'r'],
            '--diff needs a value',
        ];

        yield 'bad format' => [
            ['context-discover', '--diff', 'd', '--repo', 'r', '--budget', '10', '--format', 'yaml'],
            '--format must be "json" or "markdown"',
        ];

        yield 'non-numeric budget' => [
            ['context-discover', '--diff', 'd', '--repo', 'r', '--budget', 'lots'],
            '--budget must be a positive integer',
        ];

        yield 'zero budget' => [
            ['context-discover', '--diff', 'd', '--repo', 'r', '--budget', '0'],
            '--budget must be a positive integer',
        ];
    }

    public function testTheDeletedOutOptionIsNotQuietlyAccepted(): void
    {
        // Freeze review O4 removed it; shell redirection already covers it.
        $this->expectException(InvalidArgumentException::class);

        Options::fromArgv([
            'context-discover', '--diff', 'd', '--repo', 'r', '--budget', '10', '--out', 'bundle.json',
        ]);
    }

    private function minimal(): Options
    {
        return Options::fromArgv(['context-discover', '--diff', 'd', '--repo', 'r', '--budget', '10']);
    }
}
