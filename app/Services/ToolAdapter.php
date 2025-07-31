<?php

namespace App\Services;

use App\Traits\HasTools;
use Prism\Prism\Facades\Tool;

class ToolAdapter
{
    use HasTools;
    
    private array $toolMap = [];
    
    /**
     * Get registered tools for external use
     */
    public function getRegisteredTools(): array
    {
        return $this->registered_tools;
    }
    
    /**
     * Register tool classes and create Prism-compatible tools
     */
    public function registerAndConvert(array $toolClasses): array
    {
        // First register tools using existing trait
        $this->register($toolClasses);
        
        $prismTools = [];
        
        // Convert each registered tool to Prism format
        foreach ($this->registered_tools as $className => $toolDef) {
            $toolName = $toolDef['function']['name'];
            
            // Create Prism tool with callback to our existing tool
            $prismTool = $this->convertToPrismTool($className, $toolDef);
            
            if ($prismTool) {
                $prismTools[] = $prismTool;
                $this->toolMap[$toolName] = $className;
            }
        }
        
        return $prismTools;
    }
    
    /**
     * Convert a single tool definition to Prism format
     */
    private function convertToPrismTool(string $className, array $toolDef): ?\Prism\Prism\Tool
    {
        $functionDef = $toolDef['function'];
        $toolName = $functionDef['name'];
        $description = $functionDef['description'] ?? '';
        
        // Create Prism tool
        $tool = Tool::as($toolName)
            ->for($description)
            ->using(function (...$args) use ($toolName) {
                // Use the existing call method from HasTools trait
                // First argument will be the tool name, rest are the actual arguments
                $arguments = [];
                $paramNames = array_keys($this->registered_tools[$this->toolMap[$toolName]]['function']['parameters']['properties'] ?? []);
                
                foreach ($paramNames as $index => $paramName) {
                    if (isset($args[$index])) {
                        $arguments[$paramName] = $args[$index];
                    }
                }
                
                return $this->call($toolName, $arguments);
            });
        
        // Add parameters
        if (isset($functionDef['parameters']['properties'])) {
            foreach ($functionDef['parameters']['properties'] as $paramName => $paramDef) {
                $this->addParameterToTool($tool, $paramName, $paramDef, 
                    in_array($paramName, $functionDef['parameters']['required'] ?? []));
            }
        }
        
        return $tool;
    }
    
    /**
     * Add a parameter to a Prism tool based on type
     */
    private function addParameterToTool(\Prism\Prism\Tool $tool, string $paramName, array $paramDef, bool $isRequired): void
    {
        $description = $paramDef['description'] ?? '';
        $type = $paramDef['type'] ?? 'string';
        
        switch ($type) {
            case 'string':
                if (isset($paramDef['enum'])) {
                    $tool->withEnumParameter($paramName, $description, $paramDef['enum'], $isRequired);
                } else {
                    $tool->withStringParameter($paramName, $description, $isRequired);
                }
                break;
                
            case 'integer':
            case 'number':
                $tool->withNumberParameter($paramName, $description, $isRequired);
                break;
                
            case 'boolean':
                $tool->withBooleanParameter($paramName, $description, $isRequired);
                break;
                
            case 'array':
                $tool->withArrayParameter($paramName, $description, $isRequired);
                break;
                
            case 'object':
                // For object parameters, we'll use string and expect JSON
                $tool->withStringParameter($paramName, $description . ' (JSON object)', $isRequired);
                break;
                
            default:
                // Default to string for unknown types
                $tool->withStringParameter($paramName, $description, $isRequired);
                break;
        }
    }
    
    /**
     * Get the original tool class name from tool name
     */
    public function getToolClass(string $toolName): ?string
    {
        return $this->toolMap[$toolName] ?? null;
    }
}