<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Console\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(["user_id", "name", "descrwiption", "status", "deadline"])]
class Project extends Model
{

    use HasFactory;
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'deadline' => 'datetime'
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

}
