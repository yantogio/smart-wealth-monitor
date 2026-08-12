<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduledSyncTest extends TestCase
{
    public function test_price_sync_is_scheduled_daily(): void
    {
        $event = $this->syncEvent();

        $this->assertNotNull($event, 'sync:prices is not registered on the schedule.');
        $this->assertSame('0 18 * * *', $event->expression);
    }

    public function test_scheduled_sync_does_not_overlap(): void
    {
        $this->assertTrue($this->syncEvent()?->withoutOverlapping);
    }

    private function syncEvent(): ?Event
    {
        foreach (app(Schedule::class)->events() as $event) {
            if (str_contains($event->command ?? '', 'sync:prices')) {
                return $event;
            }
        }

        return null;
    }
}
