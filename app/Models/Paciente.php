<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'fecha_nacimiento',
        'edad',
        'sexo',
        'direccion',
        'notas',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];
}
