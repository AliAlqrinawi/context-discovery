<?php

namespace App\Support;

final class Auditor
{
    public function record(string $action): void
    {
        Log::info('auditor', ['action' => $action]);
    }
}
