<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('course-registrations:expire --all')->everyMinute()
    ->withoutOverlapping(); // 上一次尚未執行完畢時，不啟動下一次
;
