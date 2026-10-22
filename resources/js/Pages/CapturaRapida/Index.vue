<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormularioJornada from '@/Components/CapturaRapida/FormularioJornada.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    jornadas: { type: Array, default: () => [] },
    catalogo: { type: Array, required: true },
    medicos: { type: Array, default: () => [] },
});

const jornadas = ref(props.jornadas);
const mostrarFormulario = ref(false);
const aviso = ref(null);

const PASOS = [
    { titulo: 'Configura', texto: 'Elige los estudios (p. ej. antidoping) y el resultado por defecto de cada examen.' },
    { titulo: 'Agrega colaboradores', texto: 'Uno por uno con Enter, o pega la lista desde Excel.' },
    { titulo: 'Captura', texto: 'Todo arranca en negativo: solo da clic en lo que salga positivo y marca "Listo".' },
    { titulo: 'Genera reportes', texto: 'Un clic emite todos los reportes con folio, QR y PDF.' },
];

const formatoFecha = (fechaHora) => new Date(fechaHora).toLocaleString('es-MX', {
    weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
});

const porcentaje = (parte, total) => (total ? Math.round((parte / total) * 100) : 0);

const creada = ({ id }) => {
    router.visit(route('captura.rapida.jornada', id));
};

// Jornada pendiente de confirmar su eliminación
const porEliminar = ref(null);
const eliminando = ref(false);

const eliminar = async () => {
    const jornada = porEliminar.value;
    eliminando.value = true;
    try {
        await axios.delete(`/api/jornadas/${jornada.id}`);
        jornadas.value = jornadas.value.filter((j) => j.id !== jornada.id);
        aviso.value = { tipo: 'exito', texto: 'Jornada eliminada.' };
    } catch (error) {
        aviso.value = { tipo: 'error', texto: error.response?.data?.message || 'No se pudo eliminar la jornada.' };
    } finally {
        eliminando.value = false;
        porEliminar.value = null;
    }
};
</script>

<template>
    <Head title="Captura rápida" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Captura rápida</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Jornadas de captura rápida</h1>
                        <p class="text-sm text-gray-500">Para antidopings en empresas y campañas: muchos pacientes con los mismos estudios.</p>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700" @click="mostrarFormulario = true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Nueva jornada
                    </button>
                </div>

                <div
                    v-if="aviso"
                    role="status"
                    class="flex items-center justify-between gap-3 rounded-lg border px-4 py-3 text-sm"
                    :class="aviso.tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'"
                >
                    <span>{{ aviso.texto }}</span>
                    <button type="button" class="rounded p-1 hover:bg-black/5" @click="aviso = null">
                        <span class="sr-only">Cerrar aviso</span>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Sin jornadas: cómo funciona -->
                <div v-if="!jornadas.length" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-900">¿Cómo funciona?</p>
                    <ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <li v-for="(paso, i) in PASOS" :key="paso.titulo" class="rounded-lg bg-gray-50 p-4">
                            <span class="mb-2 flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">{{ i + 1 }}</span>
                            <p class="font-medium text-gray-900">{{ paso.titulo }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ paso.texto }}</p>
                        </li>
                    </ol>
                </div>

                <!-- Jornadas -->
                <ul v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <li v-for="jornada in jornadas" :key="jornada.id" class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <Link :href="route('captura.rapida.jornada', jornada.id)" class="block truncate text-lg font-semibold text-gray-900 hover:text-blue-700">{{ jornada.nombre }}</Link>
                                <p class="truncate text-sm text-gray-500">{{ jornada.empresa || 'Sin empresa' }} · {{ formatoFecha(jornada.fecha_toma) }}</p>
                            </div>
                            <button type="button" class="shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600" @click="porEliminar = jornada">
                                <span class="sr-only">Eliminar jornada {{ jornada.nombre }}</span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span v-for="estudio in jornada.estudios" :key="estudio" class="rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">{{ estudio }}</span>
                        </div>

                        <div class="mt-4 space-y-1.5">
                            <div class="flex h-2 overflow-hidden rounded-full bg-gray-100" aria-hidden="true">
                                <div class="bg-blue-600" :style="{ width: `${porcentaje(jornada.reportados, jornada.participantes)}%` }"></div>
                                <div class="bg-emerald-400" :style="{ width: `${porcentaje(jornada.listos, jornada.participantes)}%` }"></div>
                            </div>
                            <p class="text-xs text-gray-600">
                                <strong class="text-gray-900">{{ jornada.participantes }}</strong> colaboradores ·
                                <span class="text-emerald-700">{{ jornada.listos }} por emitir</span> ·
                                <span class="text-blue-700">{{ jornada.reportados }} con reporte</span>
                            </p>
                        </div>

                        <Link :href="route('captura.rapida.jornada', jornada.id)" class="mt-4 inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            Abrir captura
                        </Link>
                    </li>
                </ul>
            </div>
        </div>

        <Modal :show="!!porEliminar" max-width="md" @close="porEliminar = null">
            <div v-if="porEliminar" class="p-6">
                <h3 class="text-lg font-semibold text-gray-900">¿Eliminar «{{ porEliminar.nombre }}»?</h3>
                <p class="mt-2 text-sm text-gray-600">Se borra la lista de {{ porEliminar.participantes }} colaboradores y lo capturado que aún no tenga reporte.</p>
                <p v-if="porEliminar.reportados" class="mt-2 text-sm text-gray-600">Los {{ porEliminar.reportados }} reportes ya emitidos se conservan en Lista de reportes.</p>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="porEliminar = null">Cancelar</button>
                    <button type="button" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50" :disabled="eliminando" @click="eliminar">{{ eliminando ? 'Eliminando…' : 'Eliminar jornada' }}</button>
                </div>
            </div>
        </Modal>

        <FormularioJornada
            v-if="mostrarFormulario"
            :catalogo="catalogo"
            :medicos="medicos"
            @cerrar="mostrarFormulario = false"
            @guardada="creada"
        />
    </AuthenticatedLayout>
</template>
