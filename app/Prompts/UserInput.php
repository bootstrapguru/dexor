<?php

namespace App\Prompts;

use Laravel\Prompts\TextareaPrompt;
use Laravel\Prompts\Key;
use Laravel\Prompts\Themes\Default\TextareaPromptRenderer;

class UserInput extends TextareaPrompt
{
    public function __construct(
        string $label,
        string $placeholder = '',
        string $default = '',
        bool|string $required = false,
        mixed $validate = null,
        string $hint = '',
        int $rows = 5
    ) {

        parent::__construct($label, $placeholder, $default, $required, $validate, $hint, $rows);

        $this->on('key', function ($key) {

            if ($key[0] === "\e") {
                match ($key) {
                    Key::UP, Key::UP_ARROW, Key::CTRL_P => $this->handleUpKey(),
                    Key::DOWN, Key::DOWN_ARROW, Key::CTRL_N => $this->handleDownKey(),
                    default => null,
                };

                return;
            }
            dd(mb_str_split($key));

            if ($key === "n") {
                $this->handleDownKey();
            }

            // Keys may be buffered.
            foreach (mb_str_split($key) as $key) {
                if ($key === Key::CTRL_D || $key === Key::ENTER) {
                    $this->submit();

                    return;
                }
            }


        });

    }

    protected function getRenderer(): callable
    {
        return new TextareaPromptRenderer($this);
    }
}
