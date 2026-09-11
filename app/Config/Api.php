<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/** Version metadata consumed by the BFF's discovery endpoint. */
class Api extends BaseConfig
{
    public int $rateLimitWindow = 60;
    public int $rateLimitRequests = 60;
    public int $rateLimitUserRequests = 100;

    /** @var array<string, array{status: string, deprecated_at: ?string, sunset_at: ?string, successor: ?string}> */
    public array $apiVersions = [
        'v1' => [
            'status' => 'current',
            'deprecated_at' => null,
            'sunset_at' => null,
            'successor' => null,
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->rateLimitWindow = (int) $this->envValue('RATE_LIMIT_WINDOW', $this->rateLimitWindow);
        $this->rateLimitRequests = (int) $this->envValue('RATE_LIMIT_REQUESTS', $this->rateLimitRequests);
        $this->rateLimitUserRequests = (int) $this->envValue('RATE_LIMIT_USER_REQUESTS', $this->rateLimitUserRequests);
    }

    private function envValue(string $key, int $default): int
    {
        $value = getenv($key);

        return $value === false ? $default : (int) $value;
    }
}
