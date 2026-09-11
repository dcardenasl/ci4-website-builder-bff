<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseService;

require_once __DIR__ . '/ApiCoreServices.php';

/**
 * Services Configuration file.
 *
 * BFF starter: stateless gateway. No DB, no audit chain, no domain factories.
 * Only HubClient and the ci4-api-core HTTP/DTO helpers via ApiCoreServices.
 */
class Services extends BaseService
{
    use ApiCoreServices;

    public static function hubClient(bool $getShared = true): \App\Libraries\Hub\HubClient
    {
        if ($getShared) {
            return static::getSharedInstance('hubClient');
        }

        /** @var \Config\Hub $hubConfig */
        $hubConfig = config('Hub');

        $coreHubConfig = new \dcardenasl\Ci4ApiCore\Http\Client\HubClientConfig(
            url: $hubConfig->url,
            apiKey: $hubConfig->apiKey,
            introspectPath: $hubConfig->introspectPath ?? '/api/v1/auth/introspect',
            serviceTokenPath: $hubConfig->serviceTokenPath ?? '/api/v1/auth/service-token',
            permissionsPath: $hubConfig->permissionsPath ?? '/api/v1/iam/permissions',
            introspectCacheTtl: $hubConfig->introspectCacheTtl ?? 60,
            serviceTokenSafetyMargin: $hubConfig->serviceTokenSafetyMargin ?? 30,
            httpTimeout: $hubConfig->httpTimeout ?? 5,
        );

        return new \App\Libraries\Hub\HubClient(
            $coreHubConfig,
            \Config\Services::curlrequest(),
            \Config\Services::cache()
        );
    }

    public static function domainClient(bool $getShared = true): \App\Libraries\Domain\DomainClient
    {
        if ($getShared) {
            return static::getSharedInstance('domainClient');
        }

        /** @var \Config\Bff $bffConfig */
        $bffConfig = config('Bff');
        $baseUrl   = $bffConfig->domainUrl;

        if ($baseUrl === '') {
            throw new \InvalidArgumentException('Domain client misconfigured: bff.domainUrl is not defined.');
        }

        return new \App\Libraries\Domain\DomainClient(
            \Config\Services::curlrequest(),
            $baseUrl
        );
    }

    public static function healthChecker(bool $getShared = true): \dcardenasl\Ci4ApiCore\Monitoring\HealthChecker
    {
        if ($getShared) {
            return static::getSharedInstance('healthChecker');
        }

        return new \dcardenasl\Ci4ApiCore\Monitoring\HealthChecker();
    }

    public static function publicReadSupport(bool $getShared = true): \App\PublicRead\PublicReadSupport
    {
        if ($getShared) {
            return static::getSharedInstance('publicReadSupport');
        }

        return new \App\PublicRead\PublicReadSupport();
    }

    /**
     * The Request Service
     *
     * @param \Config\App|bool $getShared
     */
    public static function request($getShared = true): \dcardenasl\Ci4ApiCore\Http\ApiRequest
    {
        if (is_bool($getShared) && $getShared) {
            return static::getSharedInstance('request');
        }

        $config = $getShared instanceof \Config\App ? $getShared : config('App');

        return new \dcardenasl\Ci4ApiCore\Http\ApiRequest(
            $config,
            static::uri(),
            'php://input',
            new \CodeIgniter\HTTP\UserAgent()
        );
    }
}
