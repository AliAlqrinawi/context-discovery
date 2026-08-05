<?php

declare(strict_types=1);

namespace ContextDiscovery\Cli;

/**
 * The process contract (03-interfaces.md §1).
 *
 * An empty-but-valid bundle — Experiment 2's correct answer — exits `Success`. Emptiness is a
 * result, not a failure. So is a bundle that exceeds its budget because flags alone outweigh it:
 * the overage is reported on stderr and the run still succeeds (approved decision D4).
 *
 * The taxonomy itself is ordinary CLI practice rather than a research finding — architectural
 * assumption AA7.
 */
enum ExitCode: int
{
    case Success = 0;
    case UsageError = 1;
    case RepositoryUnreadable = 2;
}
