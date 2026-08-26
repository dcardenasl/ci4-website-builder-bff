<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Sql;

use App\Support\Sql\JsonArrayAggregateSql;
use App\Support\Sql\JsonProjectionDecoder;
use CodeIgniter\Test\CIUnitTestCase;

final class SqlProjectionSupportTest extends CIUnitTestCase
{
    public function testMariaDb103UsesSelectOnlyCompatibleAggregation(): void
    {
        $parts = JsonArrayAggregateSql::forDriver('MySQLi', '10.3.38-MariaDB-cll-lve');

        $expression = $parts['aggregate'] . '(' . $parts['object'] . "('id', id)" . $parts['suffix'] . ')';

        $this->assertSame(
            "CONCAT('[', GROUP_CONCAT(JSON_OBJECT('id', id) SEPARATOR ','), ']')",
            $expression,
        );
    }

    public function testMariaDbWithoutVersionUsesSafeFallback(): void
    {
        $parts = JsonArrayAggregateSql::forDriver('MySQLi', 'MariaDB');

        $expression = $parts['aggregate'] . '(' . $parts['object'] . "('id', id)" . $parts['suffix'] . ')';

        $this->assertSame(
            "CONCAT('[', GROUP_CONCAT(JSON_OBJECT('id', id) SEPARATOR ','), ']')",
            $expression,
        );
    }

    public function testSqliteKeepsNativeJsonAggregation(): void
    {
        $this->assertSame(
            [
                'aggregate' => 'json_group_array',
                'object' => 'json_object',
                'suffix' => '',
            ],
            JsonArrayAggregateSql::forDriver('SQLite3', '3.45.0'),
        );
    }

    public function testModernMariaDbKeepsJsonArrayAgg(): void
    {
        $this->assertSame(
            [
                'aggregate' => 'JSON_ARRAYAGG',
                'object' => 'JSON_OBJECT',
                'suffix' => '',
            ],
            JsonArrayAggregateSql::forDriver('MySQLi', '10.6.18-MariaDB'),
        );
    }

    public function testProjectionDecoderRejectsMalformedOrScalarValues(): void
    {
        $this->assertSame([], JsonProjectionDecoder::decodeList('{invalid'));
        $this->assertSame([], JsonProjectionDecoder::decodeList('null'));
        $this->assertSame([], JsonProjectionDecoder::decodeList(['scalar', 3, null]));
    }

    public function testProjectionDecoderKeepsOnlyAssociativeRows(): void
    {
        $this->assertSame(
            [['id' => 1], ['id' => 2]],
            JsonProjectionDecoder::decodeList('[{"id":1},"ignored",{"id":2}]'),
        );
    }
}
