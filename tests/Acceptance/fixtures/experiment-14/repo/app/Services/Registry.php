<?php

namespace App\Services;

final class Registry
{
    private function __construct(private readonly array $entries)
    {
    }

    public static function create(array $entries): self
    {
        return new self($entries);
    }

    public function all(): array
    {
        return $this->entries;
    }
}
