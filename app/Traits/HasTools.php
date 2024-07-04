<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionEnum;
use ReflectionException;
use ReflectionParameter;
use RuntimeException;
use App\Attributes\Description;
use function Laravel\Prompts\info;
use function Termwind\render;

/**
 * Trait HasTools
 * @author Hermann D. Schimpf (hschimpf)
 * Refer https://github.com/openai-php/client/issues/285#issuecomment-1883895076
 */

trait HasTools {

    private array $registered_tools = [];

    /**
     * @throws ReflectionException
     */
    public function register(array $tool_classes): void {
        foreach ($tool_classes as $class_name) {
            if ( !class_exists($class_name)) {
                continue;
            }

            $tool_class = new ReflectionClass($class_name);
            $tool_name = Str::snake(basename(str_replace('\\', '/', $class_name)));

            if (! $tool_class->hasMethod('handle')) {
                Log::warning(sprintf('Tool class %s has no "handle" method', $tool_class));

                continue;
            }

            $tool_definition = [
                'type'     => 'function',
                'function' => [ 'name' => $tool_name ],
            ];

            // set function description, if it has one
            if ( !empty($descriptions = $tool_class->getAttributes(Description::class))) {
                $tool_definition['function']['description'] = implode(
                    separator: "\n",
                    array: array_map(static fn($td) => $td->newInstance()->value, $descriptions),
                );
            }

            if ($tool_class->getMethod('handle')->getNumberOfParameters() > 0) {
                $tool_definition['function']['parameters'] = $this->parseToolParameters($tool_class);
            }

            $this->registered_tools[ $class_name ] = $tool_definition;
        }
    }

    /**
     * @throws ReflectionException
     */
    public function call(string $tool_name, ?array $arguments = []): mixed {
        if (null === $tool_class = array_key_first(array_filter($this->registered_tools, static fn($registered_tools) => $registered_tools['function']['name'] === $tool_name))) {
            return null;
        }

        $tool_class = new ReflectionClass($tool_class);
        $handle_method = $tool_class->getMethod('handle');

        $params = [];
        foreach ($handle_method->getParameters() as $parameter) {
            if ( !array_key_exists($parameter->name, $arguments) && !$parameter->isOptional() && !$parameter->isDefaultValueAvailable()) {
                throw new RuntimeException(sprintf('Parameter %s is required', $parameter->name));
            }

            // check if parameter type is an Enum and add fetch a valid value
            if (($parameter_type = $parameter->getType()) !== null && !$parameter_type->isBuiltin()) {
                if (enum_exists($parameter_type->getName())) {
                    $params[$parameter->name] = $parameter_type->getName()::tryFrom($arguments[$parameter->name]) ?? $parameter->getDefaultValue();

                    continue;
                }
            }

            $params[$parameter->name] = $arguments[$parameter->name] ?? $parameter->getDefaultValue();
        }

        return $handle_method->invoke(new $tool_class->name, ...$params);
    }

    /**
     * @throws ReflectionException
     */
    public function handleTools($toolCalls): array {

        $toolOutputs = [];

        foreach ($toolCalls as $toolCall) {
            $output = $this->call($toolCall['function']['name'], json_decode($toolCall['function']['arguments'], true));
            $toolOutputs[] = [
                'tool_call_id' => $toolCall['id'],
                'output'       => $output,
            ];

            $name = $toolCall['function']['name'];

            render(<<<HTML
                <div class="text-green-300">
                    <div class="font-bold">✅ $name: </div>
                    <div>
                        <pre>
                            $output</pre>
                        </div>
                    </div>
                HTML);

        }

        return $toolOutputs;
    }

    /**
     * @throws ReflectionException
     */
    private function parseToolParameters(ReflectionClass $tool): array {
        $parameters = [ 'type' => 'object' ];

        if (count($method_parameters = $tool->getMethod('handle')->getParameters()) > 0) {
            $parameters['properties'] = [];
        }

        foreach ($method_parameters as $method_parameter) {
            $property = [ 'type' => $this->getToolParameterType($method_parameter) ];

            // set property description, if it has one
            if (!empty($descriptions = $method_parameter->getAttributes(Description::class))) {
                $property['description'] = implode(
                    separator: "\n",
                    array: array_map(static fn($pd) => $pd->newInstance()->value, $descriptions),
                );
            }

            // register parameter to the required properties list if it's not optional
            if ( !$method_parameter->isOptional()) {
                $parameters['required'] ??= [];
                $parameters['required'][] = $method_parameter->getName();
            }

            // check if parameter type is an Enum and add it's valid values to the property
            if (($parameter_type = $method_parameter->getType()) !== null && !$parameter_type->isBuiltin()) {
                if (enum_exists($parameter_type->getName())) {
                    $property['type'] = 'string';
                    $property['enum'] = array_column((new ReflectionEnum($parameter_type->getName()))->getConstants(), 'value');
                }
            }

            $parameters['properties'][$method_parameter->getName()] = $property;
        }

        return $parameters;
    }

    private function getToolParameterType(ReflectionParameter $parameter): string {
        if (null === $parameter_type = $parameter->getType()) {
            return 'string';
        }

        if ( !$parameter_type->isBuiltin()) {
            return $parameter_type->getName();
        }

        return match ($parameter_type->getName()) {
            'bool'  => 'boolean',
            'int'   => 'integer',
            'float' => 'number',

            default => 'string',
        };
    }

}
