<?php

namespace App\Integrations\Ollama\Requests;

use App\Data\AIModelData;
use Illuminate\Support\Collection;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class ListModelsRequest extends Request
{
    protected Method $method = Method::GET;

    /**
     * The endpoint
     */
    public function resolveEndpoint(): string
    {
        return '/api/tags';
    }

    public function createDtoFromResponse(Response $response): Collection
    {
        $jsonResponse = $response->json();
        
        // Check if 'models' key exists in the response
        if (!isset($jsonResponse['models'])) {
            // Handle the case when 'models' key is not present, returning an empty collection
            return collect([]);
        }

        $data = $jsonResponse['models'];

        return collect($data)->map(function ($model) {
            return AIModelData::from($model);
        });
    }
}
