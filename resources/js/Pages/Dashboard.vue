<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

// Props que recibirás del controlador
const props = defineProps({
    estadisticas: Object,
    reportesPorMes: Array,
    ingresosPorMes: Array,
    estudiosPopulares: Array,
    reportesPorGenero: Object,
    reportesPorDia: Array
});

// Variables reactivas para las gráficas
const chartIngresos = ref(null);
const chartEstudios = ref(null);
const chartReportes = ref(null);
const chartGenero = ref(null);
const chartDias = ref(null);

// Función para formatear moneda
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(value);
};

// Crear gráficas cuando el componente se monta
onMounted(() => {
    createIngresosChart();
    createEstudiosChart();
    createReportesChart();
    createGeneroChart();
    createDiasChart();
});

// Gráfica de ingresos por mes
const createIngresosChart = () => {
    const ctx = chartIngresos.value?.getContext('2d');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.ingresosPorMes?.map(item => item.mes) || [],
            datasets: [{
                label: 'Ingresos',
                data: props.ingresosPorMes?.map(item => item.total) || [],
                borderColor: '#08cc71',
                backgroundColor: 'rgba(8, 204, 113, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#08cc71',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e40af',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#08cc71',
                    borderWidth: 2,
                    callbacks: {
                        label: function(context) {
                            return 'Ingresos: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
};

// Gráfica de estudios más vendidos
const createEstudiosChart = () => {
    const ctx = chartEstudios.value?.getContext('2d');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.estudiosPopulares?.map(item => item.nombre.substring(0, 30) + '...') || [],
            datasets: [{
                label: 'Cantidad',
                data: props.estudiosPopulares?.map(item => item.total) || [],
                backgroundColor: [
                    '#08cc71',
                    '#1e40af',
                    '#06b562',
                    '#1e3a8a',
                    '#0ea160',
                    '#1d4ed8',
                    '#059669',
                    '#2563eb',
                    '#10b981',
                    '#3b82f6'
                ],
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e40af',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#08cc71',
                    borderWidth: 2
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
};

// Gráfica de reportes por mes
const createReportesChart = () => {
    const ctx = chartReportes.value?.getContext('2d');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.reportesPorMes?.map(item => item.mes) || [],
            datasets: [{
                label: 'Reportes',
                data: props.reportesPorMes?.map(item => item.total) || [],
                backgroundColor: '#1e40af',
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e40af',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#08cc71',
                    borderWidth: 2
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
};

// Gráfica de distribución por género
const createGeneroChart = () => {
    const ctx = chartGenero.value?.getContext('2d');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Masculino', 'Femenino', 'No definir'],
            datasets: [{
                data: [
                    props.reportesPorGenero?.masculino || 0,
                    props.reportesPorGenero?.femenino || 0,
                    props.reportesPorGenero?.no_definir || 0
                ],
                backgroundColor: ['#1e40af', '#08cc71', '#fbbf24'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: '#1e40af',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#08cc71',
                    borderWidth: 2
                }
            }
        }
    });
};

// Gráfica de reportes por día de la semana
const createDiasChart = () => {
    const ctx = chartDias.value?.getContext('2d');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: props.reportesPorDia?.map(item => item.dia) || [],
            datasets: [{
                label: 'Reportes',
                data: props.reportesPorDia?.map(item => item.total) || [],
                borderColor: '#08cc71',
                backgroundColor: 'rgba(8, 204, 113, 0.2)',
                pointBackgroundColor: '#08cc71',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#08cc71',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e40af',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#08cc71',
                    borderWidth: 2
                }
            },
            scales: {
                r: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    angleLines: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        }
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                📊 Dashboard de Análisis
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <!-- KPIs Cards -->
            <div class="grid grid-cols-1 gap-6 mb-6 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Reportes -->
                <div class="overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 shadow-lg sm:rounded-lg transform hover:scale-105 transition-transform duration-200">
                    <div class="p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-100">Total Reportes</p>
                                <p class="mt-2 text-3xl font-bold">{{ estadisticas?.total_reportes || 0 }}</p>
                                <p class="mt-1 text-xs text-blue-100">Este mes</p>
                            </div>
                            <div class="p-3 bg-white bg-opacity-30 rounded-full">
                                <svg class="w-8 h-8 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ingresos del Mes -->
                <div class="overflow-hidden bg-gradient-to-br from-green-500 to-green-700 shadow-lg sm:rounded-lg transform hover:scale-105 transition-transform duration-200">
                    <div class="p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-100">Ingresos del Mes</p>
                                <p class="mt-2 text-3xl font-bold">{{ formatCurrency(estadisticas?.ingresos_mes || 0) }}</p>
                                <p class="mt-1 text-xs text-green-100">Ventas totales</p>
                            </div>
                            <div class="p-3 bg-white bg-opacity-30 rounded-full">
                                <svg class="w-8 h-8 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Promedio -->
                <div class="overflow-hidden bg-gradient-to-br from-purple-500 to-purple-700 shadow-lg sm:rounded-lg transform hover:scale-105 transition-transform duration-200">
                    <div class="p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-100">Ticket Promedio</p>
                                <p class="mt-2 text-3xl font-bold">{{ formatCurrency(estadisticas?.ticket_promedio || 0) }}</p>
                                <p class="mt-1 text-xs text-purple-100">Por reporte</p>
                            </div>
                            <div class="p-3 bg-white bg-opacity-30 rounded-full">
                                <svg class="w-8 h-8 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Estudios -->
                <div class="overflow-hidden bg-gradient-to-br from-yellow-500 to-yellow-700 shadow-lg sm:rounded-lg transform hover:scale-105 transition-transform duration-200">
                    <div class="p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-yellow-100">Estudios Realizados</p>
                                <p class="mt-2 text-3xl font-bold">{{ estadisticas?.total_estudios || 0 }}</p>
                                <p class="mt-1 text-xs text-yellow-100">Este mes</p>
                            </div>
                            <div class="p-3 bg-white bg-opacity-30 rounded-full">
                                <svg class="w-8 h-8 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


                <!-- Gráficas -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Ingresos por Mes -->
                    <div class="overflow-hidden bg-white shadow-lg sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <span class="text-green-600 mr-2">💰</span>
                                Ingresos por Mes
                            </h3>
                            <div class="h-80">
                                <canvas ref="chartIngresos"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Reportes por Mes -->
                    <div class="overflow-hidden bg-white shadow-lg sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <span class="text-blue-600 mr-2">📊</span>
                                Reportes por Mes
                            </h3>
                            <div class="h-80">
                                <canvas ref="chartReportes"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Estudios Más Vendidos -->
                    <div class="overflow-hidden bg-white shadow-lg sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <span class="text-purple-600 mr-2">🔬</span>
                                Top 10 Estudios Más Vendidos
                            </h3>
                            <div class="h-80">
                                <canvas ref="chartEstudios"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Distribución por Género -->
                    <div class="overflow-hidden bg-white shadow-lg sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <span class="text-pink-600 mr-2">👥</span>
                                Distribución por Género
                            </h3>
                            <div class="h-80 flex items-center justify-center">
                                <canvas ref="chartGenero"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Reportes por Día de la Semana -->
                    <div class="overflow-hidden bg-white shadow-lg sm:rounded-lg lg:col-span-2">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <span class="text-orange-600 mr-2">📅</span>
                                Reportes por Día de la Semana
                            </h3>
                            <div class="h-80">
                                <canvas ref="chartDias"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Link a captura de resultados -->
                <div class="mt-6 overflow-hidden bg-gradient-to-r from-blue-50 to-green-50 border-2 border-blue-200 shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">¿Listo para crear un nuevo reporte?</h3>
                                <p class="mt-1 text-sm text-gray-600">Accede a la captura de resultados y genera reportes profesionales.</p>
                            </div>
                            <a :href="route('captura.resultados')" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Capturar Resultados
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Animaciones suaves */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.overflow-hidden {
    animation: fadeIn 0.5s ease-out;
}
</style>