<script setup>
// Celda de captura de un analito: valor con marca automática (T4), valores
// calculados de solo lectura (T9) y resultado anterior del paciente (T1).
import { computed } from 'vue';
import { flechaRango, opcionesCualitativas } from '@/utils/rangoReferencia';
import { formatoFechaCorta } from '@/utils/captura';
import { alPresionarEnter } from '@/utils/navegacionCaptura';

const props = defineProps({
    examen: { type: Object, required: true },
    anterior: { type: Object, default: null },
    inputId: { type: String, required: true },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['escribir', 'marca']);

const opciones = computed(() => opcionesCualitativas(props.examen.valor_referencia) || []);
const listaId = computed(() => `${props.inputId}-opciones`);

const marca = computed(() => {
    if (!props.examen.fuera_rango) return '';
    return flechaRango(props.examen.direccion_rango) || '*';
});

const descripcionMarca = computed(() => ({
    '↑': 'Arriba del intervalo de referencia',
    '↓': 'Debajo del intervalo de referencia',
    '*': 'Distinto al valor normal',
}[marca.value] || ''));

// Selector compacto para corregir la marca a mano
const valorMarca = computed({
    get: () => (!props.examen.fuera_rango ? '' : props.examen.direccion_rango || 'texto'),
    set: (valor) => emit('marca', valor),
});

const textoAnterior = computed(() => {
    if (!props.anterior) return '';
    const fecha = formatoFechaCorta(props.anterior.fecha);
    const flecha = props.anterior.fuera_rango ? (flechaRango(props.anterior.direccion_rango) || '*') : '';
    return `${props.anterior.resultado}${flecha ? ' ' + flecha : ''} · ${fecha}`;
});
</script>

<template>
    <div>
        <div class="flex items-center gap-2">
            <div class="relative flex-1">
                <input
                    :id="inputId"
                    v-model="examen.resultado"
                    type="text"
                    :list="opciones.length ? listaId : undefined"
                    :readonly="examen.calculado || disabled"
                    :aria-describedby="examen.calculado || anterior ? `${inputId}-ayuda` : undefined"
                    :title="examen.calculado ? `Calculado: ${examen.formula}` : ''"
                    class="w-full rounded-lg border px-3 py-2 text-sm transition-all focus:border-transparent focus:ring-2 focus:ring-purple-500"
                    :class="[
                        examen.calculado ? 'border-dashed border-gray-300 bg-gray-50 pr-12' : 'border-gray-300',
                        examen.fuera_rango ? 'font-semibold text-red-700' : 'text-gray-900',
                    ]"
                    placeholder="Resultado"
                    data-captura-resultado
                    enterkeyhint="next"
                    @input="emit('escribir')"
                    @keydown.enter="alPresionarEnter"
                />
                <span
                    v-if="examen.calculado"
                    class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-700"
                >
                    Auto
                </span>
                <datalist v-if="opciones.length" :id="listaId">
                    <option v-for="opcion in opciones" :key="opcion" :value="opcion" />
                </datalist>
            </div>

            <span
                class="w-4 text-center text-base font-bold text-red-600"
                :aria-label="descripcionMarca"
                :title="descripcionMarca"
            >{{ marca }}</span>

            <select
                v-model="valorMarca"
                :disabled="disabled"
                class="rounded-lg border-gray-300 py-1.5 pl-2 pr-7 text-xs text-gray-600 focus:border-purple-500 focus:ring-purple-500"
                :aria-label="`Marca de fuera de rango para ${examen.nombre_examen}`"
                title="Se marca sola al escribir; cámbiala solo si hace falta"
            >
                <option value="">Normal</option>
                <option value="alto">↑ Alto</option>
                <option value="bajo">↓ Bajo</option>
                <option value="texto">* Anormal</option>
            </select>
        </div>

        <p v-if="examen.calculado || anterior" :id="`${inputId}-ayuda`" class="mt-1 text-xs text-gray-500">
            <span v-if="examen.calculado">Se calcula solo: {{ examen.formula }}</span>
            <span v-if="examen.calculado && anterior"> · </span>
            <span v-if="anterior">Anterior: <span class="font-medium text-gray-700">{{ textoAnterior }}</span></span>
        </p>
    </div>
</template>
