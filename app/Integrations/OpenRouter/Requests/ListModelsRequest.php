<?php

namespace App\Integrations\OpenRouter\Requests;

use App\Data\AIModelData;
use Illuminate\Support\Collection;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class ListModelsRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/models';
    }

    public function createDtoFromResponse(Response $response): Collection
    {
        $data = $response->json()['data'];
        return collect($data)->map(fn ($model) => AIModelData::from(['name' => $model['id']]));
    }
}
