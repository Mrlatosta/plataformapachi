<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ADMIN = 'admin';
    public const RECEPCION = 'recepcion';

    public const ROLES = [
        self::ADMIN => 'Administrador',
        self::RECEPCION => 'Recepción',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function esAdmin(): bool
    {
        // Sin rol (usuarios anteriores a T6) = administrador
        return ($this->rol ?? self::ADMIN) === self::ADMIN;
    }

    /**
     * Lo que el frontend necesita saber para mostrar u ocultar opciones.
     * La seguridad real esta en el middleware 'rol' y en los controladores.
     */
    public function permisos(): array
    {
        $admin = $this->esAdmin();

        return [
            'validar' => $admin,
            'ver_ventas' => $admin,
            'gestionar_catalogo' => $admin,
            'gestionar_usuarios' => $admin,
            'editar_precios' => $admin,
            'eliminar' => $admin,
            'captura_rapida' => $admin,
        ];
    }
}
