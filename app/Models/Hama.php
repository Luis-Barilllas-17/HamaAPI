<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hama extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'ancho',
        'alto',
        'cantidad_cuentas',
        'color_principal',
        'patron',
    ];

    protected function casts(): array
    {
        return [
            'ancho' => 'integer',
            'alto' => 'integer',
            'cantidad_cuentas' => 'integer',
            'patron' => 'array',
        ];
    }
}
