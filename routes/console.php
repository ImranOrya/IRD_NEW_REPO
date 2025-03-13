<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:daily-task')
    ->everyMinute(); 
    
// Schedule::command('app:daily-task')
//     ->dailyAt('8:30'); //12:00 AM (midnight) Kabul time
