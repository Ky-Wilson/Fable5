<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Round extends Model
{
    protected $fillable = [
        'room_id', 'num', 'question', 'target_num',
        'target_answer', 'guess_answer', 'correct', 'status',
    ];

    protected function casts(): array
    {
        return ['correct' => 'boolean'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
