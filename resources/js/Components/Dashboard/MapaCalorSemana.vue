<script setup>
// Mapa de calor dia de la semana x hora (hora local del laboratorio)
import { computed, ref } from 'vue';
import { rampaAzul } from './tema';

const props = defineProps({
    matriz: { type: Array, required: true }, // [7][24], 0 = lunes
});

const dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
const diasLargos = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

// Solo mostramos las horas con actividad (minimo 7:00-20:00)
const horas = computed(() => {
    let min = 7;
    let max = 20;
    props.matriz.forEach((fila) => fila.forEach((n, h) => {
        if (n > 0) { min = Math.min(min, h); max = Math.max(max, h); }
    }));
    return Array.from({ length: max - min + 1 }, (_, i) => min + i);
});

const maximo = computed(() => Math.max(1, ...props.matriz.flat()));
const total = computed(() => props.matriz.flat().reduce((a, b) => a + b, 0));
const totalesDia = computed(() => props.matriz.map((f) => f.reduce((a, b) => a + b, 0)));
const maxDia = computed(() => Math.max(1, ...totalesDia.value));

const fondo = (n) => {
    if (!n) return '#f0efec';
    const idx = Math.min(rampaAzul.length - 1, Math.round((n / maximo.value) * (rampaAzul.length - 1)));
    return rampaAzul[idx];
};

const hover = ref(null);
</script>

<template>
    <div>
        <div class="overflow-x-auto pb-1">
            <div
                class="grid min-w-[520px] gap-[2px]"
                :style="{ gridTemplateColumns: `2.5rem repeat(${horas.length}, minmax(0, 1fr)) 4.5rem` }"
                @mouseleave="hover = null"
            >
                <div></div>
                <div v-for="h in horas" :key="'h' + h" class="text-center text-[10px] tabular-nums text-[#898781]">
                    {{ h % 2 === 0 ? h : '' }}
                </div>
                <div class="pl-2 text-[10px] text-[#898781]">Total</div>

                <template v-for="(fila, d) in matriz" :key="'d' + d">
                    <div class="flex items-center text-[11px] font-medium text-[#52514e]">{{ dias[d] }}</div>
                    <div
                        v-for="h in horas"
                        :key="d + '-' + h"
                        class="aspect-square min-h-[14px] cursor-default rounded-[3px] transition-transform hover:scale-110 hover:ring-2 hover:ring-[#0b0b0b]/60"
                        :style="{ background: fondo(fila[h]) }"
                        @mouseenter="hover = { d, h, n: fila[h] }"
                    ></div>
                    <div class="flex items-center gap-1 pl-2">
                        <div class="h-2 rounded-sm bg-[#2a78d6]" :style="{ width: `${(totalesDia[d] / maxDia) * 100}%`, minWidth: totalesDia[d] ? '2px' : '0' }"></div>
                        <span class="text-[10px] tabular-nums text-[#52514e]">{{ totalesDia[d] }}</span>
                    </div>
                </template>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-[#52514e]">
            <p class="m-0 min-h-[1rem]">
                <template v-if="hover">
                    <strong class="text-[#0b0b0b]">{{ hover.n }} órdenes</strong>
                    el {{ diasLargos[hover.d] }} de {{ hover.h }}:00 a {{ hover.h + 1 }}:00
                    <span v-if="total">({{ ((hover.n / total) * 100).toFixed(1) }}% del periodo)</span>
                </template>
                <template v-else>Pasa el cursor sobre una celda para ver el detalle.</template>
            </p>
            <div class="flex items-center gap-1.5">
                <span>Menos</span>
                <span v-for="i in [0, 3, 6, 9, 12]" :key="i" class="h-3 w-3 rounded-[3px]" :style="{ background: rampaAzul[i] }"></span>
                <span>Más</span>
            </div>
        </div>
    </div>
</template>
