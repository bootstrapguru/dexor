<?php

namespace App\Utils;

use RoachPHP\Events\RunFinished;
use RoachPHP\Extensions\ExtensionInterface;

class RoachLoggerExtension implements ExtensionInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RunFinished::NAME => ['onRunFinished', 100],
        ];
    }

    public function onRunFinished(RunFinished $event): void
    {
        
    }

    public function configure(array $options): void
    {
        // TODO: Implement configure() method.
    }
}
