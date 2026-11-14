<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
| Scheduler — runs from cron every minute in production (deploy/README.md):
|   * * * * * cd /var/www/pixellap-api && php artisan schedule:run >> /dev/null 2>&1
*/

// Housekeeping only (no feature changes).
Schedule::command('queue:prune-failed --hours=336')->daily();   // keep failed jobs 14 days
Schedule::command('activitylog:clean')->weekly();               // config/activitylog.php retention
Schedule::command('auth:clear-resets')->daily();
