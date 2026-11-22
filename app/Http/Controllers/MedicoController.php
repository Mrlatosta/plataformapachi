<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use App\Services\Comisiones;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MedicoController extends Controller
{
    public function page()
    {
        return Inertia::render('GestionMedicos');
    }

    public function index(Request $request)
    {
        $medicos = Medico::orderBy('nombre')->get();

        // T12: Recepcion elige al medico de la lista pero no ve comisiones ni usuarios del portal
        if (! $request->user()?->esAdmin()) {
            return response()->json($medicos->map->only(['id', 'nombre', 'especialidad', 'activo'])->values());
        }

        return response()->json($medicos);
    }

    private function reglas(?int $id = null): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            // Usuario y contraseña solo para el portal de resultados
            'usuario' => 'nullable|string|max:100|unique:medicos,usuario'.($id ? ",{$id}" : ''),
            'password' => ($id ? 'nullable' : 'nullable|required_with:usuario').'|string|min:6',
            'activo' => 'boolean',
            'porcentaje_comision' => 'nullable|numeric|min:0|max:100',
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'usuario.unique' => 'Este usuario ya está en uso',
            'password.required_with' => 'Si le das usuario para el portal, ponle también una contraseña',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'porcentaje_comision.max' => 'El porcentaje no puede pasar de 100',
        ];
    }

    /** Otro medico con el mismo nombre escrito distinto ("DR. LUIS YAMA" = "dr luis yama"). */
    private function duplicado(string $nombre, ?int $ignorar = null): ?Medico
    {
        $clave = Comisiones::claveNombre($nombre);

        return Medico::when($ignorar, fn ($q) => $q->whereKeyNot($ignorar))->get()
            ->first(fn (Medico $m) => Comisiones::claveNombre($m->nombre) === $clave);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas(), $this->mensajes());

        if ($otro = $this->duplicado($validated['nombre'])) {
            return response()->json(['message' => "Ya existe «{$otro->nombre}». Edítalo en lugar de crear otro."], 422);
        }

        $medico = Medico::create([...$validated, 'porcentaje_comision' => $validated['porcentaje_comision'] ?? 0]);

        return response()->json($medico, 201);
    }

    /**
     * Alta rapida desde la captura (Administrador y Recepcion): solo nombre y
     * especialidad. Si ya existe con otra forma de escribirlo, regresa el existente.
     */
    public function rapido(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'porcentaje_comision' => 'nullable|numeric|min:0|max:100',
        ], $this->mensajes());

        if (Comisiones::esSinMedico($validated['nombre'])) {
            return response()->json(['message' => 'Para órdenes sin médico elige «A quien corresponda».'], 422);
        }

        if ($existente = $this->duplicado($validated['nombre'])) {
            return response()->json([...$existente->only(['id', 'nombre', 'especialidad', 'activo']), 'existente' => true]);
        }

        $medico = Medico::create([
            'nombre' => trim($validated['nombre']),
            'especialidad' => $validated['especialidad'] ?? null,
            'activo' => true,
            // Solo el administrador pone el %
            'porcentaje_comision' => $request->user()->esAdmin() ? ($validated['porcentaje_comision'] ?? 0) : 0,
        ]);

        return response()->json([...$medico->only(['id', 'nombre', 'especialidad', 'activo']), 'existente' => false], 201);
    }

    public function update(Request $request, int $id)
    {
        $medico = Medico::findOrFail($id);
        $validated = $request->validate($this->reglas($id), $this->mensajes());

        if ($otro = $this->duplicado($validated['nombre'], $id)) {
            return response()->json(['message' => "Ya existe «{$otro->nombre}» con ese nombre."], 422);
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        // Quitarle el usuario lo saca del portal
        if (array_key_exists('usuario', $validated) && blank($validated['usuario'])) {
            $validated['usuario'] = null;
            $validated['password'] = null;
        }

        $porcentajeAntes = (float) $medico->porcentaje_comision;
        $medico->update($validated);

        // T12: el nuevo % aplica a sus comisiones pendientes (las pagadas no cambian)
        $recalculadas = (float) $medico->porcentaje_comision !== $porcentajeAntes
            ? Comisiones::aplicarPorcentaje($medico)
            : 0;

        return response()->json([...$medico->toArray(), 'comisiones_recalculadas' => $recalculadas]);
    }

    public function destroy(int $id)
    {
        $medico = Medico::findOrFail($id);

        if ($medico->comisiones()->exists() || $medico->reportes()->exists()) {
            return response()->json([
                'message' => 'Este médico tiene órdenes o comisiones registradas. Desactívalo en lugar de borrarlo para no perder el historial.',
            ], 422);
        }

        $medico->delete();

        return response()->json(['message' => 'Médico eliminado']);
    }
}
