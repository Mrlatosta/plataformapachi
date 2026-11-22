<?php

namespace Tests\Feature;

use App\Models\Estudio;
use App\Models\Examen;
use App\Models\Reporte;
use App\Models\User;
use App\Services\ReportePdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T10: TFGe y relacion albumina/creatinina en el PDF.
 */
class FuncionRenalPdfTest extends TestCase
{
    use RefreshDatabase;

    private Estudio $quimica;
    private Estudio $cistatina;
    private Estudio $albuminuria;
    private Estudio $biometria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quimica = $this->estudio('Química sanguínea de 6 elementos', 'SUERO', [
            ['Glucosa', 'mg/dL', '70 a 100'],
            ['Urea', 'mg/dL', '10 a 55'],
            ['Creatinina', 'mg/dL', '0.6 a 1.3'],
        ]);
        $this->cistatina = $this->estudio('Cistatina C', 'SUERO', [['Cistatina C', 'mg/L', '0.51-1.09']]);
        $this->albuminuria = $this->estudio('Relación albúmina/creatinina en orina', 'ORINA', [
            ['Albúmina en orina', 'mg/L', '< 30'],
            ['Creatinina en orina', 'mg/dL', '20 a 300'],
        ]);
        $this->biometria = $this->estudio('Biometría hemática', 'SANGRE TOTAL', [['Hemoglobina', 'g/dL', '12 a 16']]);
    }

    private function estudio(string $nombre, string $muestra, array $examenes): Estudio
    {
        $estudio = Estudio::create(['nombre' => $nombre, 'precio' => 300, 'tipo_muestra' => $muestra, 'metodo' => 'COLORIMETRÍA']);
        foreach ($examenes as $i => [$examen, $unidad, $referencia]) {
            Examen::create(['estudio_id' => $estudio->id, 'nombre_examen' => $examen, 'unidad' => $unidad, 'valor_referencia' => $referencia, 'orden' => $i]);
        }

        return $estudio;
    }

    /** @param array<string, array<string, string>> $resultados nombre de estudio → [examen => valor] */
    private function orden(array $resultados, string $nacimiento = '1956-01-01', string $sexo = 'Masculino'): Reporte
    {
        $estudios = collect([$this->quimica, $this->cistatina, $this->albuminuria, $this->biometria])
            ->filter(fn ($e) => isset($resultados[$e->nombre]));

        $respuesta = $this->actingAs(User::factory()->create(['rol' => 'admin']))->postJson('/api/reportes', [
            'cliente' => ['nombre' => 'Paciente Renal', 'fecha_nacimiento' => $nacimiento, 'sexo' => $sexo, 'telefono' => '9981112233'],
            'toma_muestra' => '2026-11-20T08:00',
            'fecha_reporte' => '2026-11-20T09:00',
            'fecha_validacion' => '2026-11-20T09:30',
            'estudios' => $estudios->map(fn (Estudio $e) => [
                'id' => $e->id,
                'precio' => 300,
                'tipo_muestra' => $e->tipo_muestra,
                'metodo' => $e->metodo,
                'incluir_en_reporte' => true,
                'examenes' => $e->examenes()->orderBy('orden')->get()
                    ->map(fn ($x) => ['id' => $x->id, 'resultado' => $resultados[$e->nombre][$x->nombre_examen] ?? ''])->all(),
            ])->values()->all(),
        ])->assertOk();

        return Reporte::with(ReportePdf::RELACIONES)->findOrFail($respuesta->json('id'));
    }

    /** HTML del reporte y diagnostico de hojas del PDF. */
    private function generar(Reporte $reporte, string $nombre): array
    {
        $estado = ReportePdf::filtrar($reporte);
        $html = view('pdf.reporte_biolab', ['reporte' => $reporte, ...$estado])->render();
        $pdf = ReportePdf::generar($reporte, $estado)->output();

        if ($dir = env('SALIDA_PDF')) {
            @mkdir($dir, 0777, true);
            file_put_contents("{$dir}/{$nombre}.pdf", $pdf);
        }

        return [$html, ReportePdf::$ultimo];
    }

    public function test_la_tfge_sale_debajo_de_la_creatinina_con_su_tabla_y_nota(): void
    {
        // 1.3 mg/dL, 70 años, hombre → 59 (G3a) con ↓
        $reporte = $this->orden(['Química sanguínea de 6 elementos' => ['Glucosa' => '95', 'Urea' => '40', 'Creatinina' => '1.3']]);

        [$html, $diag] = $this->generar($reporte, 'tfge_quimica');

        $this->assertStringContainsString('Tasa de filtración glomerular estimada (TFGe)', $html);
        $this->assertMatchesRegularExpression('/59\s*<span class="flecha-rango">↓<\/span>/u', $html);
        $this->assertStringContainsString('G3a Leve a moderadamente disminuida &nbsp;◄ Resultado', $html);
        $this->assertStringContainsString('No es válida en embarazo', $html);
        // Debajo de la creatinina, no antes
        $this->assertGreaterThan(strpos($html, '>Creatinina<'), strpos($html, 'Tasa de filtración glomerular'));
        // Sin cistatina ni albuminuria no hay hoja de perfil
        $this->assertStringNotContainsString('FUNCIÓN RENAL ·', $html);
        $this->assertSame(0, $diag['hojas_vacias']);
    }

    public function test_en_menores_de_18_no_aparece_la_tfge(): void
    {
        $reporte = $this->orden(['Química sanguínea de 6 elementos' => ['Creatinina' => '0.7']], '2012-05-01');

        [$html] = $this->generar($reporte, 'tfge_menor');

        $this->assertStringContainsString('>Creatinina<', $html);
        $this->assertStringNotContainsString('Tasa de filtración glomerular', $html);
        $this->assertStringNotContainsString('No es válida en embarazo', $html);
    }

    public function test_perfil_renal_completo_en_una_sola_hoja(): void
    {
        // Caso del ticket: Cr 1.0 + cistatina 1.0, 50 años, hombre → 92 / 81 / 88; ACR 30.0 (A2)
        $reporte = $this->orden([
            'Química sanguínea de 6 elementos' => ['Glucosa' => '95', 'Urea' => '40', 'Creatinina' => '1.0'],
            'Cistatina C' => ['Cistatina C' => '1.0'],
            'Relación albúmina/creatinina en orina' => ['Albúmina en orina' => '45', 'Creatinina en orina' => '150'],
        ], '1976-01-01');

        [$html, $diag] = $this->generar($reporte, 'perfil_renal');

        $this->assertStringContainsString('FUNCIÓN RENAL · FILTRACIÓN GLOMERULAR ESTIMADA Y ALBUMINURIA', $html);
        // Las seis secciones, en orden
        $posiciones = array_map(fn ($t) => strpos($html, $t), [
            '1. Marcadores de función renal', '2. Filtración glomerular estimada', '3. Categoría de filtración',
            '4. Albuminuria', '5. Clasificación KDIGO', '6. Ecuaciones',
        ]);
        $this->assertNotContains(false, $posiciones);
        $ordenadas = $posiciones;
        sort($ordenadas);
        $this->assertSame($ordenadas, $posiciones);

        $this->assertMatchesRegularExpression('/TFGe combinada creatinina \+ cistatina C.*?<td[^>]*>88</su', $html);
        $this->assertMatchesRegularExpression('/TFGe por cistatina C.*?<td[^>]*>81</su', $html);
        $this->assertStringContainsString('30.0<span class="flecha-rango">↑</span>', $html);
        // G de la combinada (88 → G2) + A del ACR (A2)
        $this->assertStringContainsString('<span class="pr-kdigo-valor">G2 A2</span>', $html);

        // Cistatina y albuminuria no se repiten en su propia tabla; la química sí sale
        $this->assertStringNotContainsString('<th colspan="4" class="study-title">CISTATINA C</th>', $html);
        $this->assertStringContainsString('QUÍMICA SANGUÍNEA DE 6 ELEMENTOS', $html);
        // Con hoja de perfil, la TFGe no se repite debajo de la creatinina de la química
        $this->assertSame(1, substr_count($html, 'No es válida en embarazo'));

        $this->assertSame(0, $diag['hojas_vacias']);
        $this->assertSame(2, $diag['hojas']); // química + hoja de perfil
    }

    public function test_solo_el_perfil_sale_en_una_hoja_y_sin_huecos(): void
    {
        // Sin creatinina sérica: solo cistatina (G de la cistatina) y albuminuria (A3)
        $reporte = $this->orden([
            'Cistatina C' => ['Cistatina C' => '1.8'],
            'Relación albúmina/creatinina en orina' => ['Albúmina en orina' => '675', 'Creatinina en orina' => '150'],
        ], '1956-01-01');

        [$html, $diag] = $this->generar($reporte, 'perfil_sin_creatinina');

        $this->assertSame(1, $diag['hojas']);
        $this->assertSame(0, $diag['hojas_vacias']);
        // Sin creatinina no hay renglon de TFGe por creatinina ni combinada (las ecuaciones si se citan)
        $this->assertStringNotContainsString('TFGe combinada creatinina + cistatina C <span', $html);
        $this->assertStringNotContainsString('TFGe por creatinina <span', $html);
        $this->assertStringContainsString('TFGe por cistatina C <span', $html);
        $this->assertStringContainsString('450.0', $html);
        $this->assertStringContainsString('<span class="pr-kdigo-valor">G3b A3</span>', $html);
    }

    public function test_la_vista_previa_sin_guardar_tambien_calcula(): void
    {
        $respuesta = $this->actingAs(User::factory()->create(['rol' => 'admin']))->post('/api/reportes/preview', [
            'cliente' => ['nombre' => 'Paciente Renal', 'fecha_nacimiento' => '1978-01-01', 'sexo' => 'Femenino'],
            'toma_muestra' => '2026-11-20T08:00',
            'fecha_reporte' => '2026-11-20T09:00',
            'fecha_validacion' => '2026-11-20T09:30',
            'estudios' => [[
                'id' => $this->quimica->id,
                'precio' => 300,
                'incluir_en_reporte' => true,
                'examenes' => $this->quimica->examenes()->get()->map(fn ($x) => ['id' => $x->id, 'resultado' => $x->nombre_examen === 'Creatinina' ? '0.71' : '90'])->all(),
            ]],
        ]);

        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(0, ReportePdf::$ultimo['hojas_vacias']);
    }
}
