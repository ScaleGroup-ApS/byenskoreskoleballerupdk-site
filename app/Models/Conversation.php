<?php

namespace App\Models;

use App\Enums\ConversationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'team_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => ConversationType::class,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('last_read_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
