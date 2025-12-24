<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    reporte: Object
});

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(value);
};

const totalPrecio = props.reporte.estudios.reduce((sum, estudio) => sum + Number(estudio.precio), 0);

const descargarPDF = () => {
    window.open(`/api/reportes/${props.reporte.id}/pdf`, '_blank');
};

const descargarOrden = () => {
    window.open(`/api/reportes/${props.reporte.id}/orden`, '_blank');
};
</script>

<template>
    <Head :title="`Reporte ${reporte.folio}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalles del Reporte - {{ reporte.folio }}
                </h2>
                <div class="flex gap-2">
                    <button
                        @click="descargarPDF"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Descargar PDF
                    </button>
                    <button
                        @click="descargarOrden"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Orden de Trabajo
                    </button>
                    <Link
                        :href="route('reportes.index')"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Información del Paciente -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Información del Paciente
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Nombre Completo</label>
                                <p class="mt-1 text-sm text-gray-900 font-semibold">{{ reporte.nombre_cliente }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="mt-1 text-sm text-gray-900">{{ reporte.email || 'No proporcionado' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Edad</label>
                                <p class="mt-1 text-sm text-gray-900">{{ reporte.edad }} años</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Sexo</label>
                                <p class="mt-1 text-sm text-gray-900">{{ reporte.sexo }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Fecha de Nacimiento</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(reporte.fecha_nacimiento) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Médico Solicitante</label>
                                <p class="mt-1 text-sm text-gray-900">{{ reporte.medico_solicitante || 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Reporte -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Información del Reporte
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Folio</label>
                                <p class="mt-1 text-sm font-bold text-blue-600">{{ reporte.folio }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Toma de Muestra</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(reporte.toma_muestra) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Fecha de Reporte</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(reporte.fecha_reporte) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Fecha de Validación</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(reporte.fecha_validacion) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estudios y Resultados -->
                <div v-for="estudio in reporte.estudios" :key="estudio.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                {{ estudio.nombre }}
                            </h3>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                {{ formatCurrency(estudio.precio) }}
                            </span>
                        </div>

                        <!-- Información del estudio -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Tipo de Muestra</label>
                                <p class="mt-1 text-sm text-gray-900">{{ estudio.tipo_muestra || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Método</label>
                                <p class="mt-1 text-sm text-gray-900">{{ estudio.metodo || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Elaboró</label>
                                <p class="mt-1 text-sm text-gray-900">{{ estudio.elaboro || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Validó</label>
                                <p class="mt-1 text-sm text-gray-900">{{ estudio.valido || 'N/A' }}</p>
                            </div>
                        </div>

                        <!-- Leyenda -->
                        <div v-if="estudio.leyenda" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                            <p class="text-sm text-gray-700">
                                <span class="font-semibold">Nota:</span> {{ estudio.leyenda }}
                            </p>
                        </div>

                        <!-- Tabla de Resultados -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Examen</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resultado</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidad</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor de Referencia</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="resultado in estudio.resultados" :key="resultado.id" :class="resultado.fuera_rango ? 'bg-red-50' : ''">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ resultado.nombre_examen }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold" :class="resultado.fuera_rango ? 'text-red-600' : 'text-gray-900'">
                                            {{ resultado.resultado }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ resultado.unidad || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ resultado.valor_referencia || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="resultado.fuera_rango" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Fuera de rango
                                            </span>
                                            <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Normal
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Resumen Total -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-700 overflow-hidden shadow-lg sm:rounded-lg">
                    <div class="p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold mb-2">Total del Reporte</h3>
                                <p class="text-sm opacity-90">{{ reporte.estudios.length }} estudios realizados</p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold">{{ formatCurrency(totalPrecio) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
