<?php

namespace App\Services;

/**
 * A project-local collaborator with a static `create` — deliberately the same member name Eloquent
 * supplies dynamically on a Model. Nothing about this class is framework behaviour.
 */
final class Registry
{
    /**
     * @param array<string, string> $entries
     */
    private function __construct(private readonly array $entries)
    {
    }

    /**
     * @param array<string, string> $entries
     */
    public static function create(array $entries): self
    {
        return new self($entries);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->entries;
    }
}
