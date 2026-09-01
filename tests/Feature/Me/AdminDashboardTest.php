<?php

declare(strict_types=1);

namespace Tests\Feature\Me;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Tests\Support\ApiTestCase;

/** Regression coverage for the Builder Admin's real BFF dashboard endpoint. */
final class AdminDashboardTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('hub.url=http://hub.test');
        putenv('hub.apiKey=test-key');
        config('Bff')->domainUrl = 'http://domain.test';
        Services::reset();
    }

    protected function tearDown(): void
    {
        putenv('hub.url');
        putenv('hub.apiKey');
        Services::reset();
        parent::tearDown();
    }

    public function testRequiresAuthenticatedContext(): void
    {
        $this->get('/api/v1/me/admin-dashboard')->assertStatus(401);
    }

    public function testReturnsStableDashboardProjectionFromDomain(): void
    {
        $this->mockUpstreamCalls([
            $this->jsonResponse(200, ['data' => [
                'valid' => true,
                'uid' => 42,
                'permissions' => ['users.read', 'files.read', 'metrics.read'],
                'exp' => time() + 3600,
            ]]),
            $this->jsonResponse(200, ['data' => [
                'version' => 1,
                'generated_at' => '2026-08-31T12:00:00+00:00',
                'sections' => [
                    'users' => ['total' => 4],
                    'files' => ['total' => 9],
                    'metrics' => ['requests' => 12],
                ],
            ]]),
        ]);

        $result = $this
            ->withHeaders(['Authorization' => 'Bearer valid-token'])
            ->get('/api/v1/me/admin-dashboard');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);

        $this->assertSame('success', $body['status']);
        $this->assertSame('ok', $body['data']['source']['hub']);
        $this->assertSame('ok', $body['data']['source']['state']);
        $this->assertSame(['total' => 4], $body['data']['sections']['hub']['users']);
        $this->assertSame(['total' => 9], $body['data']['sections']['hub']['files']);
        $this->assertSame(['requests' => 12], $body['data']['sections']['hub']['metrics']);
        $this->assertIsNumeric($body['data']['source']['diagnostics']['domain_latency_ms']);
    }

    /** @param list<ResponseInterface> $responses */
    private function mockUpstreamCalls(array $responses): void
    {
        $http = $this->createMock(CURLRequest::class);
        $http->method('request')->willReturnOnConsecutiveCalls(...$responses);
        Services::injectMock('curlrequest', $http);
    }

    /** @param array<string, mixed> $body */
    private function jsonResponse(int $status, array $body): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn(json_encode($body, JSON_THROW_ON_ERROR));

        return $response;
    }
}
