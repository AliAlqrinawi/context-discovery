<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

/**
 * What produced this bundle, so two bundles can be compared knowingly.
 *
 * Every value is either declared or derived from an input. Nothing here is observed from the
 * environment: there is no clock, no hostname and no process (P8, P9).
 *
 * `repoSha` is **supplied by the caller** through `--repo-sha` and is null when it was not given.
 * The tool cannot read it: `git rev-parse` needs a subprocess, which P9 forbids, and reading
 * `.git/HEAD` fails for exactly the case that matters — ADR-A022's harness checks the reviewed
 * commit out as a detached worktree, where `.git` is a file rather than a directory. Null is
 * emitted explicitly rather than omitted, so "not supplied" is visible instead of inferred.
 */
final class RunMetadata
{
    public function __construct(
        public readonly string $engineVersion,
        public readonly string $policyVersion,
        public readonly string $frameworkTableVersion,
        public readonly int $budgetTokens,
        public readonly ?string $diffSha = null,
        public readonly ?string $repoSha = null,
    ) {
    }

    /**
     * The same metadata with the diff's digest filled in — a content hash of the input bytes, so
     * it is reproducible anywhere without reading a repository.
     */
    public function withDiffSha(string $diffSha): self
    {
        return new self(
            $this->engineVersion,
            $this->policyVersion,
            $this->frameworkTableVersion,
            $this->budgetTokens,
            $diffSha,
            $this->repoSha,
        );
    }
}
