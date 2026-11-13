<?php

namespace App\Services;

use App\Models\Reporte;
use App\Models\ReporteEstudio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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
        $pdf = Pdf::loadView('pdf.reporte_biolab', [
            'reporte' => $reporte,
            'preliminar' => $opciones['preliminar'] ?? false,
            'complementario' => $opciones['complementario'] ?? false,
        ])->setPaper('letter', 'portrait');

        self::numerarPaginas($pdf);

        return $pdf;
    }

    /**
     * "Página X de Y" en la esquina inferior derecha, sea cual sea el tamaño de hoja.
     */
    public static function numerarPaginas($pdf, int $tamano = 9): void
    {
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

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
