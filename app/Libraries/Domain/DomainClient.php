<?php

declare(strict_types=1);

namespace App\Libraries\Domain;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\IncomingRequest;
use dcardenasl\Ci4ApiCore\Http\Client\AbstractServiceClient;

/**
 * Generic HTTP client for upstream domain apps (e.g., ci4-domain-starter apps).
 *
 * Inherits request routing, automatic linear retries on 5xx or network errors,
 * X-Request-Id distributed tracing propagation, and status code-to-exception mapping
 * from the core's {@see AbstractServiceClient}.
 */
class DomainClient extends AbstractServiceClient
{
    public function __construct(
        CURLRequest $http,
        string $baseUrl,
        int $timeoutSeconds = 5
    ) {
        parent::__construct(
            http: $http,
            baseUrl: $baseUrl,
            timeoutSeconds: $timeoutSeconds
        );
    }

    /**
     * Fetch a decoded JSON resource with optional visitor and app-key headers.
     *
     * @param array<string, string> $additionalHeaders
     * @return array<string, mixed>
     */
    public function get(string $path, ?string $bearerToken = null, array $additionalHeaders = []): array
    {
        $headers = $additionalHeaders;
        if ($bearerToken !== null && $bearerToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        return $this->request('GET', $path, ['headers' => $headers]);
    }

    /**
     * Widens the core allow-list with the headers webhook providers use to
     * sign their payloads (e.g. SendGrid Signed Event Webhooks) plus the
     * generic shared-token header, so upstream domains can authenticate
     * proxied webhooks end-to-end.
     *
     * @return array<string, string>
     */
    protected function buildForwardedHeaders(IncomingRequest $incoming): array
    {
        $headers = parent::buildForwardedHeaders($incoming);

        $extra = [
            'X-App-Key',
            'X-Twilio-Email-Event-Webhook-Signature',
            'X-Twilio-Email-Event-Webhook-Timestamp',
            'X-Webhook-Token',
        ];

        foreach ($extra as $name) {
            $value = $incoming->getHeaderLine($name);
            if ($value !== '') {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
