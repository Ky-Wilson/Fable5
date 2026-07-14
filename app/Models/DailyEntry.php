<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyEntry extends Model
{
    protected $fillable = ['room_id', 'day', 'question', 'answer1', 'answer2'];

    protected function casts(): array
    {
        return ['day' => 'date:Y-m-d'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
