<script setup>
import { computed } from 'vue';
import { ESTILOS_ESTADO, moneda, telefonoWhatsApp, mensajeWhatsApp } from '@/utils/agenda';

const props = defineProps({
    cita: { type: Object, required: true },
    estados: { type: Object, required: true },
    direccionLaboratorio: { type: String, default: '' },
    compacta: { type: Boolean, default: false },
    ocupada: { type: Boolean, default: false },
});

const emit = defineEmits(['editar', 'estado', 'atender', 'eliminar']);

const estilo = computed(() => ESTILOS_ESTADO[props.cita.estado] ?? ESTILOS_ESTADO.programada);
const inactiva = computed(() => ['cancelada', 'no_asistio'].includes(props.cita.estado));
const puedeAtender = computed(() => ['programada', 'confirmada'].includes(props.cita.estado));

const enlaceWhatsApp = computed(() => {
    const numero = telefonoWhatsApp(props.cita.telefono || props.cita.paciente?.telefono);
    if (!numero) return null;
    const tipo = props.cita.estado === 'confirmada' ? 'recordar' : 'confirmar';
    return `https://wa.me/${numero}?text=${encodeURIComponent(mensajeWhatsApp(props.cita, tipo, props.direccionLaboratorio))}`;
});

const resumenEstudios = computed(() => {
    const nombres = props.cita.estudios?.map((e) => e.nombre) ?? [];
    if (!nombres.length) return 'Sin estudios definidos';
    return nombres.length > 2 ? `${nombres.slice(0, 2).join(', ')} y ${nombres.length - 2} más` : nombres.join(', ');
});

const cambiarEstado = (evento) => {
    const valor = evento.target.value;
    evento.target.value = '';
    if (valor === '__eliminar') emit('eliminar', props.cita);
    else if (valor) emit('estado', props.cita, valor);
};
</script>

<template>
    <!-- Versión compacta (vista semana): toda la tarjeta abre el editor -->
    <button
        v-if="compacta"
        type="button"
        class="w-full text-left rounded-lg border border-gray-200 border-l-4 bg-white px-2.5 py-2 shadow-sm hover:shadow-md hover:border-gray-300 transition focus:outline-none focus:ring-2 focus:ring-blue-500"
        :class="[estilo.borde, inactiva && 'opacity-60']"
        @click="emit('editar', cita)"
    >
        <span class="flex items-center justify-between gap-2 text-xs font-semibold text-gray-900">
            <span>{{ cita.hora_inicio }}</span>
            <span v-if="cita.modalidad === 'domicilio'" class="rounded bg-orange-100 px-1.5 text-[10px] font-semibold text-orange-800">Domicilio</span>
        </span>
        <span class="mt-0.5 block truncate text-sm text-gray-800" :class="cita.estado === 'cancelada' && 'line-through'">{{ cita.nombre }}</span>
        <span class="mt-0.5 flex items-center gap-1.5 text-[11px] text-gray-500">
            <span class="h-2 w-2 rounded-full" :class="estilo.punto" aria-hidden="true"></span>
            {{ estados[cita.estado] }}
        </span>
    </button>

    <!-- Versión completa (vista día y próximas) -->
    <article
        v-else
        class="rounded-xl border border-gray-200 border-l-4 bg-white shadow-sm"
        :class="[estilo.borde, inactiva && 'opacity-70']"
        :aria-label="`Cita de ${cita.nombre} a las ${cita.hora_inicio}`"
    >
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 space-y-1">
                <p class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-bold text-gray-900">{{ cita.hora_inicio }}–{{ cita.hora_fin }}</span>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="estilo.chip">{{ estados[cita.estado] }}</span>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        :class="cita.modalidad === 'domicilio' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-700'"
                    >{{ cita.modalidad === 'domicilio' ? 'A domicilio' : 'En laboratorio' }}</span>
                    <span v-if="ocupada" class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Empalmada</span>
                </p>
                <h3 class="truncate text-base font-semibold text-gray-900" :class="cita.estado === 'cancelada' && 'line-through'">
                    {{ cita.nombre }}
                    <span v-if="!cita.paciente_id" class="ml-1 align-middle text-xs font-normal text-gray-500">(sin registro)</span>
                </h3>
                <p class="text-sm text-gray-600">{{ resumenEstudios }}</p>
                <p v-if="cita.modalidad === 'domicilio' && cita.direccion" class="text-sm text-orange-800">📍 {{ cita.direccion }}</p>
                <p class="text-xs text-gray-500">
                    <span v-if="cita.telefono">{{ cita.telefono }} · </span>
                    Estimado {{ moneda.format(cita.total_estimado) }}
                    <span v-if="cita.notas"> · Nota: {{ cita.notas }}</span>
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <button
                    v-if="cita.estado === 'programada'"
                    type="button"
                    class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                    @click="emit('estado', cita, 'confirmada')"
                >Confirmar</button>
                <button
                    v-if="puedeAtender"
                    type="button"
                    class="rounded-lg bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-700"
                    title="Marca la cita como atendida y abre Captura de Resultados"
                    @click="emit('atender', cita)"
                >Atender</button>
                <a
                    v-if="enlaceWhatsApp"
                    :href="enlaceWhatsApp"
                    target="_blank"
                    rel="noopener"
                    class="rounded-lg border border-green-600 px-3 py-2 text-sm font-semibold text-green-700 hover:bg-green-50"
                >
                    {{ cita.estado === 'confirmada' ? 'Recordar' : 'Avisar' }}<span class="sr-only"> por WhatsApp a {{ cita.nombre }}</span>
                </a>
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="emit('editar', cita)"
                >Editar<span class="sr-only"> cita de {{ cita.nombre }}</span></button>
                <label class="sr-only" :for="`estado-${cita.id}`">Más acciones para la cita de {{ cita.nombre }}</label>
                <select
                    :id="`estado-${cita.id}`"
                    class="rounded-lg border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-700 focus:border-blue-500 focus:ring-blue-500"
                    @change="cambiarEstado"
                >
                    <option value="">Más…</option>
                    <optgroup label="Cambiar estado">
                        <option v-for="(etiqueta, valor) in estados" :key="valor" :value="valor" :disabled="valor === cita.estado">{{ etiqueta }}</option>
                    </optgroup>
                    <option value="__eliminar">Eliminar cita</option>
                </select>
            </div>
        </div>
    </article>
</template>
