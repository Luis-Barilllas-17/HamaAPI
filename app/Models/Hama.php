<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hama extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nombre',
        'descripcion',
        'ancho',
        'alto',
        'cantidad_cuentas',
        'color_principal',
        'es_publico',
        'patron',
    ];

    protected $hidden = [
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'ancho' => 'integer',
            'alto' => 'integer',
            'cantidad_cuentas' => 'integer',
            'es_publico' => 'boolean',
            'patron' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleToApiConsumer(Builder $query, ?User $user): Builder
    {
        if ($user?->isAdmin()) {
            return $query;
        }

        if ($user) {
            return $query->where('user_id', $user->getKey());
        }

        return $query->where('es_publico', true);
    }

    public function isVisibleToApiConsumer(?User $user): bool
    {
        if ($user?->isAdmin()) {
            return true;
        }

        if ($user) {
            return (int) $this->user_id === (int) $user->getKey();
        }

        return $this->es_publico;
    }
}
