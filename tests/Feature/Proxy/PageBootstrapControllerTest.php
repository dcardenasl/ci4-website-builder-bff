<?php

declare(strict_types=1);

namespace Tests\Feature\Proxy;

use App\Libraries\Domain\DomainClient;
use Config\Services;
use Tests\Support\ApiTestCase;

final class PageBootstrapControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Services::resetSingle('domainClient');
        config('Bff')->webAppKey = 'public-key';
    }

    protected function tearDown(): void
    {
        config('Bff')->webAppKey = '';
        Services::resetSingle('domainClient');
        parent::tearDown();
    }

    public function testAggregatesGenericPageSourcesAndForwardsAppKey(): void
    {
        $domain = $this->createMock(DomainClient::class);
        $domain->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                ['id' => 10, 'slug' => 'home'],
                ['items' => []],
                ['site_name' => 'Example'],
            );
        Services::injectMock('domainClient', $domain);

        $result = $this
            ->withHeaders(['X-App-Key' => 'public-key'])
            ->get('/api/v1/public-read/pages/es/home');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('home', $body['data']['page']['slug']);
        $this->assertSame([], $body['data']['navigation']['items']);
    }

    public function testAggregationFailsFastWhenARequiredSourceIsUnavailable(): void
    {
        $domain = $this->createMock(DomainClient::class);
        $domain->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                ['id' => 10],
                $this->throwException(new \dcardenasl\Ci4ApiCore\Exceptions\ServiceUnavailableException('down')),
            );
        Services::injectMock('domainClient', $domain);

        $result = $this
            ->withHeaders(['X-App-Key' => 'public-key'])
            ->get('/api/v1/public-read/pages/es/home');

        $result->assertStatus(503);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertSame('error', $body['status']);
    }
}
