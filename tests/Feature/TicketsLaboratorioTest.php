<?php

namespace Tests\Feature;

use App\Models\Estudio;
use App\Models\Examen;
use App\Models\Paciente;
use App\Models\Reporte;
use App\Models\ReporteEstudio;
use App\Models\User;
use App\Services\ReportePdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tickets de noviembre 2026: historial de pacientes (T1), estudios pendientes y
 * reporte complementario (T2) y usuario de Recepción (T6).
 */
class TicketsLaboratorioTest extends TestCase
{
    use RefreshDatabase;

    private Estudio $quimica;
    private Estudio $urocultivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quimica = Estudio::create(['nombre' => 'Química sanguínea', 'precio' => 300]);
        Examen::create(['estudio_id' => $this->quimica->id, 'nombre_examen' => 'Glucosa', 'unidad' => 'mg/dL', 'valor_referencia' => '70 - 100']);

        $this->urocultivo = Estudio::create(['nombre' => 'Urocultivo', 'precio' => 450]);
        Examen::create(['estudio_id' => $this->urocultivo->id, 'nombre_examen' => 'Observaciones:', 'valor_referencia' => '']);
    }

    private function admin(): User
    {
        return User::factory()->create(['rol' => 'admin']);
    }

    private function recepcion(): User
    {
        return User::factory()->create(['rol' => 'recepcion']);
    }

    private function orden(array $cambios = []): array
    {
        return array_replace_recursive([
            'cliente' => ['nombre' => 'Analucía López Pérezgil', 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Femenino', 'telefono' => '9981234567'],
            'toma_muestra' => '2026-11-09T08:00',
            'fecha_reporte' => '2026-11-09T09:00',
            'fecha_validacion' => '2026-11-09T09:30',
            'descuento' => 0,
            'estudios' => [
                [
                    'id' => $this->quimica->id,
                    'precio' => 300,
                    'elaboro' => 'QFB',
                    'valido' => 'QFB',
                    'incluir_en_reporte' => true,
                    'examenes' => [['id' => $this->quimica->examenes()->first()->id, 'resultado' => '126.0', 'fuera_rango' => true, 'direccion_rango' => 'alto']],
                ],
                [
                    'id' => $this->urocultivo->id,
                    'precio' => 450,
                    'elaboro' => 'QFB',
                    'valido' => 'QFB',
                    // Sale a las 72 horas
                    'incluir_en_reporte' => false,
                    'examenes' => [['id' => $this->urocultivo->examenes()->first()->id, 'resultado' => '']],
                ],
            ],
        ], $cambios);
    }

    // ---------- T6: permisos ----------

    public function test_recepcion_no_puede_entrar_a_opciones_de_administrador(): void
    {
        $this->actingAs($this->recepcion());

        $this->get('/gestion-estudios')->assertForbidden();
        $this->get('/usuarios')->assertForbidden();
        $this->get('/captura-rapida')->assertForbidden();
        $this->get('/gestion-medicos')->assertForbidden();
        $this->postJson('/api/estudios', ['nombre' => 'X'])->assertForbidden();
        $this->deleteJson('/api/pacientes/1')->assertForbidden();
        $this->postJson('/api/reportes/1/validar')->assertForbidden();

        // Sin ventas: su inicio es la captura
        $this->get('/dashboard')->assertRedirect(route('captura.resultados'));
        $this->get('/captura-resultados')->assertOk();
        $this->get('/cotizaciones')->assertOk();
        $this->get('/agenda')->assertOk();
    }

    public function test_el_registro_publico_esta_cerrado(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_un_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $usuario = User::factory()->create(['activo' => false]);

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_el_admin_crea_un_usuario_de_recepcion(): void
    {
        $this->actingAs($this->admin())->post('/usuarios', [
            'name' => 'Recepción Mañana',
            'email' => 'recepcion@biolab.test',
            'rol' => 'recepcion',
            'password' => 'secreta123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'recepcion@biolab.test', 'rol' => 'recepcion', 'activo' => true]);
    }

    public function test_recepcion_captura_queda_por_validar_y_con_precios_de_catalogo(): void
    {
        $usuario = $this->recepcion();

        $respuesta = $this->actingAs($usuario)->postJson('/api/reportes', $this->orden([
            'fecha_validacion' => null,
            'descuento' => 50,
            'estudios' => [['precio' => 1], ['precio' => 1]],
        ]))->assertOk();

        $reporte = Reporte::with('estudios')->find($respuesta->json('id'));

        $this->assertNull($reporte->fecha_validacion);
        $this->assertEquals(0, $reporte->descuento);
        $this->assertEquals(300, $reporte->estudios[0]->precio);
        $this->assertSame(ReporteEstudio::CAPTURADO, $reporte->estudios[0]->estado);
        $this->assertSame($usuario->id, $reporte->estudios[0]->capturado_por);
        $this->assertNotNull($reporte->estudios[0]->capturado_at);
        $this->assertSame(ReporteEstudio::PENDIENTE, $reporte->estudios[1]->estado);
        $this->assertSame($usuario->id, $reporte->creado_por);
    }

    public function test_recepcion_solo_descarga_y_envia_lo_validado(): void
    {
        $id = $this->actingAs($this->recepcion())->postJson('/api/reportes', $this->orden())->json('id');

        $this->get("/api/reportes/{$id}/pdf")->assertForbidden();
        $this->getJson("/api/reportes/{$id}/envio")->assertStatus(422);

        // El Q.F.B. valida
        $this->actingAs($this->admin())->postJson("/api/reportes/{$id}/validar")->assertOk()->assertJson(['validados' => 1]);

        $envio = $this->actingAs($this->recepcion())->getJson("/api/reportes/{$id}/envio")->assertOk();
        $this->assertStringContainsString('/r/', $envio->json('url'));
        $this->assertSame('9981234567', $envio->json('telefono'));

        $estudio = Reporte::find($id)->estudios()->where('incluir_en_reporte', true)->first();
        $this->assertSame(ReporteEstudio::ENTREGADO, $estudio->estado);
        $this->assertNotNull(Reporte::find($id)->fecha_validacion);
    }

    public function test_lo_que_captura_el_admin_queda_validado(): void
    {
        $id = $this->actingAs($this->admin())->postJson('/api/reportes', $this->orden())->json('id');

        $estudios = Reporte::find($id)->estudios;
        $this->assertSame(ReporteEstudio::VALIDADO, $estudios[0]->estado);
        $this->assertSame('QFB', $estudios[0]->valido);
        $this->assertSame(ReporteEstudio::PENDIENTE, $estudios[1]->estado);
    }

    public function test_si_recepcion_corrige_un_resultado_vuelve_a_pendiente_de_validar(): void
    {
        $id = $this->actingAs($this->admin())->postJson('/api/reportes', $this->orden())->json('id');
        $reporte = Reporte::with('estudios.resultados')->find($id);
        $quimica = $reporte->estudios[0];

        $payload = [
            'cliente' => ['nombre' => $reporte->nombre_cliente, 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Femenino'],
            'estudios' => $reporte->estudios->map(fn ($e) => [
                'id' => $e->id,
                'estudio_id' => $e->estudio_id,
                'incluir_en_reporte' => $e->incluir_en_reporte,
                'resultados' => $e->resultados->map(fn ($r) => [
                    'id' => $r->id,
                    'resultado' => $e->id === $quimica->id ? '99' : $r->resultado,
                ])->all(),
            ])->all(),
        ];

        $this->actingAs($this->recepcion())->putJson("/api/reportes/{$id}", $payload)->assertOk();

        $this->assertSame(ReporteEstudio::CAPTURADO, $quimica->fresh()->estado);
        $this->assertSame('', (string) $quimica->fresh()->valido);
    }

    public function test_recepcion_no_puede_quitar_un_estudio_validado(): void
    {
        $id = $this->actingAs($this->admin())->postJson('/api/reportes', $this->orden())->json('id');

        // Manda la orden sin estudios: los validados deben seguir ahí
        $this->actingAs($this->recepcion())->putJson("/api/reportes/{$id}", [
            'cliente' => ['nombre' => 'Analucía López Pérezgil', 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Femenino'],
            'estudios' => [],
        ])->assertOk();

        $this->assertSame(1, Reporte::find($id)->estudios()->where('estado', ReporteEstudio::VALIDADO)->count());
    }

    // ---------- T2: pendientes fuera del PDF y complementario ----------

    public function test_un_estudio_apagado_no_sale_en_el_pdf_y_se_puede_encender_despues(): void
    {
        $admin = $this->admin();
        $id = $this->actingAs($admin)->postJson('/api/reportes', $this->orden())->json('id');

        $reporte = Reporte::with(ReportePdf::RELACIONES)->find($id);
        $estado = ReportePdf::filtrar($reporte);
        $this->assertSame(['Química sanguínea'], $reporte->estudios->pluck('estudio.nombre')->all());
        $this->assertFalse($estado['preliminar']);

        // Llega el urocultivo: se captura y se enciende
        $urocultivo = Reporte::find($id)->estudios()->where('estudio_id', $this->urocultivo->id)->first();
        $this->patchJson("/api/reportes/{$id}/estudios/{$urocultivo->id}/incluir", ['incluir_en_reporte' => true])->assertOk();

        $reporte = Reporte::with(ReportePdf::RELACIONES)->find($id);
        ReportePdf::filtrar($reporte);
        $this->assertCount(2, $reporte->estudios);

        // Complementario: solo el urocultivo, mismo folio
        $reporte = Reporte::with(ReportePdf::RELACIONES)->find($id);
        $estado = ReportePdf::filtrar($reporte, [$urocultivo->id]);
        $this->assertTrue($estado['complementario']);
        $this->assertSame(['Urocultivo'], $reporte->estudios->pluck('estudio.nombre')->all());

        $this->get("/api/reportes/{$id}/pdf-preview?estudios={$urocultivo->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    // ---------- T1: pacientes, duplicados e historial ----------

    public function test_no_se_duplica_un_paciente_sin_confirmacion(): void
    {
        $this->actingAs($this->recepcion());
        $datos = ['nombre' => 'Josefo Pérezquinto', 'fecha_nacimiento' => '1985-05-05', 'telefono' => '9980000000'];

        $this->postJson('/api/pacientes', $datos)->assertCreated();
        $this->postJson('/api/pacientes', ['nombre' => 'JOSEFO  PEREZQUINTO', 'fecha_nacimiento' => '1985-05-05'])
            ->assertStatus(409)
            ->assertJsonPath('duplicado.telefono', '9980000000');
        $this->postJson('/api/pacientes', ['nombre' => 'JOSEFO PEREZQUINTO', 'fecha_nacimiento' => '1985-05-05', 'confirmar_duplicado' => true])
            ->assertCreated();
    }

    public function test_busca_pacientes_sin_acentos_y_en_cualquier_orden(): void
    {
        Paciente::create(['nombre' => 'Xiomara Lára Quintal', 'fecha_nacimiento' => '2003-09-06']);

        $this->actingAs($this->recepcion())->getJson('/api/pacientes/buscar?q=lar xio')
            ->assertOk()
            ->assertJsonPath('0.nombre', 'Xiomara Lára Quintal');
    }

    public function test_cada_orden_se_liga_al_paciente_y_muestra_el_resultado_anterior(): void
    {
        $admin = $this->admin();
        $pacientesAntes = Paciente::count();
        $primera = $this->actingAs($admin)->postJson('/api/reportes', $this->orden([
            'toma_muestra' => '2026-10-12T08:00',
        ]))->json('id');

        // Misma persona escrita distinto: no se crea otro paciente
        $segunda = $this->postJson('/api/reportes', $this->orden([
            'cliente' => ['nombre' => 'ANALUCIA LOPEZ  PEREZGIL'],
        ]))->json('id');

        $pacienteId = Reporte::find($primera)->paciente_id;
        $this->assertNotNull($pacienteId);
        $this->assertSame($pacienteId, Reporte::find($segunda)->paciente_id);
        $this->assertSame($pacientesAntes + 1, Paciente::count());

        $this->getJson("/api/pacientes/{$pacienteId}/resultados-anteriores?excluir_reporte={$segunda}")
            ->assertOk()
            ->assertJsonPath('0.resultado', '126.0')
            ->assertJsonPath('0.folio', Reporte::find($primera)->folio);

        $this->get("/pacientes/{$pacienteId}/historial")->assertOk();
    }

    public function test_el_comando_liga_ordenes_viejas_con_su_paciente(): void
    {
        $pacientesAntes = Paciente::count();
        $base = ['fecha_nacimiento' => '1970-02-02', 'sexo' => 'Masculino', 'toma_muestra' => now(), 'fecha_reporte' => now(), 'fecha_validacion' => now()];
        Reporte::create([...$base, 'folio' => 'RPT-9001', 'nombre_cliente' => 'Pedrito Gómezmayor']);
        Reporte::create([...$base, 'folio' => 'RPT-9002', 'nombre_cliente' => 'PEDRITO GOMEZMAYOR']);

        $this->artisan('pacientes:vincular-reportes --aplicar')->assertSuccessful();

        $this->assertSame($pacientesAntes + 1, Paciente::count());
        $this->assertSame(0, Reporte::whereIn('folio', ['RPT-9001', 'RPT-9002'])->whereNull('paciente_id')->count());
    }
}
