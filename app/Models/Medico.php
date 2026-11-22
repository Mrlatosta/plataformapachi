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
        // T12: % de comision por orden referida
        'porcentaje_comision',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'activo' => 'boolean',
        'porcentaje_comision' => 'decimal:2',
    ];

    public function setPasswordAttribute(?string $value): void
    {
        // Usuario y contraseña solo si el medico entra al portal de resultados
        $this->attributes['password'] = $value === null || $value === '' ? null : Hash::make($value);
    }

    public function reportes()
    {
        return $this->hasMany(Reporte::class);
    }

    public function comisiones()
    {
        return $this->hasMany(Comision::class);
    }
}
