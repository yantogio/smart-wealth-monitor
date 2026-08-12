<?php

use Illuminate\Support\Facades\Schedule;

// IDX closes at 16:00 WIB; 18:00 leaves the provider time to settle end-of-day data.
// A catch-up sync after a long gap can run for minutes, so overlapping runs are blocked.
// Requires `php artisan schedule:work` or a cron entry to actually fire; the demo
// dataset needs neither.
Schedule::command('sync:prices')->dailyAt('18:00')->withoutOverlapping();
