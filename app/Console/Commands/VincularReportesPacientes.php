<?php

namespace App\Console\Commands;

use App\Models\Paciente;
use App\Models\Reporte;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * T1: liga las ordenes anteriores (que solo guardaban el nombre en texto) con un
 * paciente, agrupando por nombre normalizado + fecha de nacimiento. Crea los
 * pacientes que falten. Se puede correr varias veces: solo toca ordenes sin paciente.
 */
class VincularReportesPacientes extends Command
{
    protected $signature = 'pacientes:vincular-reportes {--aplicar : Guarda los cambios (sin esto solo muestra lo que haria)}';

    protected $description = 'Liga las ordenes sin paciente con su paciente (lo crea si no existe) para armar el historial';

    public function handle(): int
    {
        $reportes = Reporte::whereNull('paciente_id')
            ->orderBy('toma_muestra')
            ->get(['id', 'nombre_cliente', 'fecha_nacimiento', 'sexo', 'email', 'edad']);

        $grupos = $reportes->groupBy(fn ($r) => Paciente::normalizarNombre($r->nombre_cliente) . '|' . substr((string) $r->fecha_nacimiento, 0, 10))
            ->reject(fn ($grupo, $clave) => str_starts_with($clave, '|'));

        $nuevos = 0;
        $existentes = 0;

        $aplicar = (bool) $this->option('aplicar');

        DB::transaction(function () use ($grupos, $aplicar, &$nuevos, &$existentes) {
            foreach ($grupos as $grupo) {
                // Los datos mas recientes del paciente salen de su ultima orden
                $ultimo = $grupo->last();
                $paciente = Paciente::buscarDuplicado($ultimo->nombre_cliente, $ultimo->fecha_nacimiento);

                if ($paciente) {
                    $existentes++;
                } else {
                    $nuevos++;
                    if ($aplicar) {
                        $paciente = Paciente::create([
                            'nombre' => $ultimo->nombre_cliente,
                            'fecha_nacimiento' => $ultimo->fecha_nacimiento ? substr((string) $ultimo->fecha_nacimiento, 0, 10) : null,
                            'sexo' => in_array($ultimo->sexo, ['Masculino', 'Femenino'], true) ? $ultimo->sexo : (ucfirst(strtolower((string) $ultimo->sexo)) ?: null),
                            'email' => $grupo->pluck('email')->filter()->last(),
                            'edad' => $ultimo->edad,
                        ]);
                    }
                }

                if ($aplicar && $paciente) {
                    Reporte::whereIn('id', $grupo->pluck('id'))->update(['paciente_id' => $paciente->id]);
                }
            }
        });

        $sinNombre = $reportes->count() - $grupos->flatten(1)->count();

        $this->table(['Concepto', 'Cantidad'], [
            ['Órdenes sin paciente', $reportes->count()],
            ['Pacientes nuevos', $nuevos],
            ['Pacientes que ya existían', $existentes],
            ['Órdenes sin nombre (se omiten)', $sinNombre],
        ]);

        $this->info($aplicar ? 'Listo: órdenes ligadas a su paciente.' : 'Vista previa. Corre con --aplicar para guardar.');

        return self::SUCCESS;
    }
}
