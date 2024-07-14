<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ToolCallData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public ToolFunctionData $function,
    ) {}
}
