<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'assigned_to', 'title', 'description', 'status', 'deadline'])]
class Task extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'deadline' => 'datetime'
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function asignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
