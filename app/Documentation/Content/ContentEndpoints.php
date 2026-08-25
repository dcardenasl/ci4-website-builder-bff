<?php

declare(strict_types=1);

namespace App\Documentation\Content;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/content/{path}',
    tags: ['Content'],
    summary: 'Forward a generic content request',
    description: 'Passes a generic content path to the configured domain app. The domain remains responsible for authentication and authorization.',
    parameters: [
        new OA\Parameter(name: 'path', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Response returned by the domain app'),
        new OA\Response(response: 503, description: 'Domain app unavailable'),
    ]
)]
class ContentEndpoints
{
}
