<?php

namespace App\Listeners;

use App\Events\AssignmentCreated;
use App\Jobs\SendAssignmentNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAssignmentNotificationListener implements ShouldQueue
{
    public function handle(AssignmentCreated $event): void
    {
        SendAssignmentNotificationJob::dispatch($event->assignment);
    }
}
