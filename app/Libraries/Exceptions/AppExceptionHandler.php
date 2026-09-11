<?php

declare(strict_types=1);

namespace App\Libraries\Exceptions;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Exceptions\BaseExceptionHandler;
use Throwable;

class AppExceptionHandler extends BaseExceptionHandler
{
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode
    ): void {
        $message = ENVIRONMENT === 'development'
            ? $exception::class . ': ' . $exception->getMessage()
            : lang('Api.serverError');

        $response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setBody(json_encode([
                'success' => false,
                'message' => $message,
            ], JSON_THROW_ON_ERROR))
            ->send();

        if (ENVIRONMENT !== 'testing') {
            exit($exitCode);
        }
    }
}
