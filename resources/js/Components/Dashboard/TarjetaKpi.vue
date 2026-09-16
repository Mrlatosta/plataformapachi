<script setup>
import { computed, ref } from 'vue';
import { color } from './tema';

const props = defineProps({
    titulo: { type: String, required: true },
    valor: { type: String, required: true },
    delta: { type: Number, default: null },
    // 'arriba' = subir es bueno, 'abajo' = bajar es bueno, 'neutro' = sin juicio
    sentido: { type: String, default: 'arriba' },
    detalle: { type: String, default: '' },
    aviso: { type: String, default: '' },
    tendencia: { type: Array, default: () => [] },
    tendenciaFormato: { type: Function, default: (v) => v },
    tendenciaEtiquetas: { type: Array, default: () => [] },
    info: { type: String, default: '' },
});

const estadoDelta = computed(() => {
    if (props.delta === null || props.delta === undefined) return null;
    const plano = Math.abs(props.delta) < 0.005;
    const sube = props.delta > 0;
    let tono = 'neutro';
    if (!plano && props.sentido !== 'neutro') {
        tono = (sube === (props.sentido === 'arriba')) ? 'bueno' : 'malo';
    }
    return {
        flecha: plano ? '→' : (sube ? '▲' : '▼'),
        texto: `${sube ? '+' : ''}${(props.delta * 100).toFixed(Math.abs(props.delta) < 0.1 ? 1 : 0)}%`,
        clase: { bueno: 'text-[#006300] bg-[#0ca30c]/10', malo: 'text-[#b42f2f] bg-[#d03b3b]/10', neutro: 'text-[#52514e] bg-[#f0efec]' }[tono],
    };
});

// Sparkline en SVG
const W = 240;
const H = 32;
const puntos = computed(() => {
    const vals = props.tendencia.map((v) => (v === null || v === undefined ? null : Number(v)));
    const validos = vals.filter((v) => v !== null);
    if (validos.length < 2) return [];
    const max = Math.max(...validos);
    const min = Math.min(0, ...validos);
    const rango = max - min || 1;
    const paso = vals.length > 1 ? W / (vals.length - 1) : 0;
    return vals.map((v, i) => ({
        x: i * paso,
        y: v === null ? null : H - 2 - ((v - min) / rango) * (H - 4),
        v,
        i,
    }));
});

const ruta = computed(() => {
    let d = '';
    let pluma = false;
    for (const p of puntos.value) {
        if (p.y === null) { pluma = false; continue; }
        d += `${pluma ? 'L' : 'M'}${p.x.toFixed(1)},${p.y.toFixed(1)} `;
        pluma = true;
    }
    return d;
});

const area = computed(() => {
    const v = puntos.value.filter((p) => p.y !== null);
    if (v.length < 2) return '';
    return `M${v[0].x},${H} ` + v.map((p) => `L${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ') + ` L${v[v.length - 1].x},${H} Z`;
});

const hover = ref(null);
const moverSpark = (e) => {
    const r = e.currentTarget.getBoundingClientRect();
    const x = ((e.clientX - r.left) / r.width) * W;
    let mejor = null;
    for (const p of puntos.value) {
        if (p.y === null) continue;
        if (!mejor || Math.abs(p.x - x) < Math.abs(mejor.x - x)) mejor = p;
    }
    hover.value = mejor;
};
const ultimo = computed(() => [...puntos.value].reverse().find((p) => p.y !== null));
</script>

<template>
    <div class="flex min-w-0 flex-col justify-between rounded-xl bg-[#fcfcfb] p-4 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
        <div class="flex items-start justify-between gap-2">
            <p class="m-0 text-xs font-medium uppercase tracking-wide text-[#52514e]" :title="info">
                {{ titulo }}
                <span v-if="info" class="ml-0.5 cursor-help text-[#898781]">ⓘ</span>
            </p>
            <span
                v-if="estadoDelta"
                class="shrink-0 rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular-nums"
                :class="estadoDelta.clase"
                title="Contra el periodo anterior de la misma duración"
            >{{ estadoDelta.flecha }} {{ estadoDelta.texto }}</span>
            <span v-else class="shrink-0 text-[11px] text-[#898781]" title="Sin datos en el periodo anterior">sin comparativo</span>
        </div>

        <p class="m-0 mt-2 text-2xl font-bold tabular-nums text-[#0b0b0b]">{{ valor }}</p>
        <div v-if="puntos.length" class="relative mt-2">
            <svg
                    :viewBox="`0 0 ${W} ${H}`"
                    class="block h-8 w-full overflow-visible"
                    preserveAspectRatio="none"
                    @mousemove="moverSpark"
                    @mouseleave="hover = null"
                >
                    <path :d="area" :fill="color.serie1" fill-opacity="0.08" />
                    <path :d="ruta" fill="none" :stroke="color.serie1" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                    <circle v-if="!hover && ultimo" :cx="ultimo.x" :cy="ultimo.y" r="2.5" :fill="color.serie1" />
                    <template v-if="hover">
                        <line :x1="hover.x" :x2="hover.x" y1="0" :y2="H" :stroke="color.eje" stroke-width="1" vector-effect="non-scaling-stroke" />
                        <circle :cx="hover.x" :cy="hover.y" r="3" :fill="color.serie1" stroke="#fff" stroke-width="1.5" />
                    </template>
                </svg>
                <div
                    v-if="hover"
                    :style="{ [hover.x > W / 2 ? 'right' : 'left']: hover.x > W / 2 ? `${100 - (hover.x / W) * 100}%` : `${(hover.x / W) * 100}%` }"
                    class="pointer-events-none absolute bottom-full z-10 mb-1 whitespace-nowrap rounded-md bg-white px-2 py-1 text-[11px] text-[#0b0b0b] shadow ring-1 ring-black/10"
                >
                    <span class="text-[#52514e]">{{ tendenciaEtiquetas[hover.i] }}:</span>
                    <strong class="ml-1">{{ tendenciaFormato(hover.v) }}</strong>
                </div>
        </div>

        <p v-if="detalle" class="m-0 mt-2 text-xs text-[#52514e]">{{ detalle }}</p>
        <p v-if="aviso" class="m-0 mt-1 flex items-center gap-1 text-xs font-medium text-[#8a5a00]">
            <span aria-hidden="true">⚠</span> {{ aviso }}
        </p>
    </div>
</template>
