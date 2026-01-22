<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'start_time',
        'end_time',
        'calendar_id',
        'user_id',
        'all_day',
        'color',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /**
     * Obtener el calendario al que pertenece la cita
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    /**
     * Obtener el usuario asignado a la cita
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
