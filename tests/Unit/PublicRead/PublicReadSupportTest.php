<?php

declare(strict_types=1);

namespace Tests\Unit\PublicRead;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use LogicException;

final class PublicReadSupportTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        config('Bff')->publicReadEnabled = false;
        Services::resetSingle('publicReadSupport');
        parent::tearDown();
    }

    public function testDirectReadsAreDisabledByDefault(): void
    {
        $support = Services::publicReadSupport(false);

        $this->assertFalse($support->isEnabled());
        $this->expectException(LogicException::class);
        $support->connection();
    }
}
