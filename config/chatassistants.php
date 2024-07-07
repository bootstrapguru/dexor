<?php

// Form to prompt user to select a service first
$selectedService = $this->ask('Select a service: openai, claude');

if ($selectedService == 'openai') {
    $selectedModel = $this->choice('Select a model:', ['gpt-3.5-turbo', 'gpt-4-turbo', 'gpt-4-turbo-preview']);
} elseif ($selectedService == 'claude') {
    $selectedModel = $this->choice('Select a model:', ['claude-1', 'claude-2', 'claude-instant']);
}

$selectedService;
$selectedModel;