<?php

declare(strict_types=1);

namespace ContextDiscovery\Cli;

use ContextDiscovery\Pipeline\DiscoverContext;
use ContextDiscovery\Ports\BundleWriter;

/**
 * Runs the use case, writes the bundle to stdout and diagnostics to stderr, returns an exit code.
 *
 * The two streams stay separate: the bundle on stdout is always parseable and carries no
 * diagnostics, and its token cost is the number that describes it (freeze review L2). There is no
 * `--out` — shell redirection already covers it (freeze review O4).
 */
final class DiscoverCommand
{
    /**
     * @param resource $stdout
     * @param resource $stderr
     */
    public function __construct(
        private readonly DiscoverContext $context,
        private readonly BundleWriter $writer,
        private readonly mixed $stdout,
        private readonly mixed $stderr,
    ) {
    }

    public function run(string $diffText, int $budgetTokens): ExitCode
    {
        $bundle = $this->context->run(
            $diffText,
            $budgetTokens,
            fn (string $line): int|false => fwrite($this->stderr, $line . "\n"),
        );

        fwrite($this->stdout, $this->writer->write($bundle));

        if ($bundle->usedTokens > $bundle->budgetTokens) {
            // Approved decision D4: flagged items are never dropped, so a bundle whose flags alone
            // outweigh the budget is emitted over budget rather than quietly shedding concerns.
            // The run still succeeds — the bundle is honest and the overage is visible.
            fwrite($this->stderr, sprintf(
                "over budget: %d tokens used against a budget of %d. Flagged items are never "
                . "dropped (P10); nothing was omitted to make the total fit.\n",
                $bundle->usedTokens,
                $bundle->budgetTokens,
            ));
        }

        return ExitCode::Success;
    }
}
