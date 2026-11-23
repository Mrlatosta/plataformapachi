<?php

namespace App\Console\Commands;

use App\Models\Comision;
use App\Models\Medico;
use App\Models\Reporte;
use App\Services\Comisiones;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * T12: liga las ordenes anteriores (con el medico escrito a mano) con un medico
 * de la lista, juntando las formas de escribir al mismo ("DR. LUIS YAMA",
 * "dr. luis yama", "Dr. Luis yama"). Crea los medicos que falten (sin %; se
 * pone despues en Gestion de medicos) y la comision de cada orden ligada.
 * Se puede correr varias veces: solo toca ordenes sin medico de la lista.
 */
class VincularReportesMedicos extends Command
{
    protected $signature = 'medicos:vincular-reportes
        {--aplicar : Guarda los cambios (sin esto solo muestra lo que haria)}
        {--desde= : Crea comisiones solo de ordenes con toma de muestra desde esta fecha (AAAA-MM-DD); las anteriores solo se ligan al medico}';

    protected $description = 'Liga las ordenes con el medico escrito a mano a un medico de la lista y crea sus comisiones';

    public function handle(): int
    {
        $aplicar = (bool) $this->option('aplicar');

        $reportes = Reporte::whereNull('medico_id')
            ->whereNotNull('medico_solicitante')
            ->get(['id', 'medico_solicitante'])
            ->reject(fn ($r) => Comisiones::esSinMedico($r->medico_solicitante));

        $existentes = Medico::all()->keyBy(fn (Medico $m) => Comisiones::claveNombre($m->nombre));
        // Primero los errores de dedo ("UIS YAMA" → "LUIS YAMA"), luego los nombres
        // incompletos de un medico que ya existe ("LUIS YAMA" → "LUIS YAMA CHAC")
        $grupos = self::juntarConExistentes(
            self::juntarErroresDeDedo(
                $reportes->groupBy(fn ($r) => Comisiones::claveNombre($r->medico_solicitante)),
                $existentes->keys()->all(),
            ),
            $existentes->keys()->all(),
        );

        if ($grupos->isEmpty()) {
            $this->info('No hay órdenes con médico escrito a mano por ligar.');
        }

        $filas = [];
        foreach ($grupos->sortByDesc(fn ($g) => $g->count()) as $clave => $grupo) {
            $variantes = $grupo->countBy(fn ($r) => trim($r->medico_solicitante))->sortDesc();
            $medico = $existentes->get($clave);
            $filas[] = [
                $medico ? "{$medico->nombre} (ya existe)" : self::nombreBonito($variantes->keys()->first()),
                $grupo->count(),
                $variantes->map(fn ($n, $texto) => "{$texto} ({$n})")->implode(', '),
            ];
        }
        if ($filas) {
            $this->table(['Médico', 'Órdenes', 'Cómo estaba escrito'], $filas);
        }

        // Nombres que se parecen pero no se juntan solos: revisarlos a mano
        $claves = $grupos->keys()->merge($existentes->keys())->unique()->values();
        $parecidos = [];
        foreach ($claves as $a) {
            foreach ($claves as $b) {
                if ($a < $b && (str_starts_with($b, $a.' ') || str_starts_with($a, $b.' ')
                    || str_ends_with($b, ' '.$a) || str_ends_with($a, ' '.$b) || levenshtein($a, $b) <= 2)) {
                    $parecidos[] = "«{$a}» y «{$b}»";
                }
            }
        }
        if ($parecidos) {
            $this->warn('Se parecen y quedan separados; si son el mismo médico, corrígelos en la orden: '.implode('; ', $parecidos));
        }

        $desde = $this->option('desde') ? \Carbon\Carbon::parse($this->option('desde'))->startOfDay() : null;
        // Ordenes con medico (las que ya lo tenian y las que se van a ligar) sin comision
        $sinComisionQuery = fn () => Reporte::where(fn ($q) => $q->whereNotNull('medico_id')->orWhereIn('id', $grupos->flatten()->pluck('id')))
            ->whereDoesntHave('comision')
            ->when($desde, fn ($q) => $q->where('toma_muestra', '>=', $desde));
        $sinComision = $sinComisionQuery()->count();

        if (! $aplicar) {
            $this->line('');
            $this->line(sprintf('Se ligarían %d órdenes a %d médicos (%d nuevos). Se registrarían %d comisiones pendientes%s.',
                $reportes->count(), $grupos->count(), $grupos->keys()->diff($existentes->keys())->count(), $sinComision,
                $desde ? ' (órdenes desde '.$desde->format('d/m/Y').')' : ' (de todas las fechas; usa --desde=AAAA-MM-DD para empezar en una fecha)'));
            $this->comment('Nada se guardó. Corre con --aplicar para hacerlo.');

            return self::SUCCESS;
        }

        $creados = 0;
        DB::transaction(function () use ($grupos, $existentes, &$creados) {
            foreach ($grupos as $clave => $grupo) {
                $medico = $existentes->get($clave);
                if (! $medico) {
                    $variante = $grupo->countBy(fn ($r) => trim($r->medico_solicitante))->sortDesc()->keys()->first();
                    $medico = Medico::create(['nombre' => self::nombreBonito($variante), 'activo' => true, 'porcentaje_comision' => 0]);
                    $existentes->put($clave, $medico);
                    $creados++;
                }

                Reporte::whereIn('id', $grupo->pluck('id'))->update(['medico_id' => $medico->id, 'medico_solicitante' => $medico->nombre]);
            }
        });

        // Comision (pendiente) de las ordenes con medico que aun no la tengan (desde --desde)
        $comisiones = 0;
        Reporte::whereNotNull('medico_id')->whereDoesntHave('comision')->with('estudios')
            ->when($desde, fn ($q) => $q->where('toma_muestra', '>=', $desde))
            ->chunkById(200, function ($lote) use (&$comisiones) {
                foreach ($lote as $reporte) {
                    Comisiones::sincronizar($reporte) && $comisiones++;
                }
            });

        $this->info("Listo: {$reportes->count()} órdenes ligadas, {$creados} médicos nuevos, {$comisiones} comisiones registradas.");
        $this->comment('Pon el % de cada médico en Gestión de médicos; se aplica a sus comisiones pendientes.');

        return self::SUCCESS;
    }

    /**
     * Nombre incompleto de un medico que ya existe ("DR. LUIS YAMA" → "DR. LUIS YAMA
     * CHAC"): se liga a ese medico si es el unico que empieza asi y el nombre escrito
     * tiene al menos dos palabras ("DR. MARIA" no se liga a nadie).
     */
    private static function juntarConExistentes($grupos, array $clavesExistentes)
    {
        foreach ($grupos->keys()->all() as $clave) {
            if (in_array($clave, $clavesExistentes, true) || substr_count($clave, ' ') < 1) {
                continue;
            }
            $coinciden = array_values(array_filter($clavesExistentes, fn ($e) => str_starts_with($e, $clave.' ')));
            if (count($coinciden) === 1) {
                $grupos[$coinciden[0]] = ($grupos[$coinciden[0]] ?? collect())->merge($grupos[$clave]);
                $grupos->forget($clave);
            }
        }

        return $grupos;
    }

    /**
     * Errores de una letra en nombres largos ("uis yama", "wilber vazquez") se
     * juntan con el nombre mas usado (o con un medico que ya existe).
     */
    private static function juntarErroresDeDedo($grupos, array $clavesExistentes)
    {
        // Peso de cada nombre: primero si es (o empieza) un medico que ya existe,
        // luego cuantas ordenes lo usan y al final el mas largo. El de menor peso
        // se junta con el de mayor peso (nunca al reves, aunque empaten en ordenes).
        $peso = fn (string $clave) => [
            in_array($clave, $clavesExistentes, true) || collect($clavesExistentes)->contains(fn ($e) => str_starts_with($e, $clave.' ')) ? 1 : 0,
            ($grupos[$clave] ?? collect())->count(),
            mb_strlen($clave),
        ];
        $candidatos = collect($grupos->keys())->merge($clavesExistentes)->unique()->values();

        foreach ($grupos->keys()->sortBy(fn ($k) => $peso($k))->values() as $clave) {
            $destino = $candidatos
                ->reject(fn ($c) => $c === $clave || ! isset($grupos[$clave]))
                ->filter(fn ($c) => min(mb_strlen($c), mb_strlen($clave)) >= 8 && levenshtein($c, $clave) <= 1 && $peso($c) > $peso($clave))
                ->sortByDesc(fn ($c) => $peso($c))
                ->first();

            if ($destino !== null) {
                $grupos[$destino] = ($grupos[$destino] ?? collect())->merge($grupos[$clave]);
                $grupos->forget($clave);
            }
        }

        return $grupos;
    }

    /** "DR. LUIS YAMA" / "dr luis yama" → "Dr. Luis Yama" */
    private static function nombreBonito(string $texto): string
    {
        $prefijo = preg_match('/^\s*dra\b/i', $texto) ? 'Dra. ' : (preg_match('/^\s*(dr|doctor)\b/i', $texto) ? 'Dr. ' : '');
        $sinPrefijo = preg_replace('/^\s*(dra|dr|doctora|doctor)\.?\s*/i', '', trim($texto));

        return $prefijo.Str::title(Str::lower(preg_replace('/\s+/', ' ', (string) $sinPrefijo)));
    }
}
