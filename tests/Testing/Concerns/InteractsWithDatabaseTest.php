<?php

namespace QuantaQuirk\Tests\Testing\Concerns;

use QuantaQuirk\Database\ConnectionInterface;
use QuantaQuirk\Database\Query\Expression;
use QuantaQuirk\Database\Query\Grammars\MySqlGrammar;
use QuantaQuirk\Database\Query\Grammars\PostgresGrammar;
use QuantaQuirk\Database\Query\Grammars\SQLiteGrammar;
use QuantaQuirk\Database\Query\Grammars\SqlServerGrammar;
use QuantaQuirk\Foundation\Testing\Concerns\InteractsWithDatabase;
use QuantaQuirk\Support\Facades\DB;
use QuantaQuirk\Support\Facades\Facade;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class InteractsWithDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testCastToJsonSqlite()
    {
        $grammar = new SQLiteGrammar();

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '{"foo":"bar"}'
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonPostgres()
    {
        $grammar = new PostgresGrammar();

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '{"foo":"bar"}'
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonSqlServer()
    {
        $grammar = new SqlServerGrammar();

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]')
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]')
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('{"foo":"bar"}')
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonMySql()
    {
        $grammar = new MySqlGrammar();

        $this->assertEquals(<<<'TEXT'
        cast('["foo","bar"]' as json)
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        cast('["foo","bar"]' as json)
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        cast('{"foo":"bar"}' as json)
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    protected function castAsJson($value, $grammar)
    {
        $connection = m::mock(ConnectionInterface::class);

        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);

        $connection->shouldReceive('getPdo->quote')->andReturnUsing(function ($value) {
            return "'".$value."'";
        });

        DB::shouldReceive('connection')->andReturn($connection);

        DB::shouldReceive('raw')->andReturnUsing(function ($value) {
            return new Expression($value);
        });

        $instance = new class
        {
            use InteractsWithDatabase;
        };

        return $instance->castAsJson($value)->getValue($grammar);
    }
}
