# ci4-website-builder-bff

[![CI4](https://img.shields.io/badge/CodeIgniter-4.7-EF4223)](https://codeigniter.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-2563EB)](phpstan.neon)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

CodeIgniter 4 **Backend-for-Frontend** starter — an optional stateless HTTP gateway
that fronts a `ci4-api-starter` hub and one optional `ci4-domain-starter`
apps for decoupled clients (SPAs, mobile).

## Role

```
SPA / Mobile  ──▶  ci4-bff-starter (:8188)
                        ├─▶  hub (ci4-api-starter :8180)
                        └─▶  domain (ci4-domain-starter :8190, optional)
```

The BFF:

- **Forwards** the client's `Authorization: Bearer <jwt>` header as-is to
  the hub and/or domain — it never validates JWTs itself.
- Composes generic page data with `PageBootstrapController` and can expose
  direct read-only SQL only when `BFF_PUBLIC_READ_SUPPORT=true`.
- Provides **CORS multi-origin** support via `BFF_ALLOWED_ORIGINS`.
- Exposes **health endpoints** (`/ping`, `/health`, `/ready`, `/live`).
- Holds **no writable database**, **no user store**, **no permission model** of
  its own. The optional read-only seam is disabled by default.

## What it is not

- It is **not** an auth server — the hub issues JWTs and validates them.
- It is **not** a domain backend — business logic lives in
  `ci4-domain-starter`.
- It is **not** an admin UI — that's `ci4-admin-starter`.

## Architecture cheat sheet

```
Controller  →  HubClient  →  upstream HTTP call
              (service token cache, optional)
```

- `App\Libraries\Hub\HubClient` — single egress point. Holds the cached
  service token (renewed `Config\Hub::$serviceTokenSafetyMargin` seconds
  before expiry).
- `Config\Bff` — `hubUrl`, single `domainUrl`, parsed `allowedOrigins`, the
  public-read key and the opt-in read flag. Throws on empty origins in
  production.
- `Config\Cors` — wraps `Config\Bff::$allowedOrigins`.

All other infrastructure (base controllers, CORS filter, security
headers, locale, feature toggles) comes from the
[`dcardenasl/ci4-api-core`](https://packagist.org/packages/dcardenasl/ci4-api-core)
package — same as the hub and domain starters.

## Quick start

```bash
composer install
cp .env.example .env
# Edit .env: set APP_BASE_URL, BFF_HUB_URL, BFF_DOMAIN_URL and
# BFF_ALLOWED_ORIGINS. The dotted CI4 keys remain supported for CLI use.

php spark serve --port 8188
curl http://localhost:8188/ping
```

For a fully orchestrated multi-repo project, use
[`ci4-kickstart`](https://github.com/dcardenasl/ci4-kickstart) with the
`CI4_INCLUDE_BFF=y` flag.

## Required environment variables

| Variable | Purpose |
|---|---|
| `BFF_HUB_URL` | Base URL of the upstream hub (`bff.hubUrl` remains supported) |
| `BFF_DOMAIN_URL` | Base URL of the single upstream domain app (optional) |
| `APP_BASE_URL` | Public base URL used by the Apache/PHP runtime |
| `BFF_ALLOWED_ORIGINS` | Comma-separated list of permitted CORS origins |
| `encryption.key` | CI4 encryption key (32 bytes after `hex2bin:` decode) |
| `HUB_APP_CODE` / `HUB_API_KEY` | Only required if the BFF makes M2M calls to the hub |
| `BFF_API_KEY` | Shared key for trusted server-to-server public-read calls |
| `BFF_PUBLIC_READ_SUPPORT` | Enables the read-only SQL seam; default `false` |

## Adding a proxy endpoint

For hand-authored endpoints, create a routes file under
`app/Config/Routes/v1/*.php` and a thin controller under
`app/Controllers/Api/V1/` that uses `HubClient` (or
`Services::curlrequest()`) to call upstream. Pass the client's
`Authorization` header through; the hub/domain will return 401 if the
token is invalid.

When the BFF is generated through `ci4-kickstart`, any
`template.json.public_endpoints[]` entries are turned into transparent
passthrough routes automatically via `PublicProxyController`.

## Quality

```bash
composer quality   # PHPStan L8 + CS-Fixer dry-run + PHPUnit
composer cs-fix    # auto-fix code style
composer test      # PHPUnit only
```

## License

MIT — see [LICENSE](LICENSE).
