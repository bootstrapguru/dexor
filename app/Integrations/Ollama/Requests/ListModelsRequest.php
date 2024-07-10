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
        return '/tags';
    }

    public function createDtoFromResponse(Response $response): Collection
    {
        $data = $response->json()['models'];
        return collect($data)->map(fn($model) => AIModelData::from($model));
    }
}
