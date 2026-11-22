<?php

namespace App\Services;

use App\Models\Reporte;
use App\Models\ReporteEstudio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Log;

/**
 * Arma el PDF de resultados de una orden.
 *
 *  - T2: solo salen los estudios con "Incluir en reporte" encendido, o los que se
 *    pidan explicitamente (reporte complementario con el mismo folio).
 *  - Para el paciente (QR, portal de medicos, envio) solo salen estudios validados.
 *  - T3: hoja carta y "Pagina X de Y" calculado sobre la hoja real.
 */
class ReportePdf
{
    public const RELACIONES = [
        'estudios.estudio',
        'estudios.resultados.examen.niveles',
    ];

    /**
     * Deja en la relacion 'estudios' solo los que van impresos.
     *
     * @param  int[]|null  $soloEstudios  ids de reporte_estudio para un reporte complementario
     * @return array{preliminar: bool, complementario: bool, vacio: bool}
     */
    public static function filtrar(Reporte $reporte, ?array $soloEstudios = null, bool $soloValidados = false): array
    {
        $complementario = ! empty($soloEstudios);

        $estudios = $reporte->estudios->filter(function (ReporteEstudio $estudio) use ($soloEstudios, $complementario, $soloValidados) {
            $elegido = $complementario
                ? in_array($estudio->id, $soloEstudios, false)
                : $estudio->incluir_en_reporte !== false;

            return $elegido && (! $soloValidados || $estudio->estaValidado());
        })->values();

        $reporte->setRelation('estudios', new EloquentCollection($estudios->all()));

        return [
            'preliminar' => $estudios->contains(fn (ReporteEstudio $e) => ! $e->estaValidado()),
            'complementario' => $complementario,
            'vacio' => $estudios->isEmpty(),
        ];
    }

    public static function generar(Reporte $reporte, array $opciones = [])
    {
        // 1er intento: los estudios que parecen caber en una hoja no se parten.
        $pdf = self::cargar($reporte, $opciones, true);
        $vacias = self::renderizarYContarHojasVacias($pdf);
        $reintento = false;

        // T3: si aun asi alguno no cupo, DomPDF lo manda entero a la hoja siguiente y
        // deja una hoja sin resultados (la 1, si es el primer estudio). En ese caso se
        // genera otra vez dejando que los estudios se partan entre renglones.
        if ($vacias > 0) {
            $reintento = true;
            $pdf = self::cargar($reporte, $opciones, false);
            $vacias = self::renderizarYContarHojasVacias($pdf);

            if ($vacias > 0) {
                Log::warning('Reporte PDF con hojas sin resultados', ['folio' => $reporte->folio, 'hojas_vacias' => $vacias]);
            }
        }

        self::$ultimo = [
            'hojas' => $pdf->getDomPDF()->getCanvas()->get_page_count(),
            'hojas_vacias' => $vacias,
            'reintento' => $reintento,
        ];

        self::escribirNumeroDePagina($pdf);

        return $pdf;
    }

    /** Diagnostico del ultimo PDF generado (pruebas y log). */
    public static array $ultimo = [];

    private static function cargar(Reporte $reporte, array $opciones, bool $mantenerJuntos)
    {
        return Pdf::loadView('pdf.reporte_biolab', [
            'reporte' => $reporte,
            'preliminar' => $opciones['preliminar'] ?? false,
            'complementario' => $opciones['complementario'] ?? false,
            'mantenerJuntos' => $mantenerJuntos,
        ])->setPaper('letter', 'portrait');
    }

    /**
     * Renderiza y regresa cuantas hojas quedaron sin ningun texto de resultados
     * (lo que esta dentro de .study-block; encabezado y pie no cuentan).
     */
    public static function renderizarYContarHojasVacias($pdf): int
    {
        $dompdf = $pdf->getDomPDF();
        $conContenido = [];

        $dompdf->setCallbacks([[
            'event' => 'end_frame',
            'f' => function ($frame, $canvas) use (&$conContenido) {
                $nodo = $frame->get_node();
                if ($nodo->nodeName !== '#text' || trim($nodo->nodeValue) === '') {
                    return;
                }
                for ($padre = $nodo->parentNode; $padre; $padre = $padre->parentNode) {
                    if ($padre instanceof \DOMElement && preg_match('/\bstudy-block\b/', $padre->getAttribute('class'))) {
                        $conContenido[$canvas->get_page_number()] = true;

                        return;
                    }
                }
            },
        ]]);

        $dompdf->render();
        $dompdf->setCallbacks([]);

        $total = $dompdf->getCanvas()->get_page_count();

        return count(array_diff(range(1, $total), array_keys($conContenido)));
    }

    /**
     * "Página X de Y" en la esquina inferior derecha, sea cual sea el tamaño de hoja.
     */
    public static function numerarPaginas($pdf, int $tamano = 9): void
    {
        $pdf->getDomPDF()->render();

        self::escribirNumeroDePagina($pdf, $tamano);
    }

    private static function escribirNumeroDePagina($pdf, int $tamano = 9): void
    {
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');

        // page_script y no page_text: hay que restablecer la opacidad, si no el
        // numero hereda la transparencia de la marca de agua "PRELIMINAR"
        $canvas->page_script(function ($pagina, $total, $canvas) use ($font, $tamano) {
            $canvas->set_opacity(1.0);
            $canvas->text(
                $canvas->get_width() - 112,
                $canvas->get_height() - 24,
                "Página {$pagina} de {$total}",
                $font,
                $tamano,
                [0.4, 0.4, 0.4],
            );
        });
    }

    /**
     * ids de "?estudios=3,5" (o estudios[]=3&estudios[]=5).
     */
    public static function idsDesdeRequest($valor): ?array
    {
        if ($valor === null || $valor === '' || $valor === []) {
            return null;
        }

        $ids = is_array($valor) ? $valor : explode(',', (string) $valor);
        $ids = array_values(array_filter(array_map('intval', $ids)));

        return $ids ?: null;
    }
}
