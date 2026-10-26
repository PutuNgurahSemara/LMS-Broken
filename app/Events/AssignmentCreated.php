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
        // Masalah 7
        // return [
        //     new Channel('course.'.$this->assignment->course_id),
        // ];
        // Penjelasan Masalah: Menggunakan public Channel ('use Illuminate\Broadcasting\Channel') memungkinkan siapa pun bahkan user di luar kelas untuk mendengarkan siaran event WebSocket dan menyadap pengumuman tugas.
        // Perbaikan:
        return [
            new PrivateChannel('course.'.$this->assignment->course_id),
        ];
    }
}
