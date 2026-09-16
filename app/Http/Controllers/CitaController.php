<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Estudio;
use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CitaController extends Controller
{
    private const MENSAJES = [
        'nombre.required' => 'Escribe el nombre del paciente o elige uno registrado.',
        'fecha.required' => 'Elige la fecha de la cita.',
        'fecha.date_format' => 'La fecha no es válida; elígela en el calendario.',
        'hora_inicio.required' => 'Elige la hora de la cita.',
        'hora_inicio.date_format' => 'La hora debe tener el formato HH:MM, por ejemplo 08:30.',
        'direccion.required_if' => 'Para una toma a domicilio escribe la dirección (calle, número, colonia y referencias).',
        'email.email' => 'El correo no parece válido; revisa que tenga @ y dominio.',
        'estudios.*.exists' => 'Uno de los estudios ya no existe en el catálogo; quítalo y vuelve a agregarlo.',
        'estudios.*.distinct' => 'Hay un estudio repetido en la lista.',
        'paciente_id.exists' => 'El paciente elegido ya no existe; búscalo de nuevo.',
    ];

    public function page()
    {
        return Inertia::render('Agenda', [
            'hoy' => now(config('agenda.zona_horaria'))->toDateString(),
            'config' => config('agenda'),
            'catalogo' => Estudio::orderBy('nombre')->get(['id', 'nombre', 'precio']),
            'direccionLaboratorio' => config('landing.direccion') . ', ' . config('landing.ciudad'),
        ]);
    }

    public function index(Request $request)
    {
        $validado = $request->validate([
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'required|date_format:Y-m-d|after_or_equal:desde',
            'estado' => ['nullable', Rule::in(array_keys(config('agenda.estados')))],
        ]);

        $dias = Carbon::parse($validado['desde'])->diffInDays(Carbon::parse($validado['hasta']));
        if ($dias > config('agenda.rango_maximo_dias')) {
            throw ValidationException::withMessages(['hasta' => 'Consulta como máximo ' . config('agenda.rango_maximo_dias') . ' días a la vez.']);
        }

        return Cita::with([
            'estudios' => fn ($q) => $q->select('id', 'cita_id', 'estudio_id', 'nombre', 'precio')->orderBy('id'),
            'paciente:id,nombre,telefono,email,direccion',
        ])
            ->whereBetween('fecha', [$validado['desde'], $validado['hasta']])
            ->when($validado['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();
    }

    public function store(Request $request)
    {
        $cita = $this->guardar($request, new Cita(['creado_por' => $request->user()?->id]));

        return response()->json(['message' => 'Cita agendada', 'cita' => $cita], 201);
    }

    public function update(Request $request, int $id)
    {
        $cita = $this->guardar($request, Cita::findOrFail($id));

        return response()->json(['message' => 'Cita actualizada', 'cita' => $cita]);
    }

    public function cambiarEstado(Request $request, int $id)
    {
        $validado = $request->validate([
            'estado' => ['required', Rule::in(array_keys(config('agenda.estados')))],
        ]);

        $cita = Cita::findOrFail($id);
        $cita->update($validado);

        return response()->json(['message' => 'Estado actualizado', 'estado' => $cita->estado]);
    }

    public function destroy(int $id)
    {
        Cita::findOrFail($id)->delete();

        return response()->json(['message' => 'Cita eliminada']);
    }

    private function guardar(Request $request, Cita $cita): Cita
    {
        $validado = $request->validate([
            'paciente_id' => 'nullable|integer|exists:pacientes,id',
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'guardar_paciente' => 'boolean',
            'fecha' => 'required|date_format:Y-m-d',
            'hora_inicio' => 'required|date_format:H:i',
            'duracion_minutos' => 'required|integer|min:5|max:480',
            'modalidad' => ['required', Rule::in(['laboratorio', 'domicilio'])],
            'direccion' => 'nullable|required_if:modalidad,domicilio|string|max:500',
            'estado' => ['nullable', Rule::in(array_keys(config('agenda.estados')))],
            'origen' => ['required', Rule::in(array_keys(config('agenda.origenes')))],
            'indicaciones' => 'nullable|string|max:1000',
            'notas' => 'nullable|string|max:1000',
            'estudios' => 'nullable|array|max:30',
            'estudios.*' => 'integer|distinct|exists:estudios,id',
        ], self::MENSAJES);

        $inicio = Carbon::createFromFormat('Y-m-d H:i', $validado['fecha'] . ' ' . $validado['hora_inicio']);
        $fin = $inicio->copy()->addMinutes($validado['duracion_minutos']);
        if (! $fin->isSameDay($inicio)) {
            throw ValidationException::withMessages([
                'duracion_minutos' => 'La cita terminaría al día siguiente; elige una hora más temprana o una duración menor.',
            ]);
        }

        return DB::transaction(function () use ($request, $cita, $validado, $fin) {
            $pacienteId = $validado['paciente_id'] ?? null;

            if (! $pacienteId && $request->boolean('guardar_paciente')) {
                $pacienteId = Paciente::create([
                    'nombre' => $validado['nombre'],
                    'telefono' => $validado['telefono'] ?? null,
                    'email' => $validado['email'] ?? null,
                    'direccion' => $validado['modalidad'] === 'domicilio' ? $validado['direccion'] : null,
                ])->id;
            }

            $cita->fill([
                'paciente_id' => $pacienteId,
                'nombre' => trim($validado['nombre']),
                'telefono' => $validado['telefono'] ?? null,
                'email' => $validado['email'] ?? null,
                'fecha' => $validado['fecha'],
                'hora_inicio' => $validado['hora_inicio'],
                'hora_fin' => $fin->format('H:i'),
                'duracion_minutos' => $validado['duracion_minutos'],
                'modalidad' => $validado['modalidad'],
                'direccion' => $validado['modalidad'] === 'domicilio' ? $validado['direccion'] : null,
                'estado' => $validado['estado'] ?? ($cita->estado ?: 'programada'),
                'origen' => $validado['origen'],
                'indicaciones' => $validado['indicaciones'] ?? null,
                'notas' => $validado['notas'] ?? null,
            ])->save();

            $this->sincronizarEstudios($cita, $validado['estudios'] ?? []);

            return $cita->fresh([
                'estudios' => fn ($q) => $q->orderBy('id'),
                'paciente:id,nombre,telefono,email,direccion',
            ]);
        });
    }

    /**
     * Conserva el precio de los estudios que ya estaban en la cita;
     * los nuevos toman el precio actual del catálogo.
     */
    private function sincronizarEstudios(Cita $cita, array $ids): void
    {
        $actuales = $cita->estudios()->get()->keyBy('estudio_id');
        $catalogo = Estudio::whereIn('id', $ids)->get(['id', 'nombre', 'precio'])->keyBy('id');

        $cita->estudios()->whereNotIn('estudio_id', $ids)->delete();
        $cita->estudios()->whereNull('estudio_id')->delete();

        foreach ($ids as $id) {
            if (! $actuales->has($id)) {
                $cita->estudios()->create([
                    'estudio_id' => $id,
                    'nombre' => $catalogo[$id]->nombre,
                    'precio' => $catalogo[$id]->precio ?? 0,
                ]);
            }
        }

        $cita->update(['total_estimado' => $cita->estudios()->sum('precio')]);
    }
}
