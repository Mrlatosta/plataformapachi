<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
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

// Laravel manda "« Previous" / "Next »" en ingles; se traducen aqui
const etiquetaPagina = (link, index) => {
    if (index === 0) return '&laquo; Anterior';
    if (index === props.reportes.links.length - 1) return 'Siguiente &raquo;';
    return link.label;
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(value);
};

// Menu de acciones: uno solo para toda la lista, teletransportado a <body> con
// posicion fija para que el contenedor con scroll de la tabla no lo recorte
const ANCHO_MENU = 224; // w-56
const ALTO_MENU_ESTIMADO = 170;
const menu = ref(null); // { reporte, left, top | bottom }

const abrirMenu = (reporte, evento) => {
    const rect = evento.currentTarget.getBoundingClientRect();
    const left = Math.min(Math.max(rect.right - ANCHO_MENU, 8), window.innerWidth - ANCHO_MENU - 8);

    // Si no cabe debajo del boton, se abre hacia arriba
    menu.value = window.innerHeight - rect.bottom < ALTO_MENU_ESTIMADO
        ? { reporte, left, bottom: window.innerHeight - rect.top + 4 }
        : { reporte, left, top: rect.bottom + 4 };
};

const cerrarMenu = () => {
    menu.value = null;
};

const estiloMenu = computed(() => {
    if (!menu.value) return {};
    const { left, top, bottom } = menu.value;
    return top !== undefined
        ? { left: `${left}px`, top: `${top}px` }
        : { left: `${left}px`, bottom: `${bottom}px` };
});

const cerrarConEscape = (evento) => {
    if (evento.key === 'Escape') cerrarMenu();
};

onMounted(() => {
    window.addEventListener('scroll', cerrarMenu, true);
    window.addEventListener('resize', cerrarMenu);
    window.addEventListener('keydown', cerrarConEscape);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', cerrarMenu, true);
    window.removeEventListener('resize', cerrarMenu);
    window.removeEventListener('keydown', cerrarConEscape);
});
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
                                        <span class="text-right">
                                            <span class="block font-semibold text-gray-900">{{ formatCurrency(reporte.total_precio) }}</span>
                                            <span v-if="reporte.descuento > 0" class="block text-[10px] text-red-600">Descuento {{ reporte.descuento }}%</span>
                                            <span v-if="reporte.aplica_iva" class="block text-[10px] text-emerald-600">IVA incluido</span>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ formatDate(reporte.created_at) }}</p>
                                </div>

                                <button
                                    type="button"
                                    @click="abrirMenu(reporte, $event)"
                                    :aria-expanded="menu?.reporte.id === reporte.id"
                                    aria-haspopup="menu"
                                    class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    Acciones
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
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
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
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
                                            <span v-if="reporte.descuento > 0" class="block text-xs font-normal text-red-600">
                                                Descuento {{ reporte.descuento }}%: -{{ formatCurrency(reporte.monto_descuento) }}
                                            </span>
                                            <span v-if="reporte.aplica_iva" class="block text-xs font-normal text-emerald-600">
                                                IVA incluido: {{ formatCurrency(reporte.iva) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(reporte.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button
                                                type="button"
                                                @click="abrirMenu(reporte, $event)"
                                                :aria-expanded="menu?.reporte.id === reporte.id"
                                                aria-haspopup="menu"
                                                class="inline-flex items-center gap-1 px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                                            >
                                                Acciones
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Sin resultados -->
                        <div v-if="reportes.data.length === 0" class="py-10 text-center text-sm text-gray-500">
                            No se encontraron reportes con los filtros actuales.
                        </div>

                        <!-- Paginación -->
                        <div v-if="reportes.links.length > 3" class="mt-6 flex flex-col gap-4">
                            <div class="text-xs sm:text-sm text-gray-700 text-center sm:text-left">
                                Mostrando {{ reportes.from }} a {{ reportes.to }} de {{ reportes.total }} resultados
                            </div>
                            <div class="flex flex-wrap gap-1 sm:gap-2 justify-center">
                                <template v-for="(link, index) in reportes.links" :key="index">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        preserve-state
                                        :class="[
                                            'px-2 sm:px-4 py-1 sm:py-2 rounded-lg transition-colors text-xs sm:text-sm',
                                            link.active
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                        ]"
                                    >
                                        <span v-html="etiquetaPagina(link, index)"></span>
                                    </Link>
                                    <span
                                        v-else
                                        :class="[
                                            'px-2 sm:px-4 py-1 sm:py-2 rounded-lg text-xs sm:text-sm opacity-50 cursor-not-allowed',
                                            'bg-gray-200 text-gray-700'
                                        ]"
                                        v-html="etiquetaPagina(link, index)"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menú de acciones del reporte (compartido por todas las filas) -->
        <Teleport to="body">
            <div v-if="menu" class="fixed inset-0 z-40" @click="cerrarMenu"></div>
            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="menu"
                    role="menu"
                    :style="estiloMenu"
                    class="fixed z-50 w-56 rounded-lg bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                >
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 border-b border-gray-100">
                        {{ menu.reporte.folio }}
                    </p>
                    <a
                        role="menuitem"
                        :href="`/api/reportes/${menu.reporte.id}/pdf`"
                        @click="cerrarMenu"
                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V8l-6-6H6a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Descargar reporte (PDF)
                    </a>
                    <a
                        role="menuitem"
                        :href="`/api/reportes/${menu.reporte.id}/orden`"
                        @click="cerrarMenu"
                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Descargar orden de trabajo
                    </a>
                    <Link
                        role="menuitem"
                        :href="route('reportes.show', menu.reporte.id)"
                        @click="cerrarMenu"
                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-t border-gray-100"
                    >
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a1 1 0 011.4 0l1.6 1.6a1 1 0 010 1.4L9.5 17.5 6 18l.5-3.5 8.2-8.2z"/>
                        </svg>
                        Editar reporte
                    </Link>
                </div>
            </transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
