<?php

declare(strict_types=1);

namespace App\Libraries\Hub;

use dcardenasl\Ci4ApiCore\Http\Client\HubClient as CoreHubClient;

/** BFF-specific structured reads over the shared Hub client. */
class HubClient extends CoreHubClient
{
    /**
     * Fetch a decoded JSON resource while preserving the hub app key.
     *
     * @return array<string, mixed>
     */
    public function get(string $path, ?string $bearerToken = null): array
    {
        $headers = $this->appKeyHeaders();
        if ($bearerToken !== null && $bearerToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        return $this->request('GET', $path, ['headers' => $headers]);
    }

    /**
     * Fetch the canonical authenticated-user projection from the Hub.
     *
     * @return array<string, mixed>
     */
    public function getAuthenticatedUser(string $bearerToken): array
    {
        return $this->get('/api/v1/auth/me', $bearerToken);
    }
}
