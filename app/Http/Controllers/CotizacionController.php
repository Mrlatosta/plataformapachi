<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CotizacionController extends Controller
{
    public function store(Request $request)
    {
        // Validar datos
        $validated = $request->validate([
            'cliente.nombre' => 'required|string|max:255',
            'cliente.email' => 'nullable|email|max:255',
            'cliente.telefono' => 'nullable|string|max:20',
            'cliente.direccion' => 'nullable|string|max:500',
            'fecha_cotizacion' => 'required|date',
            'vigencia' => 'nullable|integer|min:1',
            'estudios' => 'required|array|min:1',
            'estudios.*.id' => 'required|integer',
            'estudios.*.nombre' => 'required|string',
            'estudios.*.precio' => 'required|numeric|min:0',
            'estudios.*.cantidad' => 'required|integer|min:1',
            'estudios.*.descripcion' => 'nullable|string',
            'estudios.*.examenes' => 'nullable|array',
            'estudios.*.examenes.*.nombre_examen' => 'nullable|string',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'notas' => 'nullable|string|max:1000',
        ], [
            'cliente.nombre.required' => 'El nombre del cliente es obligatorio',
            'fecha_cotizacion.required' => 'La fecha de cotización es obligatoria',
            'estudios.required' => 'Debe agregar al menos un estudio',
            'estudios.min' => 'Debe agregar al menos un estudio',
            'estudios.*.precio.required' => 'Cada estudio debe tener un precio',
            'estudios.*.precio.min' => 'El precio debe ser mayor o igual a 0',
            'estudios.*.cantidad.required' => 'Cada estudio debe tener una cantidad',
            'estudios.*.cantidad.min' => 'La cantidad debe ser al menos 1',
        ]);

        // Calcular totales
        $subtotal = 0;
        foreach ($validated['estudios'] as $estudio) {
            $subtotal += $estudio['precio'] * $estudio['cantidad'];
        }

        $descuento = $validated['descuento'] ?? 0;
        $monto_descuento = $subtotal * ($descuento / 100);
        $total = $subtotal - $monto_descuento;

        // Preparar datos para el PDF
        $data = [
            'fecha_cotizacion' => $validated['fecha_cotizacion'],
            'vigencia' => $validated['vigencia'] ?? 30,
            'cliente' => $validated['cliente'],
            'estudios' => $validated['estudios'],
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'monto_descuento' => $monto_descuento,
            'total' => $total,
            'notas' => $validated['notas'] ?? '',
        ];

        // Generar PDF
        $pdf = Pdf::loadView('pdf.cotizacion_biolab', $data)
            ->setPaper('letter', 'portrait');

        return $pdf->download('cotizacion-' . date('Y-m-d') . '.pdf');
    }
}
