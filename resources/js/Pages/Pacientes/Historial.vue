<script setup>
// T1: todas las órdenes del paciente, con sus estudios, resultados y quién validó.
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import EstadoBadge from '@/Components/Captura/EstadoBadge.vue';
import { formatoFechaCorta, formatoFechaHora } from '@/utils/captura';
import { flechaRango } from '@/utils/rangoReferencia';

const props = defineProps({
    paciente: Object,
    ordenes: Array,
});

// La orden más reciente abierta; las demás se expanden al hacer clic
const abiertas = ref(new Set(props.ordenes.length ? [props.ordenes[0].id] : []));
const alternar = (id) => {
    const nuevas = new Set(abiertas.value);
    nuevas.has(id) ? nuevas.delete(id) : nuevas.add(id);
    abiertas.value = nuevas;
};
const todasAbiertas = computed(() => props.ordenes.length > 0 && abiertas.value.size === props.ordenes.length);
const alternarTodas = () => {
    abiertas.value = todasAbiertas.value ? new Set() : new Set(props.ordenes.map((o) => o.id));
};

const marca = (r) => (r.fuera_rango ? (flechaRango(r.direccion_rango) || '*') : '');
</script>

<template>
    <Head :title="`Historial de ${paciente.nombre}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500">Historial del paciente</p>
                    <h2 class="text-xl font-semibold text-gray-800">{{ paciente.nombre }}</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('captura.resultados')"
                        class="px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                        Nueva captura
                    </Link>
                    <Link :href="route('pacientes')"
                        class="px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">
                        Pacientes
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Fecha de nacimiento</dt>
                            <dd class="font-medium text-gray-900">{{ paciente.fecha_nacimiento ? formatoFechaCorta(paciente.fecha_nacimiento + 'T00:00:00') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Edad / Sexo</dt>
                            <dd class="font-medium text-gray-900">{{ paciente.edad ?? '—' }} años · {{ paciente.sexo || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Teléfono</dt>
                            <dd class="font-medium text-gray-900">{{ paciente.telefono || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Órdenes</dt>
                            <dd class="font-medium text-gray-900">{{ ordenes.length }}</dd>
                        </div>
                    </dl>
                </section>

                <div v-if="ordenes.length" class="flex justify-end">
                    <button type="button" @click="alternarTodas" class="text-sm font-medium text-blue-700 hover:underline">
                        {{ todasAbiertas ? 'Contraer todas' : 'Expandir todas' }}
                    </button>
                </div>

                <p v-if="!ordenes.length" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                    Este paciente todavía no tiene órdenes.
                </p>

                <ol class="relative space-y-4 border-l-2 border-gray-200 pl-6">
                    <li v-for="orden in ordenes" :key="orden.id" class="relative">
                        <span class="absolute -left-[33px] top-5 h-4 w-4 rounded-full border-2 border-white bg-emerald-500 ring-2 ring-emerald-200" aria-hidden="true" />
                        <article class="bg-white rounded-xl border border-gray-200 shadow-sm">
                            <button type="button" @click="alternar(orden.id)" :aria-expanded="abiertas.has(orden.id)"
                                class="flex w-full flex-wrap items-center gap-3 px-5 py-4 text-left hover:bg-gray-50 rounded-xl">
                                <span class="font-semibold text-gray-900">{{ formatoFechaCorta(orden.toma_muestra) }}</span>
                                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-800">{{ orden.folio }}</span>
                                <EstadoBadge :estado="orden.estado" tipo="orden" />
                                <span class="text-sm text-gray-500 truncate flex-1 min-w-[10rem]">
                                    {{ orden.estudios.map((e) => e.nombre).join(', ') }}
                                </span>
                                <svg class="h-5 w-5 text-gray-400 transition-transform" :class="{ 'rotate-180': abiertas.has(orden.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div v-if="abiertas.has(orden.id)" class="border-t border-gray-100 px-5 pb-5 space-y-5">
                                <p v-if="orden.medico_solicitante && orden.medico_solicitante !== 'N/A'" class="pt-3 text-sm text-gray-600">
                                    Médico: {{ orden.medico_solicitante }}
                                </p>
                                <section v-for="estudio in orden.estudios" :key="estudio.id" class="pt-3">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <h3 class="text-base font-semibold text-gray-800">{{ estudio.nombre }}</h3>
                                        <EstadoBadge :estado="estudio.estado" />
                                        <span v-if="!estudio.incluir_en_reporte" class="text-xs text-gray-500">(no sale en el PDF)</span>
                                        <span v-if="estudio.validado_por" class="text-xs text-gray-500">
                                            Validó {{ estudio.validado_por }}<template v-if="estudio.validado_at"> · {{ formatoFechaHora(estudio.validado_at) }}</template>
                                        </span>
                                    </div>
                                    <div v-if="estudio.resultados.length" class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
                                                    <th scope="col" class="py-1.5 pr-4 font-medium">Examen</th>
                                                    <th scope="col" class="py-1.5 pr-4 font-medium">Resultado</th>
                                                    <th scope="col" class="py-1.5 pr-4 font-medium">Unidad</th>
                                                    <th scope="col" class="py-1.5 font-medium">Referencia</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <tr v-for="(r, i) in estudio.resultados" :key="i">
                                                    <td class="py-1.5 pr-4 text-gray-800">{{ r.nombre }}</td>
                                                    <td class="py-1.5 pr-4" :class="r.fuera_rango ? 'font-semibold text-red-700' : 'text-gray-900'">
                                                        {{ r.resultado }} <span v-if="marca(r)" :aria-label="'fuera de rango'">{{ marca(r) }}</span>
                                                    </td>
                                                    <td class="py-1.5 pr-4 text-gray-500">{{ r.unidad }}</td>
                                                    <td class="py-1.5 text-gray-500">{{ r.referencia }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p v-else class="text-sm text-gray-500">Sin resultados capturados.</p>
                                </section>
                                <div class="pt-2">
                                    <Link :href="route('reportes.show', orden.id)" class="text-sm font-medium text-blue-700 hover:underline">
                                        Abrir orden {{ orden.folio }} →
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </li>
                </ol>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
