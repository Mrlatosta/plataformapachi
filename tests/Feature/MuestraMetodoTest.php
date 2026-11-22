<?php

namespace Tests\Feature;

use App\Models\Estudio;
use App\Models\Examen;
use App\Models\Reporte;
use App\Models\ReporteEstudio;
use App\Models\User;
use App\Services\ReportePdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T13: metodo y tipo de muestra se eligen de una lista al capturar.
 */
class MuestraMetodoTest extends TestCase
{
    use RefreshDatabase;

    private Estudio $creatinina;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creatinina = Estudio::create([
            'nombre' => 'Creatinina', 'precio' => 120,
            'tipo_muestra' => 'SUERO', 'metodo' => 'JAFFÉ CINÉTICO',
            'opciones_muestra' => ['PLASMA'], 'opciones_metodo' => ['ENZIMÁTICO'],
        ]);
        Examen::create(['estudio_id' => $this->creatinina->id, 'nombre_examen' => 'Creatinina', 'unidad' => 'mg/dL', 'valor_referencia' => '0.6 a 1.3']);
    }

    private function orden(User $usuario, array $estudio = []): Reporte
    {
        $respuesta = $this->actingAs($usuario)->postJson('/api/reportes', [
            'cliente' => ['nombre' => 'María Pérez', 'fecha_nacimiento' => '1980-01-01', 'sexo' => 'Femenino'],
            'toma_muestra' => '2026-11-20T08:00',
            'fecha_reporte' => '2026-11-20T09:00',
            'fecha_validacion' => '2026-11-20T09:30',
            'estudios' => [[
                'id' => $this->creatinina->id,
                'precio' => 120,
                'tipo_muestra' => 'SUERO',
                'metodo' => 'JAFFÉ CINÉTICO',
                'examenes' => [['id' => $this->creatinina->examenes()->first()->id, 'resultado' => '0.9']],
                ...$estudio,
            ]],
        ])->assertOk();

        return Reporte::with(ReportePdf::RELACIONES)->findOrFail($respuesta->json('id'));
    }

    private function html(Reporte $reporte): string
    {
        $estado = ReportePdf::filtrar($reporte);

        return view('pdf.reporte_biolab', ['reporte' => $reporte, ...$estado])->render();
    }

    public function test_si_no_se_cambia_nada_queda_lo_de_siempre_sin_registro_de_cambio(): void
    {
        $estudio = $this->orden(User::factory()->create(['rol' => 'admin']))->estudios->first();

        $this->assertSame('SUERO', $estudio->tipo_muestra);
        $this->assertSame('JAFFÉ CINÉTICO', $estudio->metodo);
        $this->assertNull($estudio->muestra_metodo_cambiado_por);
    }

    public function test_el_admin_cambia_el_metodo_solo_para_esa_orden_y_queda_registrado(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $cambiada = $this->orden($admin, ['metodo' => 'ENZIMÁTICO']);
        $normal = $this->orden($admin);

        $estudio = $cambiada->estudios->first();
        $this->assertSame('ENZIMÁTICO', $estudio->metodo);
        $this->assertSame($admin->id, $estudio->muestra_metodo_cambiado_por);
        $this->assertNotNull($estudio->muestra_metodo_cambiado_at);

        // El PDF de esa orden muestra el nuevo; la otra orden y el catalogo no cambian
        $this->assertStringContainsString('Método: ENZIMÁTICO', $this->html($cambiada));
        $this->assertStringContainsString('Método: JAFFÉ CINÉTICO', $this->html($normal));
        $this->assertSame('JAFFÉ CINÉTICO', $this->creatinina->fresh()->metodo);
    }

    public function test_recepcion_no_puede_cambiar_muestra_ni_metodo(): void
    {
        $recepcion = User::factory()->create(['rol' => 'recepcion']);
        $estudio = $this->orden($recepcion, ['tipo_muestra' => 'ORINA', 'metodo' => 'INVENTADO'])->estudios->first();

        $this->assertSame('SUERO', $estudio->tipo_muestra);
        $this->assertSame('JAFFÉ CINÉTICO', $estudio->metodo);
        $this->assertNull($estudio->muestra_metodo_cambiado_por);
    }

    public function test_al_editar_recepcion_conserva_lo_que_eligio_el_admin(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $reporte = $this->orden($admin, ['metodo' => 'ENZIMÁTICO']);
        /** @var ReporteEstudio $estudio */
        $estudio = $reporte->estudios->first();

        $this->actingAs(User::factory()->create(['rol' => 'recepcion']))->putJson("/api/reportes/{$reporte->id}", [
            'toma_muestra' => '2026-11-20T08:00',
            'fecha_reporte' => '2026-11-20T09:00',
            'cliente' => ['nombre' => 'María Pérez', 'fecha_nacimiento' => '1980-01-01', 'sexo' => 'Femenino'],
            'estudios' => [[
                'id' => $estudio->id,
                'estudio_id' => $this->creatinina->id,
                'tipo_muestra' => 'ORINA',
                'metodo' => 'JAFFÉ CINÉTICO',
                'resultados' => $estudio->resultados->map(fn ($r) => ['id' => $r->id, 'examen_id' => $r->examen_id, 'resultado' => $r->resultado])->all(),
            ]],
        ])->assertOk();

        $estudio->refresh();
        $this->assertSame('SUERO', $estudio->tipo_muestra);
        $this->assertSame('ENZIMÁTICO', $estudio->metodo);
        $this->assertSame($admin->id, $estudio->muestra_metodo_cambiado_por);
    }

    public function test_el_catalogo_guarda_las_opciones_sin_repetidos(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin']))->putJson("/api/estudios/{$this->creatinina->id}", [
            'nombre' => 'Creatinina',
            'precio' => 120,
            'tipo_muestra' => 'SUERO',
            'metodo' => 'JAFFÉ CINÉTICO',
            'opciones_muestra' => ['Plasma', ' plasma ', 'suero', ''],
            'opciones_metodo' => ['Enzimático', 'Jaffé cinético'],
            'examenes' => $this->creatinina->examenes->map(fn ($x) => $x->only(['id', 'nombre_examen', 'unidad', 'valor_referencia']))->all(),
        ])->assertOk();

        $estudio = $this->creatinina->fresh();
        $this->assertSame(['Plasma'], $estudio->opciones_muestra);
        $this->assertSame(['Enzimático'], $estudio->opciones_metodo);
    }
}
