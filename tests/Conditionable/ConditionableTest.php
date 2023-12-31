<?php

namespace QuantaQuirk\Tests\Conditionable;

use QuantaQuirk\Database\Capsule\Manager as DB;
use QuantaQuirk\Database\Eloquent\Builder;
use QuantaQuirk\Database\Eloquent\Model;
use QuantaQuirk\Support\HigherOrderWhenProxy;
use PHPUnit\Framework\TestCase;

class ConditionableTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();
    }

    public function testWhen(): void
    {
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->when(true));
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->when(false));
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->when());
        $this->assertInstanceOf(Builder::class, TestConditionableModel::query()->when(false, null));
        $this->assertInstanceOf(Builder::class, TestConditionableModel::query()->when(true, function () {
        }));
    }

    public function testUnless(): void
    {
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->unless(true));
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->unless(false));
        $this->assertInstanceOf(HigherOrderWhenProxy::class, TestConditionableModel::query()->unless());
        $this->assertInstanceOf(Builder::class, TestConditionableModel::query()->unless(true, null));
        $this->assertInstanceOf(Builder::class, TestConditionableModel::query()->unless(false, function () {
        }));
    }
}

class TestConditionableModel extends Model
{
}
