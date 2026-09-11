<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Exceptions;

use App\Libraries\Exceptions\AppExceptionHandler;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use RuntimeException;

/**
 * @internal
 */
final class AppExceptionHandlerTest extends CIUnitTestCase
{
    public function testMessageIsSanitizedOutsideDevelopment(): void
    {
        $exception = new RuntimeException(
            'SQLSTATE[42S02]: table `cms_languages` not found at /var/www/app/Foo.php:123'
        );
        $response = Services::response(null, false);

        (new AppExceptionHandler())->handle(
            $exception,
            Services::request(false),
            $response,
            500,
            1
        );

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertSame(lang('Api.serverError'), $body['message']);
        $this->assertStringNotContainsString('cms_languages', $body['message']);
        $this->assertStringNotContainsString('/var/www/app', $body['message']);
    }

    public function testResponseIsAlwaysValidJson(): void
    {
        $response = Services::response(null, false);

        (new AppExceptionHandler())->handle(
            new RuntimeException('boom'),
            Services::request(false),
            $response,
            503,
            1
        );

        $this->assertSame(503, $response->getStatusCode());
        $this->assertIsArray(json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }
}
