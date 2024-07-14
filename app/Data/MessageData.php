<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class MessageData extends Data
{
    public function __construct(
        public string $role,
        public ?string $content,
        public ?string $tool_call_id,
        public ?string $tool_name,
        /** @var Collection<int, ToolCallData> */
        public ?Collection $tool_calls
    ) {}
}
