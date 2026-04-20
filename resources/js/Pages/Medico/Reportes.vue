<script setup>
import { Head, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    reportes: Array,
    medicoNombre: String,
});

const busqueda = ref('');

const reportesFiltrados = computed(() => {
    if (!busqueda.value) return props.reportes;
    const q = busqueda.value.toLowerCase();
    return props.reportes.filter(r =>
        r.folio.toLowerCase().includes(q) ||
        r.nombre_cliente.toLowerCase().includes(q)
    );
});

const cerrarSesion = () => {
    router.post(route('medico.logout'));
};

const formatFecha = (fecha) => {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleDateString('es-MX', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
};

const verPDF = (id) => {
    window.open(`/api/reportes/${id}/pdf-preview`, '_blank');
};
</script>

<template>
    <Head title="Mis Reportes" />

    <div class="min-h-screen bg-gray-50">

        <!-- Header -->
        <header class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 shadow-xl">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-green-400 to-blue-500 shadow">
                            <ApplicationLogo class="block h-8 w-auto fill-current text-gray-800" />
                        </div>
                        <div>
                            <span class="text-white font-bold text-lg">BIOLAB</span>
                            <span class="text-gray-400 text-sm ml-2">Portal Médicos</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="hidden sm:flex items-center space-x-2 text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-sm font-medium">{{ medicoNombre }}</span>
                        </div>
                        <button
                            @click="cerrarSesion"
                            class="flex items-center space-x-1.5 px-3 py-1.5 text-sm text-red-400 hover:text-red-300 hover:bg-red-900/20 rounded-lg transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Salir</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Stats card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Mis Reportes Asignados</h1>
                        <p class="text-gray-500 text-sm mt-0.5">Dr. {{ medicoNombre }} — {{ reportes.length }} reporte{{ reportes.length !== 1 ? 's' : '' }} en total</p>
                    </div>
                    <div class="flex items-center space-x-2 bg-blue-50 rounded-xl px-4 py-3 border border-blue-100">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="text-2xl font-bold text-blue-600">{{ reportes.length }}</span>
                    </div>
                </div>
            </div>

            <!-- Buscador -->
            <div class="relative mb-4">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    v-model="busqueda"
                    type="text"
                    placeholder="Buscar por folio o nombre del paciente..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm text-sm"
                />
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div v-if="reportesFiltrados.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400">
                    <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-lg font-medium">No hay reportes</p>
                    <p class="text-sm mt-1">{{ busqueda ? 'Ningún resultado coincide con tu búsqueda' : 'No tienes reportes asignados aún' }}</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left">Folio</th>
                                <th class="px-6 py-3 text-left">Paciente</th>
                                <th class="px-6 py-3 text-left">Estudios</th>
                                <th class="px-6 py-3 text-left">Toma de muestra</th>
                                <th class="px-6 py-3 text-left">Fecha reporte</th>
                                <th class="px-6 py-3 text-center">PDF</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="reporte in reportesFiltrados" :key="reporte.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-mono font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                                        {{ reporte.folio }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ reporte.nombre_cliente }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="(estudio, i) in reporte.estudios.slice(0, 3)"
                                            :key="i"
                                            class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full"
                                        >
                                            {{ estudio }}
                                        </span>
                                        <span v-if="reporte.estudios.length > 3" class="text-xs text-gray-400">
                                            +{{ reporte.estudios.length - 3 }} más
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ formatFecha(reporte.toma_muestra) }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ formatFecha(reporte.fecha_reporte) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <button
                                        @click="verPDF(reporte.id)"
                                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <span>Ver PDF</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
