<?php

namespace App\Integrations\OpenAI\Requests;

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
        return '/models';
    }

    public function createDtoFromResponse(Response $response): Collection
    {
        return collect($response->json())->map(fn ($model) => new AIModelData($model));
    }
}
