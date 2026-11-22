<script setup>
// T10: renglones AUTO debajo de la creatinina, la cistatina C o la albuminuria
// (TFGe y RAC). Solo lectura; se recalculan al capturar y no se guardan:
// el PDF los vuelve a calcular con los mismos datos.
import { CATEGORIAS_A, CATEGORIAS_G } from '@/utils/funcionRenal';

defineProps({
    filas: { type: Array, default: () => [] },
});

const categorias = (fila) => (fila.clave === 'rac' ? CATEGORIAS_A : CATEGORIAS_G);
</script>

<template>
    <tr v-for="fila in filas" :key="fila.clave" class="align-top bg-indigo-50/40">
        <td class="px-4 py-3 text-sm text-gray-900">
            {{ fila.nombre }}
            <span class="block text-xs text-gray-500">{{ fila.unidad }}</span>
        </td>
        <td class="px-4 py-3 text-xs text-gray-600">
            <ul class="space-y-0.5">
                <li v-for="c in categorias(fila)" :key="c.codigo"
                    :class="c.codigo === fila.categoria?.codigo ? 'font-semibold text-gray-900' : ''">
                    {{ c.rango }} · {{ c.codigo }} {{ c.nombre }}
                </li>
            </ul>
        </td>
        <td class="px-4 py-3">
            <div class="flex items-center gap-2">
                <output
                    class="relative flex-1 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 pr-12 text-sm"
                    :class="fila.bajo || fila.alto ? 'font-semibold text-red-700' : 'text-gray-900'"
                    :aria-label="`${fila.nombre}: ${fila.texto} ${fila.unidad}`"
                >
                    {{ fila.texto }}
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-700">Auto</span>
                </output>
                <span v-if="fila.bajo" class="text-sm font-semibold text-red-600">↓ Bajo</span>
                <span v-else-if="fila.alto" class="text-sm font-semibold text-red-600">↑ Alto</span>
            </div>
            <p class="mt-1 text-xs text-gray-500">
                <span class="font-semibold text-indigo-700">AUTO</span> · Se calcula solo: {{ fila.formula }}
                <span v-if="fila.categoria"> · {{ fila.categoria.codigo }} {{ fila.categoria.nombre }}</span>
            </p>
        </td>
    </tr>
</template>
