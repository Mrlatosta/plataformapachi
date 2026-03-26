<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\CotizacionEstudio;
use App\Models\Estudio;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CotizacionController extends Controller
{
    private function applyPageNumbers($pdf, float $x = 500, float $y = 770, int $size = 9): void
    {
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text($x, $y, 'Pagina {PAGE_NUM} de {PAGE_COUNT}', $font, $size, [0.4, 0.4, 0.4]);
    }

    /**
     * Listar todas las cotizaciones
     */
    public function index()
    {
        $cotizaciones = Cotizacion::with(['estudios.estudio', 'paciente'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($cotizaciones);
    }

    /**
     * Obtener una cotización por ID
     */
    public function show($id)
    {
        $cotizacion = Cotizacion::with(['estudios.estudio.examenes', 'paciente'])->findOrFail($id);
        return response()->json($cotizacion);
    }

    /**
     * Guardar una nueva cotización en la BD
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'paciente_id' => 'nullable|integer|exists:pacientes,id',
            'cliente.nombre' => 'required|string|max:255',
            'cliente.email' => 'nullable|email|max:255',
            'cliente.telefono' => 'nullable|string|max:20',
            'cliente.direccion' => 'nullable|string|max:500',
            'fecha_cotizacion' => 'required|date',
            'vigencia' => 'nullable|integer|min:1',
            'estudios' => 'required|array|min:1',
            'estudios.*.id' => 'required|integer|exists:estudios,id',
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

        // Crear cotización
        $cotizacion = Cotizacion::create([
            'folio' => Cotizacion::generarFolio(),
            'paciente_id' => $validated['paciente_id'] ?? null,
            'nombre_cliente' => $validated['cliente']['nombre'],
            'email' => $validated['cliente']['email'] ?? null,
            'telefono' => $validated['cliente']['telefono'] ?? null,
            'direccion' => $validated['cliente']['direccion'] ?? null,
            'fecha_cotizacion' => $validated['fecha_cotizacion'],
            'vigencia' => $validated['vigencia'] ?? 30,
            'descuento' => $descuento,
            'subtotal' => $subtotal,
            'total' => $total,
            'notas' => $validated['notas'] ?? null,
            'estado' => 'pendiente_pago',
        ]);

        // Guardar estudios de la cotización
        foreach ($validated['estudios'] as $estudio) {
            CotizacionEstudio::create([
                'cotizacion_id' => $cotizacion->id,
                'estudio_id' => $estudio['id'],
                'precio' => $estudio['precio'],
                'cantidad' => $estudio['cantidad'],
                'descripcion' => $estudio['descripcion'] ?? null,
            ]);
        }

        // Cargar relaciones para la respuesta
        $cotizacion->load('estudios.estudio.examenes', 'paciente');

        return response()->json([
            'message' => 'Cotización guardada exitosamente',
            'cotizacion' => $cotizacion,
        ], 201);
    }

    /**
     * Actualizar el estado de una cotización
     */
    public function actualizarEstado(Request $request, $id)
    {
        $validated = $request->validate([
            'estado' => 'required|in:pendiente_pago,pendiente_captura,completada',
        ]);

        $cotizacion = Cotizacion::findOrFail($id);
        $cotizacion->update(['estado' => $validated['estado']]);

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'cotizacion' => $cotizacion->load('estudios.estudio', 'paciente'),
        ]);
    }

    /**
     * Eliminar una cotización
     */
    public function destroy($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        $cotizacion->delete();

        return response()->json(['message' => 'Cotización eliminada correctamente']);
    }

    /**
     * Generar PDF de una cotización guardada
     */
    public function generarPDF($id)
    {
        $cotizacion = Cotizacion::with('estudios.estudio.examenes')->findOrFail($id);

        $estudios = $cotizacion->estudios->map(function ($ce) {
            return [
                'id' => $ce->estudio_id,
                'nombre' => $ce->estudio->nombre,
                'precio' => $ce->precio,
                'cantidad' => $ce->cantidad,
                'descripcion' => $ce->descripcion,
                'examenes' => $ce->estudio->examenes ? $ce->estudio->examenes->map(function ($ex) {
                    return ['nombre_examen' => $ex->nombre_examen];
                })->toArray() : [],
            ];
        })->toArray();

        $monto_descuento = $cotizacion->subtotal * ($cotizacion->descuento / 100);

        $data = [
            'fecha_cotizacion' => $cotizacion->fecha_cotizacion->format('Y-m-d'),
            'vigencia' => $cotizacion->vigencia,
            'cliente' => [
                'nombre' => $cotizacion->nombre_cliente,
                'email' => $cotizacion->email,
                'telefono' => $cotizacion->telefono,
                'direccion' => $cotizacion->direccion,
            ],
            'estudios' => $estudios,
            'subtotal' => $cotizacion->subtotal,
            'descuento' => $cotizacion->descuento,
            'monto_descuento' => $monto_descuento,
            'total' => $cotizacion->total,
            'notas' => $cotizacion->notas ?? '',
            'folio' => $cotizacion->folio,
        ];

        $pdf = Pdf::loadView('pdf.cotizacion_biolab', $data)
            ->setPaper('letter', 'portrait');

        $this->applyPageNumbers($pdf, 500, 770, 9);

        return $pdf->download("cotizacion-{$cotizacion->folio}.pdf");
    }
}
