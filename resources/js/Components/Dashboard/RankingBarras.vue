<script setup>
// Lista ordenada con barras horizontales en HTML (nombres largos legibles)
import { computed } from 'vue';

const props = defineProps({
    filas: { type: Array, required: true }, // [{ clave, nombre, valor, principal, secundario, etiqueta? }]
    vacio: { type: String, default: 'Sin datos en el periodo' },
});

const maximo = computed(() => Math.max(1, ...props.filas.map((f) => f.valor)));
</script>

<template>
    <ol v-if="filas.length" class="m-0 list-none space-y-2.5 p-0">
        <li v-for="(f, i) in filas" :key="f.clave" class="group">
            <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                <span class="flex min-w-0 items-baseline gap-2">
                    <span class="w-4 shrink-0 text-right text-xs tabular-nums text-[#898781]">{{ i + 1 }}</span>
                    <span class="truncate text-[#0b0b0b]" :title="f.nombre">{{ f.nombre }}</span>
                    <span v-if="f.etiqueta" class="shrink-0 rounded bg-[#f0efec] px-1 text-[10px] text-[#52514e]">{{ f.etiqueta }}</span>
                </span>
                <span class="shrink-0 whitespace-nowrap tabular-nums">
                    <strong class="text-[#0b0b0b]">{{ f.principal }}</strong>
                    <span class="ml-1.5 text-xs text-[#52514e]">{{ f.secundario }}</span>
                </span>
            </div>
            <div class="ml-6 h-2 rounded-full bg-[#f0efec]">
                <div
                    class="h-2 rounded-full bg-[#2a78d6] transition-all duration-500 group-hover:bg-[#1c5cab]"
                    :style="{ width: `${Math.max(1, (f.valor / maximo) * 100)}%` }"
                ></div>
            </div>
        </li>
    </ol>
    <p v-else class="m-0 py-8 text-center text-sm text-[#898781]">{{ vacio }}</p>
</template>
