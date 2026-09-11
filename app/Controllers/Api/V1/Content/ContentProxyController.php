<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Content;

use App\Controllers\BaseProxyController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/** Generic one-to-one proxy for a site's domain-owned content resources. */
final class ContentProxyController extends BaseProxyController
{
    public function forward(string $path = ''): ResponseInterface
    {
        return $this->proxy(
            Services::domainClient(),
            '/api/v1/content/' . ltrim($path, '/'),
        );
    }
}
