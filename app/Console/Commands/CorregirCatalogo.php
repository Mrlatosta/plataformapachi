<?php

namespace App\Console\Commands;

use App\Models\Examen;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * "Correcciones menores en el catálogo" del documento de tickets (nov 2026):
 * unidades de proteínas, razones sin unidad y la referencia de HDL.
 * Por defecto solo muestra lo que cambiaria.
 */
class CorregirCatalogo extends Command
{
    protected $signature = 'catalogo:corregir {--aplicar : Guarda los cambios (sin esto solo muestra lo que haria)}';

    protected $description = 'Aplica las correcciones de unidades y referencias pedidas por el laboratorio';

    public function handle(): int
    {
        $cambios = [];

        foreach (Examen::with('estudio:id,nombre')->get() as $examen) {
            $nombre = Str::lower(Str::ascii((string) $examen->nombre_examen));
            $unidad = Str::lower(trim((string) $examen->unidad));
            $nuevo = [];

            // Proteinas totales, albumina y globulina van en g/dL
            if (preg_match('/^(proteinas totales|albumina|globulina)\b/', $nombre) && $unidad === 'mg/dl') {
                $nuevo['unidad'] = 'g/dL';
            }

            // Razones: Relacion A/G, indice aterogenico, LDL/HDL no llevan unidad
            if (preg_match('/relacion a\s*\/\s*g|aterog|ldl\s*\/\s*c?hdl/', $nombre) && $unidad !== '') {
                $nuevo['unidad'] = '';
            }

            // HDL: "Mujeres < 55 Hombres < 45" estaba invertido
            if (preg_match('/\bhdl\b|alta densidad/', $nombre) && ! preg_match('/ldl\s*\//', $nombre)
                && preg_match('/<\s*55/', (string) $examen->valor_referencia)) {
                $nuevo['valor_referencia'] = 'Mujeres > 55, Hombres > 45';
            }

            if ($nuevo) {
                $cambios[] = [$examen, $nuevo];
            }
        }

        $this->table(
            ['Estudio', 'Examen', 'Campo', 'Antes', 'Después'],
            collect($cambios)->flatMap(fn ($c) => collect($c[1])->map(fn ($valor, $campo) => [
                $c[0]->estudio?->nombre,
                $c[0]->nombre_examen,
                $campo,
                $c[0]->{$campo} === '' || $c[0]->{$campo} === null ? '(vacío)' : $c[0]->{$campo},
                $valor === '' ? '(sin unidad)' : $valor,
            ])->values())->all(),
        );

        if (! $this->option('aplicar')) {
            $this->info(count($cambios) . ' exámenes por corregir. Corre con --aplicar para guardar.');

            return self::SUCCESS;
        }

        foreach ($cambios as [$examen, $nuevo]) {
            $examen->update($nuevo);
        }

        $this->info(count($cambios) . ' exámenes corregidos.');

        return self::SUCCESS;
    }
}
