<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AIModelData extends Data
{
    public function __construct(
        public string $name
    ) {
    }
}
