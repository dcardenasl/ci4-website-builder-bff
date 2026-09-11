<?php

declare(strict_types=1);

namespace App\Documentation\PublicRead;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/public-read/pages/{locale}/{path}',
    tags: ['PublicRead'],
    summary: 'Compose a generic page bootstrap',
    description: 'Combines page, navigation and site settings reads from the configured domain. The route requires the trusted server-to-server X-App-Key.',
    parameters: [
        new OA\Parameter(name: 'locale', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'path', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'X-App-Key', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Composed generic page payload'),
        new OA\Response(response: 401, description: 'Missing or invalid app key'),
        new OA\Response(response: 503, description: 'A required domain source is unavailable'),
    ]
)]
class PageBootstrapEndpoints
{
}
