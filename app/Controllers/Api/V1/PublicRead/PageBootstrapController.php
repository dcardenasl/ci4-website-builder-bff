<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\PublicRead;

use App\Controllers\BaseProxyController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Generic page bootstrap example: one BFF response, three domain reads.
 *
 * The endpoint composes only generic CMS contracts. A site's domain decides
 * which content exists; no business resource is named here.
 */
final class PageBootstrapController extends BaseProxyController
{
    public function show(string $locale, string $path): ResponseInterface
    {
        $domain = Services::domainClient();
        $bearer = $this->extractBearerToken();
        $headers = $this->publicHeaders();
        $normalizedPath = trim($path, '/');

        return $this->aggregate([
            'page' => static fn () => $domain->get(
                '/api/v1/public/' . rawurlencode($locale) . '/pages/' . $normalizedPath,
                $bearer,
                $headers,
            ),
            'navigation' => static fn () => $domain->get(
                '/api/v1/public/menus/' . rawurlencode($locale),
                $bearer,
                $headers,
            ),
            'settings' => static fn () => $domain->get(
                '/api/v1/public/settings',
                $bearer,
                $headers,
            ),
        ]);
    }

    /** @return array<string, string> */
    private function publicHeaders(): array
    {
        $appKey = $this->request->getHeaderLine('X-App-Key');

        return $appKey === '' ? [] : ['X-App-Key' => $appKey];
    }
}
