<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EstudioController;
use App\Http\Controllers\CapturaResultadosController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\MedicoPortalController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\GestionLandingController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\JornadaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ComisionController;


Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Landing pública para pacientes (vistas en resources/views/landing, assets en public/landing).
// La raíz "/" sigue mostrando Home.vue.
Route::get('/inicio', [LandingController::class, 'inicio'])->name('inicio');
Route::get('/novedades', [LandingController::class, 'novedades'])->name('novedades.index');
Route::get('/novedades/{slug}', [LandingController::class, 'articulo'])
    ->where('slug', '[a-z0-9-]+')
    ->name('novedades.show');

// ===== Panel interno =====
// T6: Administrador ve todo. Recepción solo: agenda, cotizaciones, pacientes,
// captura de resultados y lista de reportes (descarga/envío de validados).
// Lo marcado con rol:admin devuelve 403 aunque se llame la URL directo.

Route::middleware(['auth', 'verified'])->group(function () {
    // Recepción no ve ventas: DashboardController la manda a Captura
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/agenda', [CitaController::class, 'page'])->name('agenda');
    Route::get('/captura-resultados', [CapturaResultadosController::class, 'page'])->name('captura.resultados');
    Route::get('/cotizaciones', fn () => Inertia::render('Cotizaciones'))->name('cotizaciones');
    Route::get('/pacientes', [PacienteController::class, 'page'])->name('pacientes');
    Route::get('/pacientes/{id}/historial', [PacienteController::class, 'historial'])->whereNumber('id')->name('pacientes.historial');
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/{id}', [ReporteController::class, 'show'])->name('reportes.show');

    Route::middleware('rol:admin')->group(function () {
        Route::get('/gestion-landing', [GestionLandingController::class, 'page'])->name('gestion.landing');
        Route::get('/gestion-estudios', fn () => Inertia::render('GestionEstudios'))->name('gestion.estudios');
        Route::get('/gestion-medicos', [MedicoController::class, 'page'])->name('gestion.medicos');

        // T12: comisiones por medico que refiere
        Route::get('/comisiones', [ComisionController::class, 'page'])->name('comisiones.index');
        Route::get('/comisiones/exportar/excel', [ComisionController::class, 'exportarExcel'])->name('comisiones.excel');
        Route::get('/comisiones/exportar/pdf', [ComisionController::class, 'exportarPdf'])->name('comisiones.pdf');
        Route::get('/usuarios', [UsuarioController::class, 'page'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');

        // Captura rápida por jornadas (antidoping en empresas, campañas)
        Route::get('/captura-rapida', [JornadaController::class, 'page'])->name('captura.rapida');
        Route::get('/captura-rapida/{id}', [JornadaController::class, 'show'])->whereNumber('id')->name('captura.rapida.jornada');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// API interna del panel: requiere sesion. Contiene datos de pacientes y
// resultados, nunca debe quedar publica.
Route::middleware('auth')->group(function () {
    // --- Disponible para Administrador y Recepción ---
    Route::get('/api/estudios', [EstudioController::class, 'index']);
    Route::get('/api/estudios/{id}', [EstudioController::class, 'show']);
    Route::get('/api/medicos', [MedicoController::class, 'index']);
    // T12: alta rapida del medico desde la captura (sin duplicados)
    Route::post('/api/medicos/rapido', [MedicoController::class, 'rapido']);

    Route::get('/api/citas', [CitaController::class, 'index']);
    Route::post('/api/citas', [CitaController::class, 'store']);
    Route::put('/api/citas/{id}', [CitaController::class, 'update'])->whereNumber('id');
    Route::patch('/api/citas/{id}/estado', [CitaController::class, 'cambiarEstado'])->whereNumber('id');
    Route::delete('/api/citas/{id}', [CitaController::class, 'destroy'])->whereNumber('id');

    Route::post('/api/cotizaciones', [CotizacionController::class, 'store']);
    Route::get('/api/cotizaciones', [CotizacionController::class, 'index']);
    Route::get('/api/cotizaciones/{id}', [CotizacionController::class, 'show']);
    Route::put('/api/cotizaciones/{id}/estado', [CotizacionController::class, 'actualizarEstado']);
    Route::get('/api/cotizaciones/{id}/pdf', [CotizacionController::class, 'generarPDF']);
    Route::delete('/api/cotizaciones/{id}', [CotizacionController::class, 'destroy']);

    Route::get('/api/pacientes', [PacienteController::class, 'index']);
    Route::post('/api/pacientes', [PacienteController::class, 'store']);
    Route::get('/api/pacientes/buscar', [PacienteController::class, 'buscar']);
    Route::get('/api/pacientes/{id}/resultados-anteriores', [PacienteController::class, 'resultadosAnteriores'])->whereNumber('id');
    Route::get('/api/pacientes/{id}', [PacienteController::class, 'show'])->whereNumber('id');
    Route::put('/api/pacientes/{id}', [PacienteController::class, 'update'])->whereNumber('id');


    Route::post('/api/reportes', [ReporteController::class, 'store']);
    Route::post('/api/reportes/preview', [ReporteController::class, 'previsualizarBorrador']);
    Route::get('/api/reportes/folio/{folio}', [ReporteController::class, 'buscarPorFolio']);
    // PDF y envío: Recepción solo si todo lo impreso ya está validado (se revisa en el controlador)
    Route::get('/api/reportes/{id}/pdf', [ReporteController::class, 'generarPDF']);
    Route::get('/api/reportes/{id}/pdf-preview', [ReporteController::class, 'previsualizarPDF']);
    Route::get('/api/reportes/{id}/envio', [ReporteController::class, 'enlaceEnvio'])->whereNumber('id');
    Route::get('/api/reportes/{id}/orden', [ReporteController::class, 'generarOrdenTrabajo']);
    Route::put('/api/reportes/{id}', [ReporteController::class, 'actualizarReporte']);
    Route::patch('/api/reportes/{id}/estudios/{estudio}/incluir', [ReporteController::class, 'cambiarInclusion'])->whereNumber(['id', 'estudio']);

    // --- Solo Administrador ---
    Route::middleware('rol:admin')->group(function () {
        Route::post('/api/reportes/{id}/validar', [ReporteController::class, 'validar'])->whereNumber('id');

        Route::post('/api/estudios', [EstudioController::class, 'store']);
        Route::put('/api/estudios/{id}', [EstudioController::class, 'update']);
        Route::patch('/api/estudios/{id}/visibilidad', [EstudioController::class, 'cambiarVisibilidad']);
        Route::delete('/api/estudios/{id}', [EstudioController::class, 'destroy']);

        Route::delete('/api/pacientes/{id}', [PacienteController::class, 'destroy'])->whereNumber('id');

        Route::post('/api/medicos', [MedicoController::class, 'store']);
        Route::put('/api/medicos/{id}', [MedicoController::class, 'update']);
        Route::delete('/api/medicos/{id}', [MedicoController::class, 'destroy']);

        Route::get('/api/comisiones', [ComisionController::class, 'index']);
        Route::post('/api/comisiones/pagar', [ComisionController::class, 'pagar']);
        Route::post('/api/comisiones/{id}/revertir', [ComisionController::class, 'revertir'])->whereNumber('id');

        // Gestión Landing. Se usa POST también para editar porque PHP no lee archivos en PUT.
        Route::get('/api/landing/promociones', [GestionLandingController::class, 'promociones']);
        Route::post('/api/landing/promociones', [GestionLandingController::class, 'guardarPromocion']);
        Route::post('/api/landing/promociones/{id}', [GestionLandingController::class, 'guardarPromocion'])->whereNumber('id');
        Route::patch('/api/landing/promociones/{id}/estado', [GestionLandingController::class, 'cambiarEstadoPromocion'])->whereNumber('id');
        Route::delete('/api/landing/promociones/{id}', [GestionLandingController::class, 'eliminarPromocion'])->whereNumber('id');

        Route::get('/api/landing/publicaciones', [GestionLandingController::class, 'publicaciones']);
        Route::post('/api/landing/publicaciones', [GestionLandingController::class, 'guardarPublicacion']);
        Route::post('/api/landing/publicaciones/{id}', [GestionLandingController::class, 'guardarPublicacion'])->whereNumber('id');
        Route::patch('/api/landing/publicaciones/{id}/estado', [GestionLandingController::class, 'cambiarEstadoPublicacion'])->whereNumber('id');
        Route::delete('/api/landing/publicaciones/{id}', [GestionLandingController::class, 'eliminarPublicacion'])->whereNumber('id');

        // Captura rápida por jornadas
        Route::post('/api/jornadas', [JornadaController::class, 'store']);
        Route::put('/api/jornadas/{id}', [JornadaController::class, 'update'])->whereNumber('id');
        Route::delete('/api/jornadas/{id}', [JornadaController::class, 'destroy'])->whereNumber('id');
        Route::post('/api/jornadas/{id}/participantes', [JornadaController::class, 'agregarParticipantes'])->whereNumber('id');
        Route::patch('/api/jornadas/{id}/participantes/{participante}', [JornadaController::class, 'actualizarParticipante'])->whereNumber(['id', 'participante']);
        Route::delete('/api/jornadas/{id}/participantes/{participante}', [JornadaController::class, 'eliminarParticipante'])->whereNumber(['id', 'participante']);
        Route::post('/api/jornadas/{id}/reportes', [JornadaController::class, 'generarReportes'])->whereNumber('id');
        Route::get('/api/jornadas/{id}/csv', [JornadaController::class, 'exportarCsv'])->whereNumber('id');
    });
});

// Portal médicos (sin autenticación de admin)
Route::get('/medico/login', [MedicoPortalController::class, 'showLogin'])->name('medico.login');
Route::post('/medico/login', [MedicoPortalController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('medico.login.post');
Route::post('/medico/logout', [MedicoPortalController::class, 'logout'])->name('medico.logout');
Route::middleware(['medico.auth'])->group(function () {
    Route::get('/medico/reportes', [MedicoPortalController::class, 'reportes'])->name('medico.reportes');
    Route::get('/medico/reportes/{id}/pdf', [ReporteController::class, 'pdfMedico'])->name('medico.reportes.pdf');
});

// Verificación pública por QR: el token aleatorio de cada reporte es la única llave.
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/r/{token}', [ReporteController::class, 'verificar'])
        ->where('token', '[A-Za-z0-9]{40}')
        ->name('reportes.verificar');
    Route::get('/r/{token}/pdf', [ReporteController::class, 'descargarVerificado'])
        ->where('token', '[A-Za-z0-9]{40}')
        ->name('reportes.verificar.pdf');
});


// PDFs de prueba con datos simulados: solo disponibles en desarrollo local
if (app()->environment('local')) {

Route::get('/prueba-pdf', function () {
    // Simular reporte para pruebas sin consultar BD
    $reporte = (object) [
        'folio' => 'PRUEBA-001',
        'toma_muestra' => now(),
        'fecha_reporte' => now(),
        'fecha_validacion' => now(),
        'nombre_cliente' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'fecha_nacimiento' => '1990-05-10',
        'edad' => 34,
        'sexo' => 'masculino',
        'medico_solicitante' => 'Dr. Ruiz Martínez',
        'estudios' => collect(range(1, 12))->map(function ($i) {
            return (object) [
                'elaboro' => 'QFB Angel Augusto Pérez Arias',
                'valido' => 'QFB Angel Augusto Pérez Arias',
                'tipo_muestra' => 'Sangre',
                'metodo' => 'Colorimetría',
                'observaciones' => null,
                'estudio' => (object) ['nombre' => 'PRUEBA CLÍNICA #' . $i],
                'resultados' => collect(range(1, 25))->map(function ($j) {
                    $fueraRango = rand(0, 10) > 7;
                    return (object) [
                        'resultado' => $fueraRango ? rand(200, 300) : rand(70, 110),
                        'fuera_rango' => $fueraRango,
                        'examen' => (object) [
                            'nombre_examen' => 'Parámetro ' . $j,
                            'unidad' => 'mg/dL',
                            'valor_referencia' => '70 - 110',
                        ],
                    ];
                })->toArray(),
            ];
        })->toArray(),
    ];

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reporte_biolab', compact('reporte'))
        ->setPaper('letter', 'portrait'); // puedes cambiar a 'a4' si prefieres

    return $pdf->stream('reporte_prueba.pdf');
});

Route::get('/prueba-odt', function () {
    // Simular reporte de orden de trabajo para pruebas
    $estudiosPrueba = collect(range(1, 5))->map(function ($i) {
        return (object) [
            'elaboro' => 'QFB Ángel Augusto Pérez Arias',
            'valido' => 'QFB Ángel Augusto Pérez Arias',
            'tipo_muestra' => 'Suero',
            'metodo' => 'ELISA',
            'precio' => rand(100, 350),
            'estudio' => (object) ['nombre' => 'ESTUDIO CLÍNICO #' . $i],
        ];
    });

    $subtotalPrueba = round((float) $estudiosPrueba->sum('precio'), 2);
    $porcentajeIvaPrueba = (float) config('facturacion.iva');
    $montoIvaPrueba = round($subtotalPrueba * ($porcentajeIvaPrueba / 100), 2);

    $reporte = (object) [
        'folio' => 'ODT-PRUEBA-001',
        'id' => 999,
        'toma_muestra' => now(),
        'fecha_reporte' => now(),
        'fecha_validacion' => now(),
        'nombre_cliente' => 'Carlos Ramírez Torres',
        'email' => 'carlos@example.com',
        'fecha_nacimiento' => '1988-02-15',
        'edad' => 37,
        'sexo' => 'masculino',
        'medico_solicitante' => 'Dr. José Luis García',
        'estudios' => $estudiosPrueba,
        'aplica_iva' => true,
        'subtotal' => $subtotalPrueba,
        'porcentaje_iva' => $porcentajeIvaPrueba,
        'monto_iva' => $montoIvaPrueba,
        'total' => round($subtotalPrueba + $montoIvaPrueba, 2),
    ];

    // Cargar vista de la orden de trabajo (ajusta el nombre si lo cambiaste)
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.orden_trabajo', compact('reporte'))
        ->setPaper('a4', 'portrait');

    return $pdf->stream('orden-trabajo-prueba.pdf');
});

}



require __DIR__.'/auth.php';
