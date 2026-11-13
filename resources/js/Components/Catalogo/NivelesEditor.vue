<script setup>
// T5: tabla de niveles de referencia de un examen (desde, hasta, interpretación).
// El nivel marcado como "Normal" es el que usan las flechas ↑ ↓ (T4).
import { computed, nextTick, ref } from 'vue';

const niveles = defineModel({ type: Array, default: () => [] });

const props = defineProps({
    idBase: { type: String, required: true },
    nombreExamen: { type: String, default: '' },
});

const abierto = ref(niveles.value.length > 0);

const agregar = async () => {
    const ultimo = niveles.value[niveles.value.length - 1];
    niveles.value.push({
        desde: ultimo?.hasta ?? '',
        hasta: '',
        interpretacion: niveles.value.length === 0 ? 'Normal' : '',
        es_normal: niveles.value.length === 0,
    });
    abierto.value = true;
    await nextTick();
    document.getElementById(`${props.idBase}-interp-${niveles.value.length - 1}`)?.focus();
};

const quitar = (i) => {
    const eraNormal = niveles.value[i].es_normal;
    niveles.value.splice(i, 1);
    if (eraNormal && niveles.value.length) niveles.value[0].es_normal = false;
};

const mover = (i, dir) => {
    const destino = i + dir;
    if (destino < 0 || destino >= niveles.value.length) return;
    const [nivel] = niveles.value.splice(i, 1);
    niveles.value.splice(destino, 0, nivel);
};

const marcarNormal = (i) => {
    niveles.value.forEach((n, k) => { n.es_normal = k === i; });
};

const vacio = (v) => v === '' || v === null || v === undefined;
const errorFila = (n) => {
    if (!String(n.interpretacion ?? '').trim()) return 'Falta la interpretación';
    if (vacio(n.desde) && vacio(n.hasta)) return 'Pon al menos "desde" o "hasta"';
    if (!vacio(n.desde) && !vacio(n.hasta) && Number(n.desde) > Number(n.hasta)) return '"Desde" es mayor que "hasta"';
    return '';
};

const resumen = computed(() => {
    if (!niveles.value.length) return 'Sin tabla de niveles';
    const normal = niveles.value.find((n) => n.es_normal);
    return `${niveles.value.length} ${niveles.value.length === 1 ? 'nivel' : 'niveles'}${normal ? '' : ' · sin nivel normal (no habrá flechas)'}`;
});
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-gray-50">
        <button type="button" @click="abierto = !abierto" :aria-expanded="abierto" :aria-controls="`${idBase}-panel`"
            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left">
            <span class="text-xs font-semibold text-gray-700">
                Tabla de niveles de referencia
                <span class="ml-1 font-normal text-gray-500">{{ resumen }}</span>
            </span>
            <svg class="h-4 w-4 text-gray-500 transition-transform" :class="{ 'rotate-180': abierto }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div v-show="abierto" :id="`${idBase}-panel`" class="space-y-2 border-t border-gray-200 px-3 pb-3 pt-2">
            <p class="text-xs text-gray-500">
                Úsala cuando la referencia tiene varios niveles (procalcitonina, perfil hormonal). Se imprime como tabla
                debajo del resultado con el nivel del paciente resaltado. El nivel marcado como <strong>Normal</strong> decide las flechas ↑ ↓. Deja «desde» o «hasta» vacío si ese lado no tiene límite.
            </p>

            <div v-if="niveles.length" class="hidden grid-cols-[6rem_6rem_1fr_4.5rem_6rem] gap-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500 sm:grid">
                <span>Desde</span><span>Hasta</span><span>Interpretación</span><span>Normal</span><span class="sr-only">Acciones</span>
            </div>

            <div v-for="(nivel, i) in niveles" :key="i" class="space-y-1">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-[6rem_6rem_1fr_4.5rem_6rem] sm:items-center">
                    <input :id="`${idBase}-desde-${i}`" v-model="nivel.desde" type="number" step="any" placeholder="—"
                        :aria-label="`Desde, nivel ${i + 1} de ${nombreExamen}`"
                        class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    <input :id="`${idBase}-hasta-${i}`" v-model="nivel.hasta" type="number" step="any" placeholder="—"
                        :aria-label="`Hasta, nivel ${i + 1} de ${nombreExamen}`"
                        class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    <input :id="`${idBase}-interp-${i}`" v-model="nivel.interpretacion" type="text" maxlength="120" placeholder="Ej: Elevado"
                        :aria-label="`Interpretación, nivel ${i + 1} de ${nombreExamen}`"
                        class="col-span-2 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 sm:col-span-1" />
                    <label class="flex items-center gap-1.5 text-xs text-gray-700">
                        <input type="radio" :name="`${idBase}-normal`" :checked="nivel.es_normal" @change="marcarNormal(i)"
                            class="text-emerald-600 focus:ring-emerald-500" />
                        Normal
                    </label>
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" @click="mover(i, -1)" :disabled="i === 0" :aria-label="`Subir nivel ${i + 1}`"
                            class="rounded px-2 py-1 text-gray-500 hover:bg-gray-200 disabled:opacity-30">↑</button>
                        <button type="button" @click="mover(i, 1)" :disabled="i === niveles.length - 1" :aria-label="`Bajar nivel ${i + 1}`"
                            class="rounded px-2 py-1 text-gray-500 hover:bg-gray-200 disabled:opacity-30">↓</button>
                        <button type="button" @click="quitar(i)" :aria-label="`Quitar nivel ${i + 1}`"
                            class="rounded px-2 py-1 text-red-600 hover:bg-red-50">✕</button>
                    </div>
                </div>
                <p v-if="errorFila(nivel)" class="text-xs text-amber-700">{{ errorFila(nivel) }}</p>
            </div>

            <button type="button" @click="agregar"
                class="inline-flex items-center gap-1 rounded-lg border border-dashed border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-blue-400 hover:text-blue-700">
                + Agregar nivel
            </button>
        </div>
    </div>
</template>
