<?php

namespace QuantaQuirk\Tests\Integration\Console\Events;

use QuantaQuirk\Contracts\Broadcasting\ShouldBroadcast;
use QuantaQuirk\Contracts\Queue\ShouldQueue;
use QuantaQuirk\Events\Dispatcher;
use QuantaQuirk\Foundation\Console\EventListCommand;
use Orchestra\Testbench\TestCase;

class EventListCommandTest extends TestCase
{
    public $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new Dispatcher();
        EventListCommand::resolveEventsUsing(fn () => $this->dispatcher);
    }

    public function testDisplayEmptyList()
    {
        $this->artisan(EventListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain("Your application doesn't have any events matching the given criteria.");
    }

    public function testDisplayEvents()
    {
        $this->dispatcher->subscribe(ExampleSubscriber::class);
        $this->dispatcher->listen(ExampleEvent::class, ExampleListener::class);
        $this->dispatcher->listen(ExampleEvent::class, ExampleQueueListener::class);
        $this->dispatcher->listen(ExampleBroadcastEvent::class, ExampleBroadcastListener::class);
        $this->dispatcher->listen(ExampleEvent::class, fn () => '');
        $closureLineNumber = __LINE__ - 1;
        $unixFilePath = str_replace('\\', '/', __FILE__);

        $this->artisan(EventListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('ExampleSubscriberEventName')
            ->expectsOutputToContain('⇂ QuantaQuirk\Tests\Integration\Console\Events\ExampleSubscriber@a')
            ->expectsOutputToContain('QuantaQuirk\Tests\Integration\Console\Events\ExampleBroadcastEvent (ShouldBroadcast)')
            ->expectsOutputToContain('⇂ QuantaQuirk\Tests\Integration\Console\Events\ExampleBroadcastListener')
            ->expectsOutputToContain('QuantaQuirk\Tests\Integration\Console\Events\ExampleEvent')
            ->expectsOutputToContain('⇂ Closure at: '.$unixFilePath.':'.$closureLineNumber);
    }

    public function testDisplayFilteredEvent()
    {
        $this->dispatcher->subscribe(ExampleSubscriber::class);
        $this->dispatcher->listen(ExampleEvent::class, ExampleListener::class);

        $this->artisan(EventListCommand::class, ['--event' => 'ExampleEvent'])
            ->assertSuccessful()
            ->doesntExpectOutput('  ExampleSubscriberEventName')
            ->expectsOutputToContain('ExampleEvent');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        EventListCommand::resolveEventsUsing(null);
    }
}

class ExampleSubscriber
{
    public function subscribe()
    {
        return [
            'ExampleSubscriberEventName' => [
                self::class.'@a',
                self::class.'@b',
            ],
        ];
    }

    public function a()
    {
    }

    public function b()
    {
    }
}

class ExampleEvent
{
}

class ExampleBroadcastEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        //
    }
}

class ExampleListener
{
    public function handle()
    {
    }
}

class ExampleQueueListener implements ShouldQueue
{
    public function handle()
    {
    }
}

class ExampleBroadcastListener
{
    public function handle()
    {
        //
    }
}
