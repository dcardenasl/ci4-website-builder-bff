<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Verifies that the env-driven `app.proxyIPs` parser builds the array CI4
 * expects from a comma-separated `cidr=header` string. Without this the
 * deployment configuration would have to edit Config/App.php directly,
 * which forks the file from the starter kit on every change.
 */
class AppProxyIPsTest extends CIUnitTestCase
{
    public function testContainerFriendlyBaseUrlAliasIsApplied(): void
    {
        // env() reads $_ENV/$_SERVER before falling back to getenv(), and Dotenv
        // already populated both from .env's own APP_BASE_URL default at bootstrap —
        // a plain putenv() here would be shadowed by that value. Override all three
        // to actually exercise the alias regardless of what .env defines.
        $previousEnv    = $_ENV['APP_BASE_URL'] ?? null;
        $previousServer = $_SERVER['APP_BASE_URL'] ?? null;
        $previousGetenv = getenv('APP_BASE_URL');

        $_ENV['APP_BASE_URL']    = 'http://bff.test:8188';
        $_SERVER['APP_BASE_URL'] = 'http://bff.test:8188';
        putenv('APP_BASE_URL=http://bff.test:8188');

        try {
            $config = new App();
            $this->assertSame('http://bff.test:8188/', $config->baseURL);
        } finally {
            if ($previousEnv === null) {
                unset($_ENV['APP_BASE_URL']);
            } else {
                $_ENV['APP_BASE_URL'] = $previousEnv;
            }
            if ($previousServer === null) {
                unset($_SERVER['APP_BASE_URL']);
            } else {
                $_SERVER['APP_BASE_URL'] = $previousServer;
            }
            if ($previousGetenv === false) {
                putenv('APP_BASE_URL');
            } else {
                putenv('APP_BASE_URL=' . $previousGetenv);
            }
        }
    }

    private function withProxyIpsEnv(string $value, callable $fn): void
    {
        $previous = getenv('app.proxyIPs');
        putenv('app.proxyIPs=' . $value);
        try {
            $fn();
        } finally {
            if ($previous === false) {
                putenv('app.proxyIPs');
            } else {
                putenv('app.proxyIPs=' . $previous);
            }
        }
    }

    public function testEmptyEnvLeavesProxyIpsEmpty(): void
    {
        $this->withProxyIpsEnv('', function (): void {
            $config = new App();
            $this->assertSame([], $config->proxyIPs);
        });
    }

    public function testSinglePairIsParsed(): void
    {
        $this->withProxyIpsEnv('10.0.0.0/8=X-Forwarded-For', function (): void {
            $config = new App();
            $this->assertSame(['10.0.0.0/8' => 'X-Forwarded-For'], $config->proxyIPs);
        });
    }

    public function testMultiplePairsAreParsed(): void
    {
        $this->withProxyIpsEnv('10.0.1.200=X-Forwarded-For,192.168.5.0/24=X-Real-IP', function (): void {
            $config = new App();
            $this->assertSame([
                '10.0.1.200'     => 'X-Forwarded-For',
                '192.168.5.0/24' => 'X-Real-IP',
            ], $config->proxyIPs);
        });
    }

    public function testWhitespaceAroundPairsIsTrimmed(): void
    {
        $this->withProxyIpsEnv(' 10.0.0.1 = X-Forwarded-For ,  192.168.0.0/16 = X-Real-IP ', function (): void {
            $config = new App();
            $this->assertSame([
                '10.0.0.1'       => 'X-Forwarded-For',
                '192.168.0.0/16' => 'X-Real-IP',
            ], $config->proxyIPs);
        });
    }

    public function testMalformedPairsAreSkipped(): void
    {
        // A bare entry without `=` should be silently dropped, not crash.
        $this->withProxyIpsEnv('10.0.0.1=X-Forwarded-For,no-equals-sign,=onlyHeader,onlyCidr=', function (): void {
            $config = new App();
            $this->assertSame(['10.0.0.1' => 'X-Forwarded-For'], $config->proxyIPs);
        });
    }
}
