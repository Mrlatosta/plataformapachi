<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * T6: el administrador da de alta los accesos (p. ej. Recepción), cambia su rol,
 * los desactiva y restablece contraseñas. El registro publico esta cerrado.
 */
class UsuarioController extends Controller
{
    public function page()
    {
        return Inertia::render('Usuarios/Index', [
            'usuarios' => User::orderByDesc('activo')->orderBy('name')
                ->get(['id', 'name', 'email', 'rol', 'activo', 'created_at'])
                ->map(fn (User $u) => [...$u->toArray(), 'rol' => $u->rol ?: User::ADMIN]),
            'roles' => collect(User::ROLES)->map(fn ($nombre, $clave) => ['valor' => $clave, 'nombre' => $nombre])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'rol' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', Password::min(8)],
        ], $this->mensajes());

        // Lo crea el administrador: no necesita confirmar el correo para entrar
        User::create([...$validated, 'activo' => true])
            ->forceFill(['email_verified_at' => now()])
            ->save();

        return back()->with('success', 'Usuario creado');
    }

    public function update(Request $request, User $usuario)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'rol' => ['required', Rule::in(array_keys(User::ROLES))],
            'activo' => 'required|boolean',
            'password' => ['nullable', Password::min(8)],
        ], $this->mensajes());

        // Nadie se quita a si mismo el acceso de administrador (evita quedarse fuera)
        if ($usuario->is($request->user()) && ($validated['rol'] !== User::ADMIN || ! $validated['activo'])) {
            return back()->withErrors(['rol' => 'No puedes quitarte tu propio acceso de administrador ni desactivarte.']);
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $usuario->update($validated);

        return back()->with('success', 'Usuario actualizado');
    }

    private function mensajes(): array
    {
        return [
            'email.unique' => 'Ya hay un usuario con ese correo.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
