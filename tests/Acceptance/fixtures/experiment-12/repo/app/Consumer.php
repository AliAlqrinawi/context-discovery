<?php

namespace App;

use App\Services\HiddenService;
use App\Services\NewService;
use App\Services\Noisy;
use App\Services\Untouched;
use App\Services\VisibleService;

class Consumer
{
    public function existing(): string
    {
        return 'unchanged';
    }

    public function exercise(string $input): array
    {
        return [
            'created_member'  => NewService::run($input),        // M12.1
            'visible_member'  => VisibleService::run($input),    // M12.2
            'hidden_member'   => HiddenService::run($input),     // M12.3 / M12.4
            'not_in_diff'     => Untouched::go(),                // M12.8 control
            'noisy'           => Noisy::shout(),                 // M12.5
        ];
    }

    public function surface(): ?NewService
    {
        return null;                                             // M12.1b — bare class, created file
    }
}
