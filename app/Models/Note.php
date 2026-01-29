<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Note extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'content',
        'visibility',
        'share_token',
        'user_id',
        'note_category_id',
    ];

    /**
     * Obtener el usuario propietario de la nota
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener la categoría de la nota
     */
    public function noteCategory(): BelongsTo
    {
        return $this->belongsTo(NoteCategory::class, 'note_category_id');
    }

    /**
     * Scope: notas visibles para un usuario (propias + visibility todos)
     */
    public function scopeVisibleToUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('visibility', 'todos');
        });
    }

    /**
     * Generar o regenerar el token de compartir
     */
    public function generateShareToken(): string
    {
        $this->update(['share_token' => Str::random(32)]);

        return $this->share_token;
    }
}
