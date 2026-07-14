<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['code', 'state', 'mode', 'pack', 'ai', 'total_rounds', 'questions'];

    protected function casts(): array
    {
        return [
            'ai' => 'boolean',
            'questions' => 'array',
        ];
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    public function dailyEntries(): HasMany
    {
        return $this->hasMany(DailyEntry::class);
    }

    public function currentRound(): ?Round
    {
        return $this->rounds()->orderByDesc('num')->first();
    }

    public static function generateCode(): string
    {
        // Sans caractères ambigus (0/O, 1/I/L).
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
