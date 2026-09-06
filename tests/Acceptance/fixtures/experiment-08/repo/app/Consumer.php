<?php

namespace App;

use App\Support\Direct;
use App\Support\Dynamic;
use App\Support\OneLevel;
use App\Support\TwoLevel;
use App\Support\ViaParent;
use App\Support\WithTrait;

class Consumer extends ViaParent
{
    public function existing(): string
    {
        return 'unchanged';
    }

    public function exercise(string $input): array
    {
        return [
            'direct' => Direct::run($input),
            'lookalike' => Direct::slug($input),
            'one_level' => OneLevel::run($input),
            'two_level' => TwoLevel::run($input),
            'trait_here' => WithTrait::greet($input),
            'trait_via_parent' => ViaParent::greet($input),
            'dynamic' => Dynamic::create($input),
            'unknown' => Direct::nope($input),
            'typo' => Direct::runn($input),
            'inherited_on_this' => $this->instanceGreet($input),
        ];
    }
}
