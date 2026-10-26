<?php

namespace App\Events;

use App\Models\Assignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Assignment $assignment)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('course.'.$this->assignment->course_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'course_id' => $this->assignment->course_id,
            'title' => $this->assignment->title,
        ];
    }
}