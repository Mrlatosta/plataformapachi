<?php

namespace Tests\Feature;

use App\Models\Comision;
use App\Models\Estudio;
use App\Models\Examen;
use App\Models\Medico;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T12: tabla automatica de comisiones por medico que refiere.
 */
class ComisionesTest extends TestCase
{
    use RefreshDatabase;

    private Estudio $quimica;
    private Estudio $bh;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'admin']);
        $this->quimica = Estudio::create(['nombre' => 'Química sanguínea', 'precio' => 300]);
        Examen::create(['estudio_id' => $this->quimica->id, 'nombre_examen' => 'Glucosa', 'unidad' => 'mg/dL', 'valor_referencia' => '70 - 100']);
        $this->bh = Estudio::create(['nombre' => 'Biometría hemática', 'precio' => 200]);
        Examen::create(['estudio_id' => $this->bh->id, 'nombre_examen' => 'Hemoglobina', 'unidad' => 'g/dL', 'valor_referencia' => '12 - 16']);
    }

    private function orden(?int $medicoId, array $extra = [], ?User $usuario = null, string $fecha = '2026-11-10'): Reporte
    {
        $respuesta = $this->actingAs($usuario ?? $this->admin)->postJson('/api/reportes', [
            'cliente' => ['nombre' => 'Paciente '.uniqid(), 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Masculino'],
            'toma_muestra' => "{$fecha}T08:00",
            'fecha_reporte' => "{$fecha}T09:00",
            'fecha_validacion' => "{$fecha}T09:30",
            'medico_id' => $medicoId,
            'medico_solicitante' => 'texto escrito a mano que no se usa',
            'estudios' => collect([$this->quimica, $this->bh])->map(fn (Estudio $e) => [
                'id' => $e->id,
                'precio' => (float) $e->precio,
                'examenes' => [['id' => $e->examenes()->first()->id, 'resultado' => '90']],
            ])->all(),
            ...$extra,
        ])->assertOk();

        return Reporte::findOrFail($respuesta->json('id'));
    }

    public function test_al_registrar_una_orden_con_medico_su_comision_aparece_sola(): void
    {
        $medico = Medico::create(['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 10]);

        // 300 + 200 = 500; 10 % de descuento → 450; el IVA no cuenta
        $reporte = $this->orden($medico->id, ['descuento' => 10, 'aplica_iva' => true]);

        $this->assertSame('Dr. Luis Yama', $reporte->medico_solicitante); // el nombre sale de la lista
        $comision = $reporte->comision;
        $this->assertNotNull($comision);
        $this->assertEquals(450.00, $comision->monto_base);
        $this->assertEquals(10.00, $comision->porcentaje);
        $this->assertEquals(45.00, $comision->comision);
        $this->assertSame(Comision::PENDIENTE, $comision->estado);
    }

    public function test_sin_medico_no_hay_comision(): void
    {
        $reporte = $this->orden(null);

        $this->assertNull($reporte->medico_solicitante); // PDF: "A QUIEN CORRESPONDA"
        $this->assertNull($reporte->comision);
    }

    public function test_si_cambia_el_descuento_o_el_medico_la_comision_se_recalcula(): void
    {
        $yama = Medico::create(['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 10]);
        $cano = Medico::create(['nombre' => 'Dr. Luis Cano', 'porcentaje_comision' => 5]);
        $reporte = $this->orden($yama->id)->load('estudios.resultados');

        $editar = fn (array $cambios) => $this->actingAs($this->admin)->putJson("/api/reportes/{$reporte->id}", [
            'toma_muestra' => '2026-11-10T08:00',
            'fecha_reporte' => '2026-11-10T09:00',
            'cliente' => ['nombre' => $reporte->nombre_cliente, 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Masculino'],
            'medico_id' => $yama->id,
            'estudios' => $reporte->estudios->map(fn ($e) => [
                'id' => $e->id, 'estudio_id' => $e->estudio_id, 'precio' => (float) $e->precio,
                'resultados' => $e->resultados->map(fn ($r) => ['id' => $r->id, 'examen_id' => $r->examen_id, 'resultado' => $r->resultado])->all(),
            ])->all(),
            ...$cambios,
        ])->assertOk();

        $editar(['descuento' => 20]);
        $this->assertEquals(40.00, $reporte->comision()->first()->comision); // 400 × 10 %

        $editar(['descuento' => 20, 'medico_id' => $cano->id]);
        $comision = $reporte->comision()->first();
        $this->assertSame($cano->id, $comision->medico_id);
        $this->assertEquals(20.00, $comision->comision); // 400 × 5 %

        $editar(['medico_id' => null]);
        $this->assertNull($reporte->comision()->first());
    }

    public function test_el_reporte_suma_por_medico_y_marca_pagadas_las_del_periodo(): void
    {
        $yama = Medico::create(['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 10]);
        $cano = Medico::create(['nombre' => 'Dr. Luis Cano', 'porcentaje_comision' => 5]);
        $this->orden($yama->id, [], null, '2026-11-03');
        $this->orden($yama->id, ['descuento' => 10], null, '2026-11-12');
        $this->orden($cano->id, [], null, '2026-11-20');
        $this->orden($yama->id, [], null, '2026-10-30'); // fuera del periodo
        $this->orden(null, [], null, '2026-11-05');       // sin medico

        $datos = $this->actingAs($this->admin)->getJson('/api/comisiones?desde=2026-11-01&hasta=2026-11-30')->assertOk()->json();

        // Suma manual: Yama 50 + 45 = 95; Cano 25 → 120
        $porMedico = collect($datos['por_medico'])->keyBy('medico');
        $this->assertEquals(95, $porMedico['Dr. Luis Yama']['comision']);
        $this->assertSame(2, $porMedico['Dr. Luis Yama']['ordenes']);
        $this->assertEquals(25, $porMedico['Dr. Luis Cano']['comision']);
        $this->assertEquals(120, $datos['total']['comision']);
        $this->assertSame(3, $datos['total']['ordenes']);

        // Se paga el corte de Yama de la primera quincena
        $this->actingAs($this->admin)->postJson('/api/comisiones/pagar', ['desde' => '2026-11-01', 'hasta' => '2026-11-15', 'medico_id' => $yama->id])
            ->assertOk()->assertJson(['marcadas' => 2]);

        $datos = $this->actingAs($this->admin)->getJson('/api/comisiones?desde=2026-11-01&hasta=2026-11-30')->json();
        $this->assertEquals(95, $datos['total']['pagada']);
        $this->assertEquals(25, $datos['total']['pendiente']);
        $pagada = collect($datos['filas'])->firstWhere('estado', 'pagada');
        $this->assertNotNull($pagada['pagada_at']);

        // Deshacer
        $this->actingAs($this->admin)->postJson("/api/comisiones/{$pagada['id']}/revertir")->assertOk();
        $this->assertSame(Comision::PENDIENTE, Comision::find($pagada['id'])->estado);
    }

    public function test_una_comision_pagada_no_cambia_si_se_edita_la_orden(): void
    {
        $yama = Medico::create(['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 10]);
        $reporte = $this->orden($yama->id);
        $this->actingAs($this->admin)->postJson('/api/comisiones/pagar', ['desde' => '2026-11-01', 'hasta' => '2026-11-30'])->assertOk();

        $yama->update(['porcentaje_comision' => 50]);
        \App\Services\Comisiones::aplicarPorcentaje($yama);
        \App\Services\Comisiones::sincronizar($reporte->fresh('estudios'));

        $this->assertEquals(50.00, $reporte->comision()->first()->comision);
    }

    public function test_al_cambiar_el_porcentaje_se_recalculan_solo_las_pendientes(): void
    {
        $yama = Medico::create(['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 0]);
        $pagada = $this->orden($yama->id, [], null, '2026-11-02');
        $this->actingAs($this->admin)->postJson('/api/comisiones/pagar', ['desde' => '2026-11-01', 'hasta' => '2026-11-02'])->assertOk();
        $pendiente = $this->orden($yama->id, [], null, '2026-11-10');

        $this->actingAs($this->admin)->putJson("/api/medicos/{$yama->id}", ['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 12])
            ->assertOk()->assertJson(['comisiones_recalculadas' => 1]);

        $this->assertEquals(60.00, $pendiente->comision()->first()->comision); // 500 × 12 %
        $this->assertEquals(0.00, $pagada->comision()->first()->comision);
    }

    public function test_exporta_excel_y_pdf(): void
    {
        $yama = Medico::create(['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 10]);
        $this->orden($yama->id);

        $excel = $this->actingAs($this->admin)->get('/comisiones/exportar/excel?desde=2026-11-01&hasta=2026-11-30')->assertOk();
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $excel->headers->get('content-type'));
        $this->assertStringStartsWith("PK\x03\x04", $excel->getContent());
        $this->assertStringContainsString('Dr. Luis Yama', $excel->getContent());

        $pdf = $this->actingAs($this->admin)->get('/comisiones/exportar/pdf?desde=2026-11-01&hasta=2026-11-30&medico_id='.$yama->id)->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_recepcion_no_ve_comisiones(): void
    {
        $recepcion = User::factory()->create(['rol' => 'recepcion']);
        Medico::create(['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 10, 'usuario' => 'yama', 'password' => 'secreta']);

        $this->actingAs($recepcion)->get('/comisiones')->assertForbidden();
        $this->actingAs($recepcion)->getJson('/api/comisiones')->assertForbidden();
        $this->actingAs($recepcion)->get('/comisiones/exportar/excel')->assertForbidden();
        $this->actingAs($recepcion)->postJson('/api/comisiones/pagar')->assertForbidden();

        // Elige médico de la lista, pero sin ver su % ni su usuario del portal
        $medicos = $this->actingAs($recepcion)->getJson('/api/medicos')->assertOk()->json();
        $this->assertSame(['id', 'nombre', 'especialidad', 'activo'], array_keys($medicos[0]));
    }

    public function test_alta_rapida_no_duplica_al_mismo_medico_escrito_distinto(): void
    {
        $recepcion = User::factory()->create(['rol' => 'recepcion']);
        $yama = Medico::create(['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 10]);

        $this->actingAs($recepcion)->postJson('/api/medicos/rapido', ['nombre' => 'DR. LUIS YAMA'])
            ->assertOk()->assertJson(['id' => $yama->id, 'existente' => true]);
        $this->actingAs($recepcion)->postJson('/api/medicos/rapido', ['nombre' => 'dr luis yamá'])
            ->assertJson(['id' => $yama->id]);

        // Recepción puede dar de alta uno nuevo, pero sin %
        $nuevo = $this->actingAs($recepcion)->postJson('/api/medicos/rapido', ['nombre' => 'Dra. Ana Pech', 'porcentaje_comision' => 30])
            ->assertCreated()->json();
        $this->assertEquals(0, Medico::find($nuevo['id'])->porcentaje_comision);
        $this->assertSame(2, Medico::count()); // Yama y Pech, sin duplicados

        $this->actingAs($recepcion)->postJson('/api/medicos/rapido', ['nombre' => 'A quien corresponda'])->assertStatus(422);
    }

    public function test_un_medico_con_historial_no_se_borra(): void
    {
        $yama = Medico::create(['nombre' => 'Dr. Luis Yama', 'porcentaje_comision' => 10]);
        $this->orden($yama->id);

        $this->actingAs($this->admin)->deleteJson("/api/medicos/{$yama->id}")->assertStatus(422);
        $this->assertNotNull($yama->fresh());
    }

    public function test_el_comando_liga_las_ordenes_escritas_a_mano(): void
    {
        $crear = fn (?string $texto, string $fecha = '2026-11-05') => Reporte::create([
            'folio' => Reporte::siguienteFolio(), 'nombre_cliente' => 'X', 'fecha_nacimiento' => '1990-01-01', 'edad' => 36, 'sexo' => 'Masculino',
            'toma_muestra' => $fecha, 'fecha_reporte' => $fecha, 'medico_solicitante' => $texto,
        ])->estudios()->create(['estudio_id' => $this->quimica->id, 'precio' => 300, 'tipo_muestra' => '', 'metodo' => '', 'elaboro' => '', 'valido' => ''])->reporte;

        // Incluye un error de dedo de una letra ("UIS"), que se junta con el más usado
        $variantes = [$crear('DR. LUIS YAMA'), $crear('dr. luis yama'), $crear('Dr. Luis yama'), $crear('DR. UIS YAMA')];
        $sinMedico = [$crear('N/A'), $crear('A quien corresponda.'), $crear('A QUIEN COREESPONDA.'), $crear('')];
        $crear('Dr Javier Sainz');

        // Sin --aplicar no cambia nada
        $this->artisan('medicos:vincular-reportes')->assertSuccessful();
        $this->assertSame(0, Medico::count());

        $this->artisan('medicos:vincular-reportes --aplicar')->assertSuccessful();

        $this->assertSame(2, Medico::count());
        $yama = Medico::where('nombre', 'Dr. Luis Yama')->firstOrFail();
        foreach ($variantes as $reporte) {
            $reporte->refresh();
            $this->assertSame($yama->id, $reporte->medico_id);
            $this->assertSame('Dr. Luis Yama', $reporte->medico_solicitante);
            $this->assertNotNull($reporte->comision); // pendiente, en 0 hasta poner el %
        }
        foreach ($sinMedico as $reporte) {
            $this->assertNull($reporte->fresh()->medico_id);
        }

        // Se puede volver a correr sin duplicar
        $this->artisan('medicos:vincular-reportes --aplicar')->assertSuccessful();
        $this->assertSame(2, Medico::count());
        $this->assertSame(5, Comision::count()); // 4 de Yama + 1 de Sainz
    }
}
