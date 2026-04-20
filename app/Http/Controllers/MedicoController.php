<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MedicoController extends Controller
{
    public function page()
    {
        return Inertia::render('GestionMedicos');
    }

    public function index()
    {
        return response()->json(Medico::orderBy('nombre')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'       => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'usuario'      => 'required|string|max:100|unique:medicos,usuario',
            'password'     => 'required|string|min:6',
            'activo'       => 'boolean',
        ], [
            'nombre.required'    => 'El nombre es obligatorio',
            'usuario.required'   => 'El usuario es obligatorio',
            'usuario.unique'     => 'Este usuario ya está registrado',
            'password.required'  => 'La contraseña es obligatoria',
            'password.min'       => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        $medico = Medico::create($validated);

        return response()->json($medico, 201);
    }

    public function update(Request $request, int $id)
    {
        $medico = Medico::findOrFail($id);

        $validated = $request->validate([
            'nombre'       => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'usuario'      => 'required|string|max:100|unique:medicos,usuario,' . $id,
            'password'     => 'nullable|string|min:6',
            'activo'       => 'boolean',
        ], [
            'nombre.required'   => 'El nombre es obligatorio',
            'usuario.required'  => 'El usuario es obligatorio',
            'usuario.unique'    => 'Este usuario ya está en uso',
            'password.min'      => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $medico->update($validated);

        return response()->json($medico);
    }

    public function destroy(int $id)
    {
        $medico = Medico::findOrFail($id);
        $medico->delete();

        return response()->json(['message' => 'Médico eliminado']);
    }
}
