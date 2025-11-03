<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EstudioController;
use App\Http\Controllers\CapturaResultadosController;
use App\Http\Controllers\ReporteController;


Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/gestion-estudios', function () {
    return Inertia::render('GestionEstudios');
})->middleware(['auth', 'verified'])->name('gestion.estudios');

Route::get('/captura-resultados', function () {
    return Inertia::render('CapturaResultados');
})->middleware(['auth', 'verified'])->name('captura.resultados');


Route::get('/reimpresion-resultados', function () {
    return Inertia::render('ReimpresionResultados');
})->middleware(['auth', 'verified'])->name('reimpresion.resultados');


Route::get('/api/estudios', [EstudioController::class, 'index']);
Route::post('/api/estudios', [EstudioController::class, 'store']);
Route::get('/api/estudios/{id}', [EstudioController::class, 'show']);
Route::put('/api/estudios/{id}', [EstudioController::class, 'update']);


Route::get('/api/estudios-con-examenes', [CapturaResultadosController::class, 'index']);
Route::post('/api/resultados', [CapturaResultadosController::class, 'store']);
Route::get('/api/resultados/pdf', [CapturaResultadosController::class, 'generarPDF']);


Route::post('/api/reportes', [ReporteController::class, 'store']);

Route::post('/guardar-reporte', [ReporteController::class, 'guardar'])->name('reporte.guardar');

Route::get('/api/reportes/{id}/pdf', [ReporteController::class, 'generarPDF']);

Route::get('api/reportes/folio/{folio}', [ReporteController::class, 'buscarPorFolio']);

Route::put('api/reportes/{id}', [ReporteController::class, 'actualizarReporte']);

Route::get('api/reportes/{id}/orden', [ReporteController::class, 'generarOrdenTrabajo']);


use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Reporte;


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

    $pdf = Pdf::loadView('pdf.reporte_biolab', compact('reporte'))
        ->setPaper('letter', 'portrait'); // puedes cambiar a 'a4' si prefieres

    return $pdf->stream('reporte_prueba.pdf');
});

Route::get('/prueba-odt', function () {
    // Simular reporte de orden de trabajo para pruebas
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
        'estudios' => collect(range(1, 5))->map(function ($i) {
            return (object) [
                'elaboro' => 'QFB Ángel Augusto Pérez Arias',
                'valido' => 'QFB Ángel Augusto Pérez Arias',
                'tipo_muestra' => 'Suero',
                'metodo' => 'ELISA',
                'precio' => rand(100, 350),
                'estudio' => (object) ['nombre' => 'ESTUDIO CLÍNICO #' . $i],
            ];
        }),
    ];

    // Cargar vista de la orden de trabajo (ajusta el nombre si lo cambiaste)
    $pdf = Pdf::loadView('pdf.orden_trabajo', compact('reporte'))
        ->setPaper('a4', 'portrait');

    return $pdf->stream('orden-trabajo-prueba.pdf');
});



require __DIR__.'/auth.php';
