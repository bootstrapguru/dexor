<?php

namespace App\Integrations\Claude\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

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
}
