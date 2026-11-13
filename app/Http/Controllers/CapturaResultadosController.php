<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Cita;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CapturaResultadosController extends Controller
{
    /**
     * Renderiza la página de captura. Si viene cotizacion_id, precarga los datos.
     */
    public function page(Request $request)
    {
        $cotizacion = null;

        if ($request->has('cotizacion_id')) {
            $cotizacion = Cotizacion::with(['estudios.estudio.examenes', 'paciente'])
                ->find($request->cotizacion_id);
        } elseif ($request->filled('cita_id')) {
            $cotizacion = $this->precargaDesdeCita((int) $request->cita_id);
        }

        return Inertia::render('CapturaResultados', [
            'cotizacionPrecargada' => $cotizacion,
        ]);
    }

    /**
     * Arma, desde una cita de la agenda, los mismos datos que la vista espera
     * de una cotización (paciente, contacto y estudios con su precio).
     */
    private function precargaDesdeCita(int $citaId): ?array
    {
        $cita = Cita::with(['paciente', 'estudios.estudio.examenes'])->find($citaId);
        if (! $cita) {
            return null;
        }

        return [
            'origen' => 'Cita del ' . $cita->fecha->format('d/m/Y') . ' a las ' . $cita->hora_inicio,
            'aplica_iva' => false,
            'descuento' => 0,
            'paciente' => $cita->paciente,
            'nombre_cliente' => $cita->nombre,
            'email' => $cita->email,
            'estudios' => $cita->estudios
                ->filter(fn ($item) => $item->estudio)
                ->map(fn ($item) => ['precio' => $item->precio, 'estudio' => $item->estudio])
                ->values(),
        ];
    }
}
