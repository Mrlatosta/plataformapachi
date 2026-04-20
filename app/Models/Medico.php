<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Medico extends Model
{
    protected $fillable = [
        'nombre',
        'especialidad',
        'usuario',
        'password',
        'activo',
    ];

    protected $hidden = ['password'];

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function reportes()
    {
        return $this->hasMany(Reporte::class);
    }
}
