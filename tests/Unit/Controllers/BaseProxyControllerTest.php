<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\BaseProxyController;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Exceptions\ServiceUnavailableException;

final class BaseProxyControllerTest extends CIUnitTestCase
{
    public function testPartialAggregationKeepsHealthySources(): void
    {
        $controller = new class () extends BaseProxyController {
            /** @param array<string, callable(): array<string, mixed>> $calls */
            public function runPartial(array $calls): array
            {
                return $this->aggregatePartialData($calls);
            }
        };

        $result = $controller->runPartial([
            'healthy' => static fn (): array => ['value' => 1],
            'broken' => static function (): array {
                throw new ServiceUnavailableException('upstream unavailable');
            },
        ]);

        $this->assertSame('ok', $result['healthy']['state']);
        $this->assertSame(['value' => 1], $result['healthy']['data']);
        $this->assertSame('unavailable', $result['broken']['state']);
        $this->assertSame([], $result['broken']['data']);
    }

    public function testOverallStateDistinguishesPartialDegradation(): void
    {
        $controller = new class () extends BaseProxyController {
            /** @param list<string> $states */
            public function state(array $states): string
            {
                return $this->overallState($states);
            }
        };

        $this->assertSame('ok', $controller->state(['ok', 'ok']));
        $this->assertSame('partial', $controller->state(['ok', 'unavailable']));
        $this->assertSame('unavailable', $controller->state(['unavailable']));
    }
}
