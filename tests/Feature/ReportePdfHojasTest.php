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
 * T3 (reabierto nov 2026): ningun reporte debe salir con una hoja sin resultados.
 * Caso real: RPT-0307, Perfil TORCH de 10 renglones cuya referencia ocupa 3 lineas;
 * se mantenia "junto", no cabia y DomPDF dejaba la hoja 1 en blanco.
 */
class ReportePdfHojasTest extends TestCase
{
    use RefreshDatabase;

    private function estudio(string $nombre, int $examenes, string $referencia): Estudio
    {
        $estudio = Estudio::create(['nombre' => $nombre, 'precio' => 500]);
        foreach (range(1, $examenes) as $i) {
            Examen::create([
                'estudio_id' => $estudio->id,
                'nombre_examen' => "{$nombre} analito {$i}",
                'unidad' => 'UI/mL',
                'valor_referencia' => $referencia,
            ]);
        }

        return $estudio;
    }

    private function crearOrden(array $estudios, ?callable $resultado = null): Reporte
    {
        $resultado ??= fn ($i) => (string) (1 + $i / 10);

        $respuesta = $this->actingAs(User::factory()->create(['rol' => 'admin']))->postJson('/api/reportes', [
            'cliente' => ['nombre' => 'José Núñez', 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Masculino', 'telefono' => '9981112233'],
            'toma_muestra' => '2026-11-20T08:00',
            'fecha_reporte' => '2026-11-20T09:00',
            'fecha_validacion' => '2026-11-20T09:30',
            'descuento' => 0,
            'estudios' => collect($estudios)->map(fn (Estudio $e) => [
                'id' => $e->id,
                'precio' => 500,
                'elaboro' => 'QFB',
                'valido' => 'QFB',
                'incluir_en_reporte' => true,
                'observaciones' => 'Sin observaciones adicionales para este estudio.',
                'examenes' => $e->examenes()->orderBy('id')->get()->values()
                    ->map(fn ($x, $i) => ['id' => $x->id, 'resultado' => $resultado($i)])->all(),
            ])->all(),
        ])->assertOk();

        return Reporte::with(ReportePdf::RELACIONES)->findOrFail($respuesta->json('id'));
    }

    private function generar(Reporte $reporte): array
    {
        $estado = ReportePdf::filtrar($reporte);
        ReportePdf::generar($reporte, $estado)->output();

        return ReportePdf::$ultimo;
    }

    public function test_un_estudio_con_referencias_largas_no_deja_la_primera_hoja_en_blanco(): void
    {
        $torch = $this->estudio('Perfil TORCH', 10, 'NEGATIVO A 0.9 INDETERMINADO 0.9 - 1.0 POSITIVO MAYOR A 1.0');

        $diagnostico = $this->generar($this->crearOrden([$torch]));

        $this->assertSame(0, $diagnostico['hojas_vacias']);
        $this->assertLessThanOrEqual(2, $diagnostico['hojas']);
    }

    public function test_referencias_de_varias_lineas_tampoco_dejan_hojas_vacias(): void
    {
        $largo = $this->estudio('Perfil hormonal', 14, "Fase folicular 2.4 - 12.6\nFase ovulatoria 14.0 - 95.6\nFase lútea 1.0 - 11.4\nPosmenopausia 7.7 - 58.5");
        $corto = $this->estudio('Química', 6, '70 - 100');

        $diagnostico = $this->generar($this->crearOrden([$largo, $corto, $largo]));

        $this->assertSame(0, $diagnostico['hojas_vacias']);
    }

    public function test_si_la_estimacion_falla_se_regenera_sin_hojas_vacias(): void
    {
        // Se simula que la estimacion se equivoca: todo estudio "cabe" en una hoja
        config(['reportes.altura_util_px' => 99999]);
        $torch = $this->estudio('Perfil TORCH', 12, 'NEGATIVO A 0.9 INDETERMINADO 0.9 - 1.0 POSITIVO MAYOR A 1.0');

        $diagnostico = $this->generar($this->crearOrden([$torch]));

        $this->assertTrue($diagnostico['reintento']);
        $this->assertSame(0, $diagnostico['hojas_vacias']);
    }

    public function test_resultados_de_texto_largos_no_dejan_hojas_vacias(): void
    {
        $cultivo = $this->estudio('Cultivo', 9, 'Ver interpretación');
        $orden = $this->crearOrden([$cultivo], fn ($i) => str_repeat('Desarrollo de flora mixta habitual. ', 6));

        $diagnostico = $this->generar($orden);

        $this->assertFalse($diagnostico['reintento']);
        $this->assertSame(0, $diagnostico['hojas_vacias']);
    }

    public function test_una_orden_sin_resultados_no_sale_como_hoja_vacia(): void
    {
        $quimica = $this->estudio('Química', 3, '70 - 100');
        $orden = $this->crearOrden([$quimica], fn () => '');

        $diagnostico = $this->generar($orden);

        $this->assertSame(1, $diagnostico['hojas']);
        $this->assertSame(0, $diagnostico['hojas_vacias']);
    }
}
