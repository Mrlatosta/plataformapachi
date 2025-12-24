<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    reportes: Object,
    filters: Object
});

const search = ref(props.filters.search || '');
const fechaDesde = ref(props.filters.fecha_desde || '');
const fechaHasta = ref(props.filters.fecha_hasta || '');

const filterReportes = () => {
    router.get('/reportes', {
        search: search.value,
        fecha_desde: fechaDesde.value,
        fecha_hasta: fechaHasta.value
    }, {
        preserveState: true,
        replace: true
    });
};

const clearFilters = () => {
    search.value = '';
    fechaDesde.value = '';
    fechaHasta.value = '';
    filterReportes();
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(value);
};
</script>

<template>
    <Head title="Lista de Reportes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Lista de Reportes
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Filtros de Búsqueda</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Búsqueda -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Buscar</label>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Folio, nombre o email..."
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    @keyup.enter="filterReportes"
                                />
                            </div>

                            <!-- Fecha Desde -->
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                                <input
                                    v-model="fechaDesde"
                                    type="date"
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <!-- Fecha Hasta -->
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                                <input
                                    v-model="fechaHasta"
                                    type="date"
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mt-4">
                            <button
                                @click="filterReportes"
                                class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center"
                            >
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Buscar
                            </button>
                            <button
                                @click="clearFilters"
                                class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition-colors flex items-center justify-center"
                            >
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Reportes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <!-- Vista de tarjetas para móvil -->
                        <div class="block lg:hidden space-y-4">
                            <div v-for="reporte in reportes.data" :key="reporte.id" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ reporte.folio }}
                                    </span>
                                    <span class="px-2 py-1 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        {{ reporte.total_estudios }} estudios
                                    </span>
                                </div>
                                
                                <div class="space-y-2 mb-4">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ reporte.nombre_cliente }}</p>
                                        <p class="text-xs text-gray-500">{{ reporte.email || 'Sin email' }}</p>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-600">{{ reporte.edad }} años - {{ reporte.sexo }}</span>
                                        <span class="font-semibold text-gray-900">{{ formatCurrency(reporte.total_precio) }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ formatDate(reporte.created_at) }}</p>
                                </div>

                                <Link
                                    :href="route('reportes.show', reporte.id)"
                                    class="w-full inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver Detalles
                                </Link>
                            </div>
                        </div>

                        <!-- Vista de tabla para desktop -->
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Folio</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paciente</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Edad/Sexo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estudios</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="reporte in reportes.data" :key="reporte.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ reporte.folio }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ reporte.nombre_cliente }}</div>
                                            <div class="text-sm text-gray-500">{{ reporte.email || 'Sin email' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ reporte.edad }} años</div>
                                            <div class="text-sm text-gray-500">{{ reporte.sexo }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ reporte.total_estudios }} estudios
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ formatCurrency(reporte.total_precio) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(reporte.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <Link
                                                :href="route('reportes.show', reporte.id)"
                                                class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                            >
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Ver Detalles
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación CORREGIDA SIN WARNING -->
                        <div v-if="reportes.links.length > 3" class="mt-6 flex flex-col gap-4">
                            <div class="text-xs sm:text-sm text-gray-700 text-center sm:text-left">
                                Mostrando {{ reportes.from }} a {{ reportes.to }} de {{ reportes.total }} resultados
                            </div>
                            <div class="flex flex-wrap gap-1 sm:gap-2 justify-center">
                                <template v-for="(link, index) in reportes.links" :key="index">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            'px-2 sm:px-4 py-1 sm:py-2 rounded-lg transition-colors text-xs sm:text-sm',
                                            link.active
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                        ]"
                                    >
                                        <span v-html="link.label"></span>
                                    </Link>
                                    <span
                                        v-else
                                        :class="[
                                            'px-2 sm:px-4 py-1 sm:py-2 rounded-lg text-xs sm:text-sm opacity-50 cursor-not-allowed',
                                            'bg-gray-200 text-gray-700'
                                        ]"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
