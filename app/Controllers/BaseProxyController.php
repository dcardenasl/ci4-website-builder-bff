<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\RequestTelemetry;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Exceptions\ApiException;
use dcardenasl\Ci4ApiCore\Http\ApiResponse;
use dcardenasl\Ci4ApiCore\Http\Client\AbstractServiceClient;
use dcardenasl\Ci4ApiCore\Support\ExceptionFormatter;
use LogicException;
use Throwable;

/** Shared proxy and composition primitives for stateless BFF controllers. */
abstract class BaseProxyController extends Controller
{
    /** @var list<string> */
    protected array $forwardResponseHeaders = ['Content-Type', 'Content-Language'];

    protected function proxy(AbstractServiceClient $client, string $upstreamPath): ResponseInterface
    {
        if (! $this->request instanceof IncomingRequest) {
            throw new LogicException('Proxy endpoints can only be reached via HTTP.');
        }

        try {
            $upstream = $client->forward($this->request, $upstreamPath);
            $contentType = $upstream->getHeaderLine('Content-Type') ?: 'application/json';

            $this->response
                ->setStatusCode($upstream->getStatusCode())
                ->setContentType($contentType)
                ->setBody((string) $upstream->getBody());

            foreach ($this->forwardResponseHeaders as $header) {
                if ($header === 'Content-Type') {
                    continue;
                }

                $value = $upstream->getHeaderLine($header);
                if ($value !== '') {
                    $this->response->setHeader($header, $value);
                }
            }

            return $this->response;
        } catch (ApiException $exception) {
            return $this->respondWithException($exception);
        }
    }

    /** @param array<string, callable(): array<string, mixed>> $calls */
    protected function aggregate(array $calls): ResponseInterface
    {
        try {
            $data = [];
            foreach ($calls as $key => $call) {
                $startedAt = hrtime(true);
                try {
                    $data[$key] = $call();
                    RequestTelemetry::recordSource($key, $this->elapsedSince($startedAt), 'ok', 200);
                } catch (ApiException $exception) {
                    RequestTelemetry::recordSource($key, $this->elapsedSince($startedAt), 'unavailable', $exception->getStatusCode());
                    throw $exception;
                } catch (Throwable $exception) {
                    RequestTelemetry::recordSource($key, $this->elapsedSince($startedAt), 'unavailable', 500);
                    throw $exception;
                }
            }

            return $this->response->setJSON(ApiResponse::success($data));
        } catch (ApiException $exception) {
            return $this->respondWithException($exception);
        } catch (Throwable $exception) {
            log_message('error', sprintf(
                'aggregate() call failed: %s: %s',
                $exception::class,
                $exception->getMessage(),
            ));

            return $this->respondWithException(new \dcardenasl\Ci4ApiCore\Exceptions\ServiceUnavailableException(
                'One or more upstream sources are unavailable.',
            ));
        }
    }

    /**
     * Execute independent sources without hiding healthy data when one fails.
     *
     * @param array<string, callable(): array<array-key, mixed>> $calls
     * @return array<string, array{state: 'ok'|'unavailable', data: array<string, mixed>, duration_ms: float}>
     */
    protected function aggregatePartialData(array $calls): array
    {
        $data = [];

        foreach ($calls as $key => $call) {
            $startedAt = hrtime(true);
            try {
                $payload = $call();
                $data[$key] = [
                    'state' => 'ok',
                    'data' => $payload,
                    'duration_ms' => $this->elapsedSince($startedAt),
                ];
                RequestTelemetry::recordSource($key, $this->elapsedSince($startedAt), 'ok', 200);
            } catch (Throwable $exception) {
                $status = $exception instanceof ApiException ? $exception->getStatusCode() : 500;
                RequestTelemetry::recordSource($key, $this->elapsedSince($startedAt), 'unavailable', $status);
                log_message('error', sprintf(
                    'Partial aggregate source "%s" unavailable: %s: %s',
                    $key,
                    $exception::class,
                    $exception->getMessage(),
                ));

                $data[$key] = [
                    'state' => 'unavailable',
                    'data' => [],
                    'duration_ms' => $this->elapsedSince($startedAt),
                ];
            }
        }

        return $data;
    }

    /**
     * Return partial source states in a standard success envelope.
     *
     * The response remains 200 when at least one source is healthy and becomes
     * 503 only when every source is unavailable.
     *
     * @param array<string, callable(): array<array-key, mixed>> $calls
     */
    protected function aggregatePartial(array $calls): ResponseInterface
    {
        $sources = $this->aggregatePartialData($calls);
        $states = array_values(array_map(
            static fn (array $source): string => $source['state'],
            $sources,
        ));
        $state = $this->overallState($states);

        return $this->response
            ->setStatusCode($state === 'unavailable' ? 503 : 200)
            ->setJSON(ApiResponse::success([
                'source' => ['state' => $state],
                'sources' => $sources,
            ]));
    }

    /** @param callable(): ResponseInterface $operation */
    protected function handleOperation(callable $operation, string $source): ResponseInterface
    {
        $startedAt = hrtime(true);
        try {
            $response = $operation();
            $status = $response->getStatusCode();
            RequestTelemetry::recordSource(
                $source,
                $this->elapsedSince($startedAt),
                $status >= 400 ? 'unavailable' : 'ok',
                $status,
            );

            return $response;
        } catch (ApiException $exception) {
            RequestTelemetry::recordSource($source, $this->elapsedSince($startedAt), 'unavailable', $exception->getStatusCode());
            return $this->respondWithException($exception);
        } catch (Throwable $exception) {
            RequestTelemetry::recordSource($source, $this->elapsedSince($startedAt), 'unavailable', 503);
            log_message('error', sprintf(
                '%s unavailable: %s: %s',
                $source,
                $exception::class,
                $exception->getMessage(),
            ));

            return $this->respondWithException(new \dcardenasl\Ci4ApiCore\Exceptions\ServiceUnavailableException(
                $source . ' unavailable.',
            ));
        }
    }

    /** @param list<string> $states */
    protected function overallState(array $states): string
    {
        if ($states !== [] && count(array_unique($states)) === 1 && $states[0] === 'ok') {
            return 'ok';
        }

        if (in_array('ok', $states, true)) {
            return 'partial';
        }

        return 'unavailable';
    }

    protected function extractBearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function respondWithException(ApiException $exception): ResponseInterface
    {
        $result = ExceptionFormatter::format($exception);

        return $this->response
            ->setStatusCode($result->status)
            ->setJSON($result->body);
    }

    private function elapsedSince(int $startedAt): float
    {
        return (hrtime(true) - $startedAt) / 1_000_000;
    }
}
