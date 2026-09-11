<?php

declare(strict_types=1);

namespace Tests\Integration\PublicRead;

use App\Libraries\Domain\DomainClient;
use Config\Services;
use Tests\Support\ApiTestCase;

final class PageBootstrapIntegrationTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $domain = $this->createMock(DomainClient::class);
        $domain->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                ['slug' => 'home'],
                ['items' => []],
                ['site_name' => 'Example'],
            );
        Services::injectMock('domainClient', $domain);
    }

    protected function tearDown(): void
    {
        Services::resetSingle('domainClient');
        parent::tearDown();
    }

    public function testRouteValidatesAppKeyAndReturnsComposedResponse(): void
    {
        $result = $this
            ->withHeaders(['X-App-Key' => 'integration-key'])
            ->get('/api/v1/public-read/pages/es/home');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('home', $body['data']['page']['slug']);
    }
}
