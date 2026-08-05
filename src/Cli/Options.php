<?php

declare(strict_types=1);

namespace ContextDiscovery\Cli;

use InvalidArgumentException;

/**
 * The public command surface (03-interfaces.md §1).
 *
 * `--budget` is required and has no default. The research states the cost condition only in
 * relative terms — "dramatically below B", "well under half of B's tokens" — against a B that was
 * never measured, so any default this tool invented would be a fabricated threshold travelling
 * inside a project built to keep validated and assumed apart (ADR-A008). The operator sets the
 * number they are demonstrating against, and it is recorded in the bundle.
 *
 * The other defaults are transcribed from the contract, not chosen here: `app/` is Experiment 4's
 * minimum context, and `20` call sites is architectural assumption AA1.
 */
final class Options
{
    public const FORMAT_JSON = 'json';
    public const FORMAT_MARKDOWN = 'markdown';

    private const DEFAULT_CALLER_SCOPE = 'app/';
    private const DEFAULT_MAX_CALL_SITES = 20;

    private const USAGE = <<<'USAGE'
    context-discover --diff <path|-> --repo <path> --budget <int>
                     [--format json|markdown]
                     [--caller-scope <prefix>] [--max-call-sites <int>]
    USAGE;

    private function __construct(
        public readonly string $diffPath,
        public readonly string $repositoryRoot,
        public readonly int $budgetTokens,
        public readonly string $format,
        public readonly string $callerScope,
        public readonly int $maxCallSites,
    ) {
    }

    /**
     * @param list<string> $argv Including the program name at index 0.
     *
     * @throws InvalidArgumentException on any usage error; the CLI reports it and exits 1.
     */
    public static function fromArgv(array $argv): self
    {
        $values = self::parse(array_slice($argv, 1));

        $diff = self::required($values, 'diff');
        $repository = self::required($values, 'repo');

        if (!isset($values['budget'])) {
            throw new InvalidArgumentException(
                "--budget is required and has no default: the research fixes no absolute number, "
                . "only that the bundle must cost dramatically less than sending the whole "
                . "repository. Choose the budget this run is measured against.\n\n" . self::USAGE
            );
        }

        $format = $values['format'] ?? self::FORMAT_JSON;

        if ($format !== self::FORMAT_JSON && $format !== self::FORMAT_MARKDOWN) {
            throw new InvalidArgumentException(
                sprintf('--format must be "json" or "markdown", got "%s".', $format)
            );
        }

        return new self(
            diffPath: $diff,
            repositoryRoot: $repository,
            budgetTokens: self::positiveInt($values['budget'], 'budget'),
            format: $format,
            callerScope: $values['caller-scope'] ?? self::DEFAULT_CALLER_SCOPE,
            maxCallSites: isset($values['max-call-sites'])
                ? self::positiveInt($values['max-call-sites'], 'max-call-sites')
                : self::DEFAULT_MAX_CALL_SITES,
        );
    }

    public function readsStdin(): bool
    {
        return $this->diffPath === '-';
    }

    public static function usage(): string
    {
        return self::USAGE;
    }

    /**
     * @param list<string> $arguments
     *
     * @return array<string, string>
     */
    private static function parse(array $arguments): array
    {
        $values = [];
        $count = count($arguments);

        for ($i = 0; $i < $count; $i++) {
            $argument = $arguments[$i];

            if (!str_starts_with($argument, '--')) {
                throw new InvalidArgumentException(
                    sprintf('Unexpected argument "%s".%s%s', $argument, "\n\n", self::USAGE)
                );
            }

            $name = substr($argument, 2);
            $value = null;

            if (str_contains($name, '=')) {
                [$name, $value] = explode('=', $name, 2);
            } elseif ($i + 1 < $count && !str_starts_with($arguments[$i + 1], '--')) {
                $value = $arguments[++$i];
            }

            if ($value === null || $value === '') {
                throw new InvalidArgumentException(
                    sprintf('--%s needs a value.%s%s', $name, "\n\n", self::USAGE)
                );
            }

            if (!in_array($name, ['diff', 'repo', 'budget', 'format', 'caller-scope', 'max-call-sites'], true)) {
                throw new InvalidArgumentException(
                    sprintf('Unknown option --%s.%s%s', $name, "\n\n", self::USAGE)
                );
            }

            $values[$name] = $value;
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     */
    private static function required(array $values, string $name): string
    {
        if (!isset($values[$name])) {
            throw new InvalidArgumentException(
                sprintf('--%s is required.%s%s', $name, "\n\n", self::USAGE)
            );
        }

        return $values[$name];
    }

    private static function positiveInt(string $value, string $name): int
    {
        if (preg_match('/^\d+$/', $value) !== 1 || (int) $value < 1) {
            throw new InvalidArgumentException(
                sprintf('--%s must be a positive integer, got "%s".', $name, $value)
            );
        }

        return (int) $value;
    }
}
