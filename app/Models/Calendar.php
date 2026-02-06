<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calendar extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'color',
        'user_id',
        'is_active',
        'start_time',
        'end_time',
        'slot_duration',
        'time_format',
        'timezone',
        'business_days',
        'visibility',
        'include_in_analytics',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'business_days' => 'array',
            'include_in_analytics' => 'boolean',
        ];
    }

    /**
     * Obtener el usuario propietario del calendario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener las citas del calendario
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
