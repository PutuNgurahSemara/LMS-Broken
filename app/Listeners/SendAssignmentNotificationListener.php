<?php

namespace App\Listeners;

use App\Events\AssignmentCreated;
use App\Jobs\SendAssignmentNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAssignmentNotificationListener implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 10;

    public function handle(AssignmentCreated $event): void
    {
        SendAssignmentNotificationJob::dispatch(
            (int) $event->assignment->id
        );
    }
}