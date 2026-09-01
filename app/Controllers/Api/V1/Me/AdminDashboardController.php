<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Me;

use App\Controllers\BaseProxyController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\AuthenticationException;
use dcardenasl\Ci4ApiCore\Http\ApiResponse;
use dcardenasl\Ci4ApiCore\Http\ContextHolder;

/** Stable, permission-aware dashboard projection for Builder Admin. */
final class AdminDashboardController extends BaseProxyController
{
    public function index(): ResponseInterface
    {
        $context = ContextHolder::get();
        $bearer = $this->extractBearerToken();
        if ($context === null || $bearer === null) {
            throw new AuthenticationException('Missing authenticated user context.');
        }

        $startedAt = hrtime(true);
        try {
            $upstream = Services::domainClient()->get('/api/v1/admin/dashboard/summary', $bearer);
            $elapsed = (hrtime(true) - $startedAt) / 1_000_000;
            $data = is_array($upstream['data'] ?? null) ? $upstream['data'] : $upstream;
            $data = is_array($data['data'] ?? null) ? $data['data'] : $data;
            $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];

            return $this->response->setJSON(ApiResponse::success([
                'version' => (int) ($data['version'] ?? 1),
                'generated_at' => (string) ($data['generated_at'] ?? date(DATE_ATOM)),
                'source' => [
                    'hub' => 'ok',
                    'state' => 'ok',
                    'diagnostics' => ['domain_latency_ms' => round($elapsed, 2)],
                ],
                'sections' => [
                    'hub' => [
                        'users' => is_array($sections['users'] ?? null) ? $sections['users'] : [],
                        'files' => is_array($sections['files'] ?? null) ? $sections['files'] : [],
                        'metrics' => is_array($sections['metrics'] ?? null) ? $sections['metrics'] : [],
                    ],
                ],
            ]));
        } catch (\Throwable $exception) {
            log_message('error', 'Builder admin dashboard upstream unavailable: ' . $exception->getMessage());

            return $this->response
                ->setStatusCode(503)
                ->setJSON(ApiResponse::success([
                    'version' => 1,
                    'generated_at' => date(DATE_ATOM),
                    'source' => [
                        'hub' => 'unavailable',
                        'state' => 'unavailable',
                        'diagnostics' => ['domain_latency_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2)],
                    ],
                    'sections' => ['hub' => []],
                ]));
        }
    }
}
