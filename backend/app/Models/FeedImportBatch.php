<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedImportBatch extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'created_rows' => 'integer',
            'updated_rows' => 'integer',
            'skipped_rows' => 'integer',
            'failed_rows' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metrics' => 'array',
        ];
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function rowErrors(): HasMany
    {
        return $this->hasMany(FeedImportRowError::class);
    }

    public function scopeRunning($query)
    {
        return $query->whereIn('status', ['pending', 'running']);
    }
}
