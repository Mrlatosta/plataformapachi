<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PacienteController extends Controller
{
    // Listar todos los pacientes
    public function index()
    {
        return Paciente::orderBy('nombre', 'asc')->get();
    }

    // Página Inertia
    public function page()
    {
        return Inertia::render('Pacientes');
    }

    // Crear nuevo paciente
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'edad' => 'nullable|integer|min:0',
            'sexo' => 'nullable|in:Masculino,Femenino',
            'direccion' => 'nullable|string|max:500',
            'notas' => 'nullable|string|max:1000',
        ], [
            'nombre.required' => 'El nombre del paciente es obligatorio',
            'email.email' => 'El email no tiene un formato válido',
        ]);

        $paciente = Paciente::create($validated);

        return response()->json([
            'message' => 'Paciente registrado correctamente',
            'paciente' => $paciente,
        ], 201);
    }

    // Obtener un paciente específico
    public function show($id)
    {
        return Paciente::findOrFail($id);
    }

    // Actualizar paciente
    public function update(Request $request, $id)
    {
        $paciente = Paciente::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'edad' => 'nullable|integer|min:0',
            'sexo' => 'nullable|in:Masculino,Femenino',
            'direccion' => 'nullable|string|max:500',
            'notas' => 'nullable|string|max:1000',
        ]);

        $paciente->update($validated);

        return response()->json([
            'message' => 'Paciente actualizado correctamente',
            'paciente' => $paciente,
        ]);
    }

    // Eliminar paciente
    public function destroy($id)
    {
        $paciente = Paciente::findOrFail($id);
        $paciente->delete();

        return response()->json([
            'message' => 'Paciente eliminado correctamente',
        ]);
    }

    // Buscar pacientes por nombre
    public function buscar(Request $request)
    {
        $termino = $request->query('q', '');

        $pacientes = Paciente::where('nombre', 'like', "%{$termino}%")
            ->orderBy('nombre', 'asc')
            ->limit(20)
            ->get();

        return response()->json($pacientes);
    }
}
