<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    protected $fillable = ['room_id', 'num', 'name', 'gender', 'token', 'score'];

    protected $hidden = ['token'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
