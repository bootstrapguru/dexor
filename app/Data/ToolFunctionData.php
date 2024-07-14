<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ToolFunctionData extends Data
{
    public function __construct(
        public string $name,
        public string $arguments,
    ) {}
}
