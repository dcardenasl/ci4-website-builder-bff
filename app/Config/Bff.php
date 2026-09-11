<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;
use RuntimeException;

/**
 * BFF configuration — the **local server**'s view of the world.
 *
 * Owns three things the BFF itself decides:
 *   - which upstream hub to talk to (`hubUrl`)
 *   - which upstream domain app to talk to (`domainUrl`, optional)
 *   - which client origins to accept (`allowedOrigins`)
 *
 * For the **upstream client's** view (M2M auth, paths, timeouts), see
 * {@see Hub} — they're intentionally separate so future BFF deployments
 * fronting a different hub API version only touch `Hub`. The shared piece
 * — the hub's base URL — is canonicalised under `bff.hubUrl` here; `hub.url`
 * is accepted as a fallback so older `.env` files keep working.
 *
 * Authentication is forward-only: the BFF does not validate bearer tokens, it relays
 * the client's `Authorization` header to the upstream. This config therefore
 * has nothing to say about signing secrets or sessions.
 */
class Bff extends BaseConfig
{
    /**
     * Base URL of the upstream hub (no trailing slash). e.g. http://localhost:8180
     *
     * Resolved from `bff.hubUrl` first; falls back to `hub.url` if unset.
     */
    public string $hubUrl = '';

    /** Base URL of the optional upstream domain app (no trailing slash). */
    public string $domainUrl = '';

    /** Shared key accepted by opt-in public-read routes. */
    public string $webAppKey = '';

    /** Direct SQL public-read support is deliberately opt-in. */
    public bool $publicReadEnabled = false;

    /**
     * Origins permitted by CORS. Populated from the comma-separated
     * `BFF_ALLOWED_ORIGINS` env var.
     *
     * @var list<string>
     */
    public array $allowedOrigins = [];

    public function __construct()
    {
        parent::__construct();

        $this->hubUrl = rtrim(self::resolveHubUrl(), '/');
        $this->domainUrl = rtrim(self::resolveEnvValue('BFF_DOMAIN_URL', 'bff.domainUrl'), '/');
        $this->webAppKey = self::resolveEnvValue('BFF_API_KEY', 'WEB_API_KEY');
        $this->publicReadEnabled = filter_var(
            env('BFF_PUBLIC_READ_SUPPORT', false),
            FILTER_VALIDATE_BOOL,
        );

        $rawOrigins = (string) env('BFF_ALLOWED_ORIGINS', '');
        $this->allowedOrigins = $this->parseCsv($rawOrigins);

        if (ENVIRONMENT === 'production' && $this->allowedOrigins === []) {
            throw new RuntimeException(
                'BFF misconfigured for production: BFF_ALLOWED_ORIGINS is empty. '
                . 'Set a comma-separated list of permitted client origins in .env.'
            );
        }
    }

    /**
     * Single resolver shared with {@see Hub} so both configs land on the same
     * hub URL regardless of which env var the operator wrote.
     */
    public static function resolveHubUrl(): string
    {
        $primary = self::resolveEnvValue('BFF_HUB_URL', 'bff.hubUrl');
        if ($primary !== '') {
            return $primary;
        }

        return self::resolveEnvValue('HUB_URL', 'hub.url');
    }

    /**
     * Resolve a container-friendly uppercase alias before the dotted CI4 key.
     * Apache/PHP can drop dotted process variables even when Docker injects
     * them correctly, so every runtime-critical setting has an explicit alias.
     */
    public static function resolveEnvValue(string $alias, string $legacy, string $default = ''): string
    {
        $value = getenv($alias);
        if ($value !== false && trim((string) $value) !== '') {
            return trim((string) $value);
        }

        $value = getenv($legacy);
        if ($value !== false && trim((string) $value) !== '') {
            return trim((string) $value);
        }

        $value = env($legacy, $default);

        return trim((string) ($value ?? $default));
    }

    /**
     * @return list<string>
     */
    private function parseCsv(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $items = array_map(static fn (string $item): string => trim($item), explode(',', $value));

        return array_values(array_filter($items, static fn (string $item): bool => $item !== ''));
    }
}
