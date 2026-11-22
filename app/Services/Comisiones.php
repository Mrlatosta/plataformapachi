<?php

namespace App\Services;

use App\Models\Comision;
use App\Models\Medico;
use App\Models\Reporte;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * T12: cada orden con medico guarda su comision = monto cobrado x % del medico.
 * Se recalcula si la orden cambia (estudios, descuento, IVA o medico). Las ya
 * pagadas no se tocan.
 */
class Comisiones
{
    /** Monto sobre el que se calcula (ver config/comisiones.php). */
    public static function base(Reporte $reporte): float
    {
        return round(config('comisiones.base') === 'con_iva' ? $reporte->total : $reporte->base_gravable, 2);
    }

    public static function sincronizar(Reporte $reporte): ?Comision
    {
        $reporte->loadMissing('estudios');
        $actual = Comision::where('reporte_id', $reporte->id)->first();

        if ($actual?->estado === Comision::PAGADA) {
            $base = self::base($reporte);
            if (abs($base - (float) $actual->monto_base) >= 0.01 || (int) $actual->medico_id !== (int) $reporte->medico_id) {
                Log::warning('Orden con comision ya pagada cambio de monto o de medico', [
                    'folio' => $reporte->folio, 'pagada_sobre' => $actual->monto_base, 'monto_actual' => $base,
                ]);
            }

            return $actual;
        }

        $medico = $reporte->medico_id ? Medico::find($reporte->medico_id) : null;

        // Sin medico ("A quien corresponda") no hay comision
        if (! $medico) {
            $actual?->delete();

            return null;
        }

        // El % se fija al crear la comision; si cambia el medico, se toma el del nuevo
        $porcentaje = $actual && (int) $actual->medico_id === (int) $medico->id
            ? (float) $actual->porcentaje
            : (float) $medico->porcentaje_comision;

        $base = self::base($reporte);

        return Comision::updateOrCreate(
            ['reporte_id' => $reporte->id],
            [
                'medico_id' => $medico->id,
                'monto_base' => $base,
                'porcentaje' => $porcentaje,
                'comision' => round($base * $porcentaje / 100, 2),
                'estado' => Comision::PENDIENTE,
            ],
        );
    }

    /**
     * Al cambiar el % de un medico, sus comisiones pendientes se recalculan con el nuevo.
     */
    public static function aplicarPorcentaje(Medico $medico): int
    {
        $pendientes = Comision::where('medico_id', $medico->id)->where('estado', Comision::PENDIENTE)->get();

        foreach ($pendientes as $comision) {
            $comision->update([
                'porcentaje' => $medico->porcentaje_comision,
                'comision' => round((float) $comision->monto_base * (float) $medico->porcentaje_comision / 100, 2),
            ]);
        }

        return $pendientes->count();
    }

    /**
     * Clave para reconocer al mismo medico escrito de distintas formas:
     * "DR. LUIS YAMA", "dr. luis yama" y "Dr Luis Yama" → "luis yama".
     */
    public static function claveNombre(?string $nombre): string
    {
        $t = Str::lower(Str::ascii((string) $nombre));
        $t = preg_replace('/[^a-z0-9 ]+/', ' ', $t);
        $t = preg_replace('/^\s*(dr|dra|doctor|doctora|q ?f ?b|lic)\b\s*/', '', (string) $t);

        return trim(preg_replace('/\s+/', ' ', (string) $t));
    }

    /** Textos que significan "sin medico". */
    public static function esSinMedico(?string $nombre): bool
    {
        $clave = self::claveNombre($nombre);

        return $clave === ''
            || in_array($clave, ['n a', 'na', 'ninguno', 'sin medico', 'particular'], true)
            // "A quien corresponda" y sus errores de dedo ("a quien coreesponda")
            || (bool) preg_match('/^(a )?quien co/', $clave);
    }
}
