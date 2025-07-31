<?php

namespace App\Services;

use App\Data\MessageData;
use App\Data\ToolCallData;
use App\Models\Thread;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Facades\Tool;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\ToolResultMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\ToolCall;
use Prism\Prism\ValueObjects\ToolResult;

class PrismAdapter
{
    /**
     * Map service names to Prism Provider enums
     */
    private function mapServiceToProvider(string $service): Provider
    {
        return match ($service) {
            'openai' => Provider::OpenAI,
            'claude' => Provider::Anthropic,
            'ollama' => Provider::Ollama,
            'openrouter' => Provider::OpenRouter,
            'deep_seek' => Provider::DeepSeek,
            default => throw new \Exception("Provider {$service} not supported by Prism"),
        };
    }

    /**
     * Convert thread messages to Prism message format
     */
    public function formatMessagesForPrism(Thread $thread): array
    {
        $messages = [];
        
        foreach ($thread->messages as $message) {
            $content = $message->content;
            
            switch ($message->role) {
                case 'system':
                    $messages[] = new SystemMessage($content);
                    break;
                    
                case 'user':
                    $messages[] = new UserMessage($content);
                    break;
                    
                case 'assistant':
                    $assistantMessage = new AssistantMessage($content);
                    // If the message has tool calls, we'll need to handle them differently
                    if ($message->tool_calls) {
                        // For now, we'll just include the message content
                        // Tool calls will be handled in the response
                    }
                    $messages[] = $assistantMessage;
                    break;
                    
                case 'tool':
                    // Create a tool result message
                    $messages[] = new ToolResultMessage(
                        toolCallId: $message->tool_call_id,
                        content: $content
                    );
                    break;
            }
        }
        
        return $messages;
    }

    /**
     * Convert Dexor tools to Prism Tool format
     */
    public function convertToolToPrism(array $toolDefinition): \Prism\Prism\Tool
    {
        $functionDef = $toolDefinition['function'];
        $toolName = $functionDef['name'];
        $description = $functionDef['description'] ?? '';
        
        // Create a new Prism tool
        $tool = Tool::as($toolName)
            ->for($description);
        
        // Add parameters if they exist
        if (isset($functionDef['parameters']['properties'])) {
            foreach ($functionDef['parameters']['properties'] as $paramName => $paramDef) {
                $paramDescription = $paramDef['description'] ?? '';
                $paramType = $paramDef['type'] ?? 'string';
                $isRequired = in_array($paramName, $functionDef['parameters']['required'] ?? []);
                
                switch ($paramType) {
                    case 'string':
                        if (isset($paramDef['enum'])) {
                            $tool->withEnumParameter($paramName, $paramDescription, $paramDef['enum']);
                        } else {
                            $tool->withStringParameter($paramName, $paramDescription, !$isRequired);
                        }
                        break;
                        
                    case 'integer':
                    case 'number':
                        $tool->withNumberParameter($paramName, $paramDescription, !$isRequired);
                        break;
                        
                    case 'boolean':
                        $tool->withBooleanParameter($paramName, $paramDescription, !$isRequired);
                        break;
                        
                    case 'array':
                        $tool->withArrayParameter($paramName, $paramDescription, !$isRequired);
                        break;
                        
                    case 'object':
                        // For object parameters, we'll use string and expect JSON
                        $tool->withStringParameter($paramName, $paramDescription . ' (JSON object)', !$isRequired);
                        break;
                }
            }
        }
        
        return $tool;
    }

    /**
     * Convert multiple tools to Prism format
     */
    public function convertToolsToPrism(array $toolDefinitions): array
    {
        $prismTools = [];
        
        foreach ($toolDefinitions as $toolDef) {
            $prismTools[] = $this->convertToolToPrism($toolDef);
        }
        
        return $prismTools;
    }

    /**
     * Send a chat request using Prism
     */
    public function sendChatRequest(Thread $thread, array $tools = []): MessageData
    {
        $assistant = $thread->assistant;
        $provider = $this->mapServiceToProvider($assistant->service);
        
        // Get Prism tools
        $prismTools = \App\Services\PrismTools::getAllTools();
        
        // Build the request
        $request = Prism::text()
            ->using($provider, $assistant->model)
            ->withSystemPrompt($assistant->prompt)
            ->withMessages($this->formatMessagesForPrism($thread));
        
        // Add tools if any
        if (!empty($prismTools)) {
            $request->withTools($prismTools)
                ->withMaxSteps(3); // Allow multiple tool calls
        }
        
        // Send request and get response
        $response = $request->asText();
        
        // Convert Prism response to MessageData
        return $this->convertPrismResponseToMessageData($response);
    }

    /**
     * Convert Prism response to MessageData format
     */
    private function convertPrismResponseToMessageData($response): MessageData
    {
        $messageData = [
            'role' => 'assistant',
            'content' => $response->text ?? '',
        ];
        
        // Check if there are tool calls in the response
        if (isset($response->steps) && count($response->steps) > 0) {
            $toolCalls = [];
            
            foreach ($response->steps as $step) {
                if (isset($step->toolCalls) && is_array($step->toolCalls)) {
                    foreach ($step->toolCalls as $toolCall) {
                        if ($toolCall instanceof ToolCall) {
                            $toolCalls[] = [
                                'id' => $toolCall->id,
                                'type' => 'function',
                                'function' => [
                                    'name' => $toolCall->name,
                                    'arguments' => json_encode($toolCall->arguments()),
                                ],
                            ];
                        }
                    }
                }
            }
            
            if (!empty($toolCalls)) {
                $messageData['tool_calls'] = collect($toolCalls)->map(fn($tc) => ToolCallData::from($tc));
            }
        }
        
        return MessageData::from($messageData);
    }

    /**
     * Get available models for a provider
     */
    public function getModels(string $service): array
    {
        // For now, we'll return a predefined list of models per provider
        // In the future, this could be dynamic if Prism supports model listing
        return match ($service) {
            'openai' => [
                'gpt-4o',
                'gpt-4o-mini',
                'gpt-4-turbo',
                'gpt-4',
                'gpt-3.5-turbo',
            ],
            'claude' => [
                'claude-3-5-sonnet-20241022',
                'claude-3-5-haiku-20241022',
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307',
            ],
            'ollama' => [
                'llama3.2',
                'llama3.1',
                'llama3',
                'llama2',
                'mistral',
                'codellama',
                'phi3',
            ],
            'openrouter' => [
                'openai/gpt-4o',
                'openai/gpt-4-turbo',
                'anthropic/claude-3-5-sonnet',
                'anthropic/claude-3-opus',
                'meta-llama/llama-3.1-70b',
                'google/gemini-pro',
                'mistralai/mistral-7b-instruct',
            ],
            'deep_seek' => [
                'deepseek-coder-v2',
                'deepseek-chat',
            ],
            default => [],
        };
    }
}