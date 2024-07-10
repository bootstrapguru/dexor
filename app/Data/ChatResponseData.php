<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ChatResponseData extends Data
{
    public function __construct(
        public string $role,
        public ?string $content,
        public ?string $tool_call_id,
        public ?string $name
    ) {}
}
