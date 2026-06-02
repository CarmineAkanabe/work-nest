<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyProjectManager
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskCompleted $event): void
    {
        $task = $event->task;
        $manager = $task->project->owner;

        // At this level we implement a mailer to send Email notifications to managers...
        // // Now this runs in the background
        // Mail::to($manager->email)->send(new TaskCompletedMail($task));

        //... not yet we will just Log
        Log::info("Manager {$manager->name} notified: task '{$task->title}' was completed.");
    }
}
