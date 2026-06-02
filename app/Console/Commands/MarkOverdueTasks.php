<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Log;

#[Signature('tasks:mark-overdue')]
#[Description('Mark all tasks past their deadline as overdue')]
class MarkOverdueTasks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $updated = Task::query()
            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Overdue->value])
            ->where('deadline', '<', now())
            ->whereNotNull('deadline')
            ->update(['status' => TaskStatus::Overdue->value]);

        Log::info("MarkOverdueTasks: {$updated} task(s) marked as overdue.");
        $this->info("{$updated} task(s) marked as overdue.");
    }
}
