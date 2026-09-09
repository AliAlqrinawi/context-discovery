<?php

declare(strict_types=1);

namespace ContextDiscovery\Cli;

use ContextDiscovery\Domain\Bundle\RunMetadata;
use ContextDiscovery\Pipeline\DiscoverContext;
use ContextDiscovery\Ports\BundleWriter;

/**
 * Runs the use case, writes the bundle to stdout and diagnostics to stderr, returns an exit code.
 *
 * The two streams stay separate. stdout carries the bundle and stderr the diagnostics, and every
 * diagnostic line still goes to stderr byte for byte — v2's `diagnostics[]` is a mirror for
 * machines, never a relocation, and it costs no tokens, so `used_tokens` still describes the
 * artifact (freeze review L2, ADR-A024). There is no `--out` — shell redirection already covers
 * it (freeze review O4).
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
        private readonly RunMetadata $run,
        private readonly mixed $stdout,
        private readonly mixed $stderr,
    ) {
    }

    public function run(string $diffText): ExitCode
    {
        // The diff's digest is a hash of the bytes handed in, so it reproduces anywhere without
        // reading a repository or spawning anything (P8, P9).
        $bundle = $this->context->run(
            $diffText,
            $this->run->withDiffSha(substr(sha1($diffText), 0, 12)),
            fn (string $line): int|false => fwrite($this->stderr, $line . "\n"),
        );

        fwrite($this->stdout, $this->writer->write($bundle));

        if ($bundle->usedTokens > $bundle->budgetTokens()) {
            // Approved decision D4: flagged items are never dropped, so a bundle whose flags alone
            // outweigh the budget is emitted over budget rather than quietly shedding concerns.
            // The run still succeeds — the bundle is honest and the overage is visible.
            fwrite($this->stderr, sprintf(
                "over budget: %d tokens used against a budget of %d. Flagged items are never "
                . "dropped (P10); nothing was omitted to make the total fit.\n",
                $bundle->usedTokens,
                $bundle->budgetTokens(),
            ));
        }

        return ExitCode::Success;
    }
}
