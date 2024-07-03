<?php

namespace App\Attributes;


use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER)]
final class Description
{
    public function __construct(
        public string $value,
    ) {
    }
}
