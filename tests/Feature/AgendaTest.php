<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Estudio;
use App\Models\Examen;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function datosCita(array $cambios = []): array
    {
        return [
            'nombre' => 'María López',
            'telefono' => '998 123 4567',
            'fecha' => '2026-10-05',
            'hora_inicio' => '09:30',
            'duracion_minutos' => 30,
            'modalidad' => 'laboratorio',
            'origen' => 'whatsapp',
            'estudios' => [],
            ...$cambios,
        ];
    }

    public function test_invitado_no_accede_a_la_agenda(): void
    {
        $this->get('/agenda')->assertRedirect('/login');
        $this->getJson('/api/citas?desde=2026-10-01&hasta=2026-10-07')->assertUnauthorized();
        $this->postJson('/api/citas', $this->datosCita())->assertUnauthorized();
    }

    public function test_admin_abre_la_agenda(): void
    {
        Estudio::create(['nombre' => 'Glucosa', 'precio' => 35]);

        $this->actingAs($this->admin())
            ->get('/agenda')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->component('Agenda')
                ->has('catalogo', 1)
                ->has('config.estados')
                ->where('config.horario.6', ['08:00', '14:00']));
    }

    public function test_agenda_cita_con_paciente_nuevo_y_estudios(): void
    {
        $bhc = Estudio::create(['nombre' => 'Biometría hemática', 'precio' => 150, 'costo' => 55]);
        $qs = Estudio::create(['nombre' => 'Química sanguínea', 'precio' => 360]);
        $admin = $this->admin();

        $respuesta = $this->actingAs($admin)->postJson('/api/citas', $this->datosCita([
            'guardar_paciente' => true,
            'modalidad' => 'domicilio',
            'direccion' => 'SM 20, Mz 3, Lt 7',
            'estudios' => [$bhc->id, $qs->id],
        ]));

        $respuesta->assertCreated()
            ->assertJsonPath('cita.hora_inicio', '09:30')
            ->assertJsonPath('cita.hora_fin', '10:00')
            ->assertJsonPath('cita.estado', 'programada')
            ->assertJsonPath('cita.total_estimado', '510.00')
            ->assertJsonCount(2, 'cita.estudios')
            ->assertJsonMissingPath('cita.estudios.0.costo');

        $cita = Cita::sole();
        $this->assertSame($admin->id, $cita->creado_por);
        $paciente = Paciente::sole();
        $this->assertSame($paciente->id, $cita->paciente_id);
        $this->assertSame('SM 20, Mz 3, Lt 7', $paciente->direccion);
    }

    public function test_paciente_registrado_no_se_duplica(): void
    {
        $paciente = Paciente::create(['nombre' => 'Juan Pérez', 'telefono' => '9981112233']);

        $this->actingAs($this->admin())->postJson('/api/citas', $this->datosCita([
            'paciente_id' => $paciente->id,
            'nombre' => 'Juan Pérez',
            'guardar_paciente' => true,
        ]))->assertCreated();

        $this->assertSame(1, Paciente::count());
        $this->assertSame($paciente->id, Cita::sole()->paciente_id);
    }

    public function test_validaciones_explican_como_corregir(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/citas', $this->datosCita(['modalidad' => 'domicilio', 'direccion' => '', 'nombre' => '']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.direccion.0', 'Para una toma a domicilio escribe la dirección (calle, número, colonia y referencias).')
            ->assertJsonValidationErrors(['nombre']);

        $this->actingAs($admin)
            ->postJson('/api/citas', $this->datosCita(['hora_inicio' => '23:30', 'duracion_minutos' => 60]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duracion_minutos']);

        $this->actingAs($admin)
            ->postJson('/api/citas', $this->datosCita(['hora_inicio' => '9 am']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hora_inicio']);

        $this->assertSame(0, Cita::count());
    }

    public function test_lista_por_rango_y_estado(): void
    {
        $admin = $this->admin();
        foreach (['2026-10-04', '2026-10-05', '2026-10-12'] as $fecha) {
            $this->actingAs($admin)->postJson('/api/citas', $this->datosCita(['fecha' => $fecha, 'nombre' => "Paciente $fecha"]))->assertCreated();
        }
        Cita::where('fecha', '2026-10-05')->update(['estado' => 'confirmada']);

        $this->actingAs($admin)->getJson('/api/citas?desde=2026-10-04&hasta=2026-10-10')
            ->assertOk()->assertJsonCount(2)
            ->assertJsonPath('0.fecha', '2026-10-04');

        $this->actingAs($admin)->getJson('/api/citas?desde=2026-10-01&hasta=2026-10-31&estado=confirmada')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.nombre', 'Paciente 2026-10-05');

        $this->actingAs($admin)->getJson('/api/citas?desde=2026-01-01&hasta=2026-12-31')
            ->assertUnprocessable()->assertJsonValidationErrors(['hasta']);
    }

    public function test_editar_conserva_precio_pactado_de_estudios_existentes(): void
    {
        $bhc = Estudio::create(['nombre' => 'Biometría hemática', 'precio' => 150]);
        $qs = Estudio::create(['nombre' => 'Química sanguínea', 'precio' => 360]);
        $ego = Estudio::create(['nombre' => 'Examen de orina', 'precio' => 100]);
        $admin = $this->admin();

        $id = $this->actingAs($admin)->postJson('/api/citas', $this->datosCita(['estudios' => [$bhc->id, $qs->id]]))
            ->assertCreated()->json('cita.id');

        // El catálogo sube de precio después de agendar
        $bhc->update(['precio' => 200]);

        $this->actingAs($admin)->putJson("/api/citas/{$id}", $this->datosCita([
            'estudios' => [$bhc->id, $ego->id],
            'estado' => 'confirmada',
            'hora_inicio' => '11:00',
        ]))->assertOk()
            ->assertJsonPath('cita.hora_fin', '11:30')
            ->assertJsonPath('cita.estado', 'confirmada')
            ->assertJsonPath('cita.total_estimado', '250.00');

        $precios = Cita::find($id)->estudios()->pluck('precio', 'estudio_id');
        $this->assertEquals(['150.00', '100.00'], [$precios[$bhc->id], $precios[$ego->id]]);
        $this->assertArrayNotHasKey($qs->id, $precios->all());
    }

    public function test_cambiar_estado_y_eliminar(): void
    {
        $admin = $this->admin();
        $id = $this->actingAs($admin)->postJson('/api/citas', $this->datosCita())->json('cita.id');

        $this->actingAs($admin)->patchJson("/api/citas/{$id}/estado", ['estado' => 'no_asistio'])
            ->assertOk()->assertJsonPath('estado', 'no_asistio');
        $this->actingAs($admin)->patchJson("/api/citas/{$id}/estado", ['estado' => 'perdida'])
            ->assertUnprocessable();

        $this->actingAs($admin)->deleteJson("/api/citas/{$id}")->assertOk();
        $this->assertSame(0, Cita::count());
    }

    public function test_atender_precarga_la_captura_de_resultados(): void
    {
        $estudio = Estudio::create(['nombre' => 'Glucosa', 'precio' => 35]);
        Examen::create(['estudio_id' => $estudio->id, 'nombre_examen' => 'Glucosa', 'unidad' => 'mg/dL', 'valor_referencia' => '70 - 100']);
        $paciente = Paciente::create(['nombre' => 'Ana Ruiz']);
        $admin = $this->admin();

        $id = $this->actingAs($admin)->postJson('/api/citas', $this->datosCita([
            'paciente_id' => $paciente->id,
            'nombre' => 'Ana Ruiz',
            'estudios' => [$estudio->id],
        ]))->json('cita.id');

        $this->actingAs($admin)
            ->get("/captura-resultados?cita_id={$id}")
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->component('CapturaResultados')
                ->where('cotizacionPrecargada.origen', 'Cita del 05/10/2026 a las 09:30')
                ->where('cotizacionPrecargada.paciente.id', $paciente->id)
                ->where('cotizacionPrecargada.estudios.0.estudio.nombre', 'Glucosa')
                ->has('cotizacionPrecargada.estudios.0.estudio.examenes', 1));

        $this->actingAs($admin)->get('/captura-resultados?cita_id=999999')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina->where('cotizacionPrecargada', null));
    }
}
