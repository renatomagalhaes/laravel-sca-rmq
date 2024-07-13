<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ConsumeSalesOrder::class,
        \App\Console\Commands\ConsumeItemEvent::class,
        \App\Console\Commands\ReprocessDlq::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Schedule artisan commands here, if needed.
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
