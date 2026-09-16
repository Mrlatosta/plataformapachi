<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import GraficaChart from '@/Components/Dashboard/GraficaChart.vue';
import MapaCalorSemana from '@/Components/Dashboard/MapaCalorSemana.vue';
import PanelDashboard from '@/Components/Dashboard/PanelDashboard.vue';
import RankingBarras from '@/Components/Dashboard/RankingBarras.vue';
import SelectorSegmentos from '@/Components/Dashboard/SelectorSegmentos.vue';
import TarjetaKpi from '@/Components/Dashboard/TarjetaKpi.vue';
import { color, fmt, opcionesBase } from '@/Components/Dashboard/tema';

const props = defineProps({
    metricas: { type: Object, required: true },
});

const m = computed(() => props.metricas);
const k = computed(() => m.value.kpis);
const hoy = computed(() => m.value.hoy);

// ---------------------------------------------------------------- Filtro de fechas
const filtro = ref({ fecha_inicio: m.value.periodo.inicio, fecha_fin: m.value.periodo.fin });
const cargando = ref(false);

watch(() => m.value.periodo, (p) => {
    filtro.value = { fecha_inicio: p.inicio, fecha_fin: p.fin };
});

// Fecha local en formato YYYY-MM-DD (toISOString usaria UTC y cambia el dia de noche)
const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

const atajos = [
    { clave: '7d', label: '7 días', rango: (h) => [new Date(h.getFullYear(), h.getMonth(), h.getDate() - 6), h] },
    { clave: '30d', label: '30 días', rango: (h) => [new Date(h.getFullYear(), h.getMonth(), h.getDate() - 29), h] },
    { clave: '90d', label: '90 días', rango: (h) => [new Date(h.getFullYear(), h.getMonth(), h.getDate() - 89), h] },
    { clave: 'mes', label: 'Este mes', rango: (h) => [new Date(h.getFullYear(), h.getMonth(), 1), h] },
    { clave: 'mesant', label: 'Mes pasado', rango: (h) => [new Date(h.getFullYear(), h.getMonth() - 1, 1), new Date(h.getFullYear(), h.getMonth(), 0)] },
    { clave: '12m', label: '12 meses', rango: (h) => [new Date(h.getFullYear() - 1, h.getMonth(), h.getDate() + 1), h] },
    { clave: 'anio', label: 'Este año', rango: (h) => [new Date(h.getFullYear(), 0, 1), h] },
];

const rangoAtajo = (a) => a.rango(new Date()).map(iso);

const atajoActivo = computed(() => atajos.find((a) => {
    const [i, f] = rangoAtajo(a);
    return i === m.value.periodo.inicio && f === m.value.periodo.fin;
})?.clave);

const aplicar = () => {
    router.get(route('dashboard'), filtro.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['metricas'],
        onStart: () => { cargando.value = true; },
        onFinish: () => { cargando.value = false; },
    });
};

const refrescar = () => {
    router.get(route('dashboard'), { ...filtro.value, refrescar: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['metricas'],
        onStart: () => { cargando.value = true; },
        onFinish: () => { cargando.value = false; },
    });
};

const usarAtajo = (a) => {
    const [i, f] = rangoAtajo(a);
    filtro.value = { fecha_inicio: i, fecha_fin: f };
    aplicar();
};

const fechaCorta = (s) => new Date(`${s}T12:00:00`).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
const textoPeriodo = computed(() => `${fechaCorta(m.value.periodo.inicio)} – ${fechaCorta(m.value.periodo.fin)}`);
const textoPrevio = computed(() => `${fechaCorta(m.value.periodo.prev_inicio)} – ${fechaCorta(m.value.periodo.prev_fin)}`);
const nombreGranularidad = computed(() => ({ day: 'día', week: 'semana', month: 'mes' })[m.value.periodo.granularidad]);

// ---------------------------------------------------------------- KPIs
const serie = computed(() => m.value.serie);
const etiquetasSerie = computed(() => serie.value.map((p) => p.label));

const tarjetas = computed(() => {
    const x = k.value.extra;
    return [
        {
            titulo: 'Ventas netas',
            valor: fmt.moneda(k.value.ventas.valor),
            delta: k.value.ventas.delta,
            detalle: x.brutas !== k.value.ventas.valor
                ? `Bruto ${fmt.moneda(x.brutas)} · Descuentos ${fmt.moneda(k.value.descuentos.valor)}`
                : `Periodo anterior: ${fmt.moneda(k.value.ventas.anterior)}`,
            aviso: x.ordenes_sin_precio
                ? `${x.ordenes_sin_precio} órdenes en $0 (${fmt.moneda(x.no_cobrado)} a precio de lista)`
                : '',
            tendencia: serie.value.map((p) => p.ventas),
            tendenciaFormato: fmt.moneda,
            info: 'Precio de estudios menos descuentos, sin IVA. Fecha = toma de muestra',
        },
        {
            titulo: 'Órdenes',
            valor: fmt.numero(k.value.ordenes.valor),
            delta: k.value.ordenes.delta,
            detalle: `${fmt.numero(k.value.ordenes.valor / Math.max(1, m.value.periodo.dias))} por día en promedio`,
            tendencia: serie.value.map((p) => p.ordenes),
            tendenciaFormato: fmt.numero,
            info: 'Reportes creados en el periodo',
        },
        {
            titulo: 'Ticket promedio',
            valor: fmt.moneda(k.value.ticket.valor),
            delta: k.value.ticket.delta,
            detalle: `${fmt.numero(k.value.estudios_por_orden.valor)} estudios por orden`,
            tendencia: serie.value.map((p) => p.ticket),
            tendenciaFormato: fmt.moneda,
            info: 'Venta neta por orden cobrada (excluye órdenes sin precio)',
        },
        {
            titulo: 'Pacientes únicos',
            valor: fmt.numero(k.value.pacientes.valor),
            delta: k.value.pacientes.delta,
            detalle: `${x.pacientes_nuevos} primera vez · ${fmt.pct(k.value.recurrencia.valor)} con más de una visita`,
            info: 'Nombres distintos en los reportes. Recurrente = tiene más de una visita',
        },
        {
            titulo: 'Utilidad bruta',
            valor: fmt.moneda(k.value.utilidad.valor),
            delta: k.value.utilidad.delta,
            detalle: x.margen !== null ? `Margen ${fmt.pct(x.margen)} en estudios con costo` : 'Sin estudios con costo',
            aviso: x.cobertura_costo < 0.8 ? `Solo ${fmt.pct(x.cobertura_costo)} de la venta tiene costo` : '',
            info: 'Venta neta menos costo, solo de estudios con costo capturado',
        },
        {
            titulo: 'Estudios realizados',
            valor: fmt.numero(k.value.estudios.valor),
            delta: k.value.estudios.delta,
            detalle: `${m.value.estudios.distintos} tipos distintos`,
        },
        {
            titulo: 'Tiempo de entrega',
            valor: fmt.horas(k.value.entrega.valor),
            delta: k.value.entrega.delta,
            sentido: 'abajo',
            detalle: x.entrega_24h !== null ? `${fmt.pct(x.entrega_24h)} validados en < 24 h` : 'Sin fechas de toma/validación',
            aviso: x.pendientes_validar ? `${x.pendientes_validar} órdenes sin validar` : '',
            info: 'Mediana entre la toma de muestra y la validación',
        },
        {
            titulo: 'Resultados fuera de rango',
            valor: fmt.pct(k.value.fuera_rango.valor, 1),
            delta: k.value.fuera_rango.delta,
            sentido: 'neutro',
            detalle: 'Indicador clínico de la población atendida',
            info: 'Porcentaje de exámenes capturados marcados fuera del valor de referencia',
        },
    ];
});

// ---------------------------------------------------------------- Tendencia
const metricaTendencia = ref('ventas');
const compararPrevio = ref(true);
const opcionesMetrica = [
    { valor: 'ventas', label: 'Ventas' },
    { valor: 'ordenes', label: 'Órdenes' },
    { valor: 'ticket', label: 'Ticket' },
];
const formatoMetrica = computed(() => (metricaTendencia.value === 'ordenes' ? fmt.numero : fmt.moneda));
const hayPrevio = computed(() => serie.value.some((p) => p[`prev_${metricaTendencia.value}`]));

const datosTendencia = computed(() => {
    const key = metricaTendencia.value;
    const datasets = [{
        label: 'Periodo actual',
        data: serie.value.map((p) => p[key]),
        borderColor: color.serie1,
        backgroundColor: 'rgba(42, 120, 214, 0.10)',
        fill: true,
        borderWidth: 2,
        tension: 0.3,
        pointRadius: serie.value.length > 40 ? 0 : 3,
        pointHoverRadius: 5,
        pointBackgroundColor: color.serie1,
        pointBorderColor: '#fff',
        spanGaps: true,
    }];
    if (compararPrevio.value) {
        datasets.push({
            label: 'Periodo anterior',
            data: serie.value.map((p) => p[`prev_${key}`]),
            borderColor: color.neutro,
            borderDash: [5, 4],
            borderWidth: 2,
            fill: false,
            tension: 0.3,
            pointRadius: 0,
            pointHoverRadius: 4,
            pointBackgroundColor: color.neutro,
            spanGaps: true,
        });
    }
    return { labels: etiquetasSerie.value, datasets };
});

const opcionesTendencia = computed(() => {
    const o = opcionesBase();
    const f = formatoMetrica.value;
    o.scales.y.ticks.callback = (v) => (metricaTendencia.value === 'ordenes' ? v : fmt.monedaCorta(v));
    o.plugins.tooltip.callbacks = {
        title: (items) => items[0]?.label,
        label: (ctx) => {
            const p = serie.value[ctx.dataIndex];
            if (ctx.datasetIndex === 1) {
                return ` ${p.prev_label ?? 'Anterior'}: ${ctx.raw === null ? '—' : f(ctx.raw)}`;
            }
            return ` Actual: ${ctx.raw === null ? '—' : f(ctx.raw)}`;
        },
    };
    return o;
});

const tablaTendencia = ref(false);

// ---------------------------------------------------------------- Estudios
const ordenEstudios = ref('ventas');
const vistaEstudios = ref('barras');
const listaEstudios = computed(() => (ordenEstudios.value === 'ventas' ? m.value.estudios.por_ventas : m.value.estudios.por_cantidad));
const filasEstudios = computed(() => listaEstudios.value.map((e) => ({
    clave: e.id ?? e.nombre,
    nombre: e.nombre,
    valor: ordenEstudios.value === 'ventas' ? e.ventas : e.cantidad,
    principal: ordenEstudios.value === 'ventas' ? fmt.moneda(e.ventas) : `${e.cantidad}`,
    secundario: ordenEstudios.value === 'ventas' ? `${fmt.pct(e.participacion)} · ${e.cantidad} u.` : fmt.moneda(e.ventas),
})));

// ---------------------------------------------------------------- Medicos
const filasMedicos = computed(() => m.value.medicos.top.map((d) => ({
    clave: d.nombre,
    nombre: d.nombre,
    valor: d.ventas,
    principal: fmt.moneda(d.ventas),
    secundario: `${d.ordenes} órd.`,
    etiqueta: d.registrado ? 'Portal' : '',
})));

// ---------------------------------------------------------------- Demografia
const datosDemografia = computed(() => {
    const s = m.value.demografia.series;
    const colores = { Femenino: color.serie1, Masculino: color.serie2, 'Sin dato': color.neutro };
    return {
        labels: m.value.demografia.grupos,
        datasets: Object.keys(s)
            .filter((sexo) => s[sexo].some((n) => n > 0))
            .map((sexo) => ({
                label: sexo,
                data: s[sexo],
                backgroundColor: colores[sexo],
                borderColor: color.superficie,
                borderWidth: { top: 2 },
                borderSkipped: false,
                borderRadius: 3,
                maxBarThickness: 36,
            })),
    };
});

const opcionesDemografia = computed(() => {
    const o = opcionesBase();
    o.scales.x.stacked = true;
    o.scales.y.stacked = true;
    o.scales.y.ticks.precision = 0;
    o.scales.x.ticks.autoSkip = false;
    o.plugins.legend = {
        display: true,
        position: 'top',
        align: 'end',
        labels: { usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 8, boxHeight: 8, color: color.tinta2, font: { size: 11 } },
    };
    o.plugins.tooltip.callbacks = {
        title: (items) => `Edad ${items[0]?.label}`,
        footer: (items) => `Total: ${items.reduce((a, i) => a + i.raw, 0)}`,
    };
    return o;
});

const resumenDemografia = computed(() => {
    const s = m.value.demografia.series;
    const f = (s.Femenino ?? []).reduce((a, b) => a + b, 0);
    const ma = (s.Masculino ?? []).reduce((a, b) => a + b, 0);
    const tot = f + ma + (s['Sin dato'] ?? []).reduce((a, b) => a + b, 0);
    const porGrupo = m.value.demografia.grupos.map((g, i) => ({ g, n: Object.values(s).reduce((a, arr) => a + arr[i], 0) }))
        .filter((x) => x.g !== 'Sin dato')
        .sort((a, b) => b.n - a.n)[0];
    return { f, ma, tot, top: porGrupo };
});

// ---------------------------------------------------------------- Cotizaciones
const tonosEmbudo = ['#86b6ef', '#3987e5', '#184f95'];
const maxEmbudo = computed(() => Math.max(1, m.value.cotizaciones.etapas[0].n));

// ---------------------------------------------------------------- Mes en curso
const barraMes = computed(() => {
    const h = hoy.value;
    const tope = Math.max(h.proyeccion_mes, h.mes_anterior, 1);
    return {
        actual: (h.ventas_mes / tope) * 100,
        proyeccion: (h.proyeccion_mes / tope) * 100,
        anterior: (h.mes_anterior / tope) * 100,
    };
});

const estiloInsight = {
    bueno: { icono: '✓', clase: 'bg-[#0ca30c]/10 text-[#006300]', etiqueta: 'Positivo' },
    alerta: { icono: '!', clase: 'bg-[#fab219]/20 text-[#8a5a00]', etiqueta: 'Atención' },
    info: { icono: 'i', clase: 'bg-[#2a78d6]/10 text-[#1c5cab]', etiqueta: 'Dato' },
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="m-0 text-xl font-semibold leading-tight text-gray-800">Dashboard de negocio</h2>
        </template>

        <div class="bg-[#f6f6f3] px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-5">

                <!-- Filtros -->
                <div class="sticky top-0 z-20 -mx-1 rounded-xl bg-[#f6f6f3]/90 px-1 py-2 backdrop-blur">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="m-0 text-sm font-semibold text-[#0b0b0b]">
                                {{ textoPeriodo }}
                                <span class="ml-1 font-normal text-[#52514e]">({{ m.periodo.dias }} días, agrupado por {{ nombreGranularidad }})</span>
                            </p>
                            <p class="m-0 text-xs text-[#898781]">
                                <template v-if="k.comparable">Comparado con {{ textoPrevio }}</template>
                                <template v-else>Sin comparativo: el periodo anterior ({{ textoPrevio }}) casi no tiene órdenes</template>
                                <span v-if="m.generado"> · datos de las {{ m.generado }}</span>
                                <button type="button" class="ml-1 text-[#2a78d6] hover:underline" @click="refrescar">actualizar</button>
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex flex-wrap gap-1">
                                <button
                                    v-for="a in atajos"
                                    :key="a.clave"
                                    type="button"
                                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors"
                                    :class="atajoActivo === a.clave
                                        ? 'bg-[#2a78d6] text-white'
                                        : 'bg-white text-[#52514e] ring-1 ring-black/10 hover:bg-[#f0efec]'"
                                    @click="usarAtajo(a)"
                                >{{ a.label }}</button>
                            </div>
                            <div class="flex items-center gap-1 rounded-full bg-white px-2 py-0.5 ring-1 ring-black/10">
                                <input v-model="filtro.fecha_inicio" type="date" aria-label="Fecha inicio"
                                       class="border-0 bg-transparent p-1 text-xs focus:ring-0" @change="aplicar">
                                <span class="text-xs text-[#898781]">→</span>
                                <input v-model="filtro.fecha_fin" type="date" aria-label="Fecha fin"
                                       class="border-0 bg-transparent p-1 text-xs focus:ring-0" @change="aplicar">
                            </div>
                            <span v-if="cargando" class="flex items-center gap-1 text-xs text-[#52514e]">
                                <span class="h-3 w-3 animate-spin rounded-full border-2 border-[#2a78d6] border-t-transparent"></span>
                                Actualizando…
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 transition-opacity" :class="cargando ? 'pointer-events-none opacity-60' : ''">

                    <!-- Pulso de hoy y cierre de mes -->
                    <div class="grid gap-4 rounded-xl bg-gradient-to-r from-[#0d366b] to-[#1c5cab] p-5 text-white shadow-sm md:grid-cols-12">
                        <div class="md:col-span-3">
                            <p class="m-0 text-xs uppercase tracking-wide text-white/70">Hoy · {{ hoy.fecha }}</p>
                            <p class="m-0 mt-1 text-3xl font-bold">{{ fmt.moneda(hoy.ventas_hoy) }}</p>
                            <p class="m-0 text-sm text-white/80">{{ hoy.ordenes_hoy }} {{ hoy.ordenes_hoy === 1 ? 'orden' : 'órdenes' }}</p>
                        </div>
                        <div class="md:col-span-6">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="m-0 text-xs uppercase tracking-wide text-white/70">Cierre de {{ hoy.mes_nombre }}</p>
                                <p class="m-0 text-xs text-white/80">
                                    Proyección <strong class="text-white">{{ fmt.moneda(hoy.proyeccion_mes) }}</strong>
                                    · mes pasado {{ fmt.moneda(hoy.mes_anterior) }}
                                </p>
                            </div>
                            <div class="relative mt-3 h-3 rounded-full bg-white/15" :title="`Avance del mes: ${fmt.pct(hoy.avance_mes)}`">
                                <div class="absolute inset-y-0 left-0 rounded-full bg-white/30" :style="{ width: `${barraMes.proyeccion}%` }"></div>
                                <div class="absolute inset-y-0 left-0 rounded-full bg-white" :style="{ width: `${barraMes.actual}%` }"></div>
                                <div class="absolute -top-1 h-5 w-0.5 bg-[#fab219]" :style="{ left: `${barraMes.anterior}%` }" title="Ventas del mes pasado"></div>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/80">
                                <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-white"></span>Vendido {{ fmt.moneda(hoy.ventas_mes) }} ({{ hoy.ordenes_mes }} órd.)</span>
                                <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-white/40"></span>Proyección al ritmo actual</span>
                                <span><span class="mr-1 inline-block h-2 w-0.5 bg-[#fab219]"></span>Mes pasado</span>
                                <span v-if="hoy.delta_mismo_avance !== null" class="font-semibold text-white">
                                    {{ hoy.delta_mismo_avance >= 0 ? '▲' : '▼' }} {{ fmt.pct(Math.abs(hoy.delta_mismo_avance)) }} vs. mismo día del mes pasado
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col justify-center gap-2 md:col-span-3 md:items-end">
                            <Link
                                :href="route('reportes.index')"
                                class="rounded-lg bg-white/10 px-3 py-2 text-sm text-white no-underline ring-1 ring-white/20 hover:bg-white/20"
                            >
                                <strong>{{ hoy.pendientes_validar }}</strong> sin validar (30 días) →
                            </Link>
                            <Link
                                :href="route('captura.resultados')"
                                class="rounded-lg bg-white px-3 py-2 text-sm font-semibold text-[#0d366b] no-underline hover:bg-[#cde2fb]"
                            >
                                + Capturar resultados
                            </Link>
                        </div>
                    </div>

                    <!-- KPIs -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <TarjetaKpi
                            v-for="t in tarjetas"
                            :key="t.titulo"
                            v-bind="t"
                            :tendencia-etiquetas="etiquetasSerie"
                        />
                    </div>

                    <!-- Tendencia + hallazgos -->
                    <div class="grid gap-5 lg:grid-cols-12">
                        <PanelDashboard
                            class="lg:col-span-8"
                            titulo="Tendencia"
                            :subtitulo="`Por ${nombreGranularidad}, contra el periodo anterior`"
                        >
                            <template #acciones>
                                <SelectorSegmentos v-model="metricaTendencia" :opciones="opcionesMetrica" />
                                <label class="flex cursor-pointer items-center gap-1.5 text-xs text-[#52514e]">
                                    <input v-model="compararPrevio" type="checkbox" class="h-3.5 w-3.5 rounded border-gray-300 text-[#2a78d6] focus:ring-0">
                                    Comparar
                                </label>
                                <button type="button" class="text-xs text-[#2a78d6] hover:underline" @click="tablaTendencia = !tablaTendencia">
                                    {{ tablaTendencia ? 'Ver gráfica' : 'Ver tabla' }}
                                </button>
                            </template>

                            <div v-if="!tablaTendencia" class="h-72">
                                <GraficaChart tipo="line" :datos="datosTendencia" :opciones="opcionesTendencia" etiqueta="Tendencia del periodo" />
                            </div>
                            <div v-else class="max-h-72 overflow-auto">
                                <table class="w-full text-sm tabular-nums">
                                    <thead class="sticky top-0 bg-[#fcfcfb] text-left text-xs text-[#52514e]">
                                        <tr>
                                            <th class="py-1.5 font-medium">Periodo</th>
                                            <th class="py-1.5 text-right font-medium">Ventas</th>
                                            <th class="py-1.5 text-right font-medium">Órdenes</th>
                                            <th class="py-1.5 text-right font-medium">Ticket</th>
                                            <th class="py-1.5 text-right font-medium">Ventas ant.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="p in serie" :key="p.k" class="border-t border-[#e1e0d9]">
                                            <td class="py-1.5">{{ p.label }}</td>
                                            <td class="py-1.5 text-right">{{ fmt.moneda(p.ventas) }}</td>
                                            <td class="py-1.5 text-right">{{ p.ordenes }}</td>
                                            <td class="py-1.5 text-right">{{ p.ticket === null ? '—' : fmt.moneda(p.ticket) }}</td>
                                            <td class="py-1.5 text-right text-[#52514e]">{{ p.prev_ventas === null ? '—' : fmt.moneda(p.prev_ventas) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <template #pie>
                                <div class="flex flex-wrap items-center gap-4">
                                    <span class="flex items-center gap-1.5"><span class="h-0.5 w-4 bg-[#2a78d6]"></span>{{ textoPeriodo }}</span>
                                    <span v-if="compararPrevio" class="flex items-center gap-1.5">
                                        <span class="w-4 border-t-2 border-dashed border-[#b4b2a9]"></span>{{ textoPrevio }}
                                        <em v-if="!hayPrevio" class="text-[#898781]">(sin datos)</em>
                                    </span>
                                </div>
                            </template>
                        </PanelDashboard>

                        <PanelDashboard class="lg:col-span-4" titulo="Hallazgos" subtitulo="Lectura automática del periodo">
                            <ul class="m-0 max-h-[22rem] list-none space-y-3 overflow-y-auto p-0 pr-1">
                                <li v-for="(ins, i) in m.insights" :key="i" class="flex gap-3">
                                    <span
                                        class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                        :class="estiloInsight[ins.tono].clase"
                                        :title="estiloInsight[ins.tono].etiqueta"
                                        :aria-label="estiloInsight[ins.tono].etiqueta"
                                    >{{ estiloInsight[ins.tono].icono }}</span>
                                    <div class="min-w-0">
                                        <p class="m-0 text-sm font-semibold text-[#0b0b0b]">{{ ins.titulo }}</p>
                                        <p class="m-0 mt-0.5 text-xs leading-relaxed text-[#52514e]">{{ ins.texto }}</p>
                                    </div>
                                </li>
                            </ul>
                        </PanelDashboard>
                    </div>

                    <!-- Estudios + medicos -->
                    <div class="grid gap-5 lg:grid-cols-2">
                        <PanelDashboard titulo="Estudios que más venden" :subtitulo="`Top 10 de ${m.estudios.distintos} estudios`">
                            <template #acciones>
                                <SelectorSegmentos
                                    v-model="ordenEstudios"
                                    :opciones="[{ valor: 'ventas', label: 'Por ingreso' }, { valor: 'cantidad', label: 'Por volumen' }]"
                                />
                                <SelectorSegmentos
                                    v-model="vistaEstudios"
                                    :opciones="[{ valor: 'barras', label: 'Barras' }, { valor: 'tabla', label: 'Tabla' }]"
                                />
                            </template>

                            <RankingBarras v-if="vistaEstudios === 'barras'" :filas="filasEstudios" />
                            <div v-else class="overflow-x-auto">
                                <table class="w-full text-sm tabular-nums">
                                    <thead class="text-left text-xs text-[#52514e]">
                                        <tr>
                                            <th class="py-1.5 font-medium">Estudio</th>
                                            <th class="py-1.5 text-right font-medium">Cant.</th>
                                            <th class="py-1.5 text-right font-medium">Ventas</th>
                                            <th class="py-1.5 text-right font-medium">% venta</th>
                                            <th class="py-1.5 text-right font-medium">Precio medio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="e in listaEstudios" :key="e.id ?? e.nombre" class="border-t border-[#e1e0d9]">
                                            <td class="max-w-[14rem] truncate py-1.5" :title="e.nombre">
                                                {{ e.nombre }}
                                                <span v-if="!e.tiene_costo" class="ml-1 text-[10px] text-[#8a5a00]" title="Sin costo capturado">sin costo</span>
                                            </td>
                                            <td class="py-1.5 text-right">{{ e.cantidad }}</td>
                                            <td class="py-1.5 text-right">{{ fmt.moneda(e.ventas) }}</td>
                                            <td class="py-1.5 text-right">{{ fmt.pct(e.participacion, 1) }}</td>
                                            <td class="py-1.5 text-right">{{ fmt.moneda(e.precio_medio) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <template v-if="m.estudios.pareto_80" #pie>
                                <strong class="text-[#0b0b0b]">{{ m.estudios.pareto_80 }} de {{ m.estudios.distintos }}</strong>
                                estudios generan el 80% del ingreso ({{ fmt.moneda(m.estudios.total_ventas) }} en {{ m.estudios.total_cantidad }} estudios).
                            </template>
                        </PanelDashboard>

                        <PanelDashboard
                            titulo="Médicos que refieren"
                            :subtitulo="`${m.medicos.total_medicos} médicos · ${fmt.pct(m.medicos.pct_referidas)} de las órdenes vienen referidas`"
                        >
                            <template #acciones>
                                <Link :href="route('gestion.medicos')" class="text-xs text-[#2a78d6] no-underline hover:underline">Gestionar →</Link>
                            </template>

                            <div class="mb-4 flex h-2.5 overflow-hidden rounded-full bg-[#f0efec]" :title="`${m.medicos.ordenes_referidas} referidas / ${m.medicos.ordenes_particulares} particulares`">
                                <div class="bg-[#2a78d6]" :style="{ width: `${m.medicos.pct_referidas * 100}%` }"></div>
                            </div>
                            <div class="-mt-2 mb-4 flex justify-between text-xs text-[#52514e]">
                                <span>{{ m.medicos.ordenes_referidas }} referidas</span>
                                <span>{{ m.medicos.ordenes_particulares }} particulares</span>
                            </div>

                            <RankingBarras :filas="filasMedicos" vacio="Ningún médico registrado en las órdenes del periodo" />

                            <template #pie>
                                Nombres agrupados sin distinguir mayúsculas ni el prefijo “Dr.”. Da seguimiento a los que más refieren.
                            </template>
                        </PanelDashboard>
                    </div>

                    <!-- Afluencia + demografia -->
                    <div class="grid gap-5 lg:grid-cols-12">
                        <PanelDashboard class="lg:col-span-7" titulo="¿Cuándo llegan los pacientes?" subtitulo="Órdenes por día y hora de toma de muestra">
                            <MapaCalorSemana :matriz="m.heatmap.matriz" />
                        </PanelDashboard>

                        <PanelDashboard class="lg:col-span-5" titulo="Perfil de pacientes" subtitulo="Órdenes por grupo de edad y sexo">
                            <div class="h-60">
                                <GraficaChart tipo="bar" :datos="datosDemografia" :opciones="opcionesDemografia" etiqueta="Órdenes por edad y sexo" />
                            </div>
                            <template v-if="resumenDemografia.tot" #pie>
                                {{ fmt.pct(resumenDemografia.f / resumenDemografia.tot) }} mujeres ·
                                {{ fmt.pct(resumenDemografia.ma / resumenDemografia.tot) }} hombres.
                                <template v-if="resumenDemografia.top?.n">
                                    Grupo principal: <strong class="text-[#0b0b0b]">{{ resumenDemografia.top.g }} años</strong>.
                                </template>
                            </template>
                        </PanelDashboard>
                    </div>

                    <!-- Cotizaciones + accesos -->
                    <div class="grid gap-5 lg:grid-cols-12">
                        <PanelDashboard
                            class="lg:col-span-7"
                            titulo="Cotizaciones"
                            :subtitulo="`${m.cotizaciones.total} emitidas · ${fmt.moneda(m.cotizaciones.monto_total)} cotizados`"
                        >
                            <template #acciones>
                                <Link :href="route('cotizaciones')" class="text-xs text-[#2a78d6] no-underline hover:underline">Ver cotizaciones →</Link>
                            </template>

                            <div v-if="m.cotizaciones.total" class="grid gap-5 md:grid-cols-5">
                                <div class="space-y-2 md:col-span-3">
                                    <div v-for="(e, i) in m.cotizaciones.etapas" :key="e.clave" class="flex items-center gap-3">
                                        <span class="w-20 shrink-0 text-sm text-[#52514e]">{{ e.label }}</span>
                                        <div class="h-7 min-w-0 flex-1 rounded-md bg-[#f0efec]">
                                            <div
                                                class="flex h-7 items-center rounded-md px-2 text-xs font-semibold transition-all duration-500"
                                                :class="i === 0 ? 'text-[#0b0b0b]' : 'text-white'"
                                                :style="{ width: `${Math.max(10, (e.n / maxEmbudo) * 100)}%`, background: tonosEmbudo[i] }"
                                            >{{ e.n }}</div>
                                        </div>
                                        <span class="w-20 shrink-0 text-right text-xs tabular-nums text-[#52514e]">{{ fmt.monedaCorta(e.monto) }}</span>
                                    </div>
                                    <p class="m-0 pt-1 text-xs text-[#898781]">
                                        “Se hicieron”: hay una orden con el mismo nombre en los 45 días siguientes (estimado).
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <p class="m-0 mb-2 text-xs font-medium uppercase tracking-wide text-[#52514e]">Lo más cotizado</p>
                                    <ol class="m-0 list-none space-y-1.5 p-0">
                                        <li v-for="e in m.cotizaciones.top_estudios" :key="e.nombre" class="flex justify-between gap-2 text-sm">
                                            <span class="truncate text-[#0b0b0b]" :title="e.nombre">{{ e.nombre }}</span>
                                            <span class="shrink-0 tabular-nums text-[#52514e]">{{ e.n }}</span>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                            <p v-else class="m-0 py-6 text-center text-sm text-[#898781]">No se emitieron cotizaciones en este periodo.</p>

                            <template v-if="m.cotizaciones.total" #pie>
                                <div class="grid grid-cols-3 gap-3 text-center">
                                    <div>
                                        <p class="m-0 text-lg font-bold text-[#0b0b0b]">{{ fmt.pct(m.cotizaciones.conversion) }}</p>
                                        <p class="m-0">conversión estimada</p>
                                    </div>
                                    <div>
                                        <p class="m-0 text-lg font-bold text-[#0b0b0b]">{{ fmt.moneda(m.cotizaciones.ticket) }}</p>
                                        <p class="m-0">promedio por cotización</p>
                                    </div>
                                    <div>
                                        <p class="m-0 text-lg font-bold" :class="m.cotizaciones.anonimas ? 'text-[#8a5a00]' : 'text-[#0b0b0b]'">{{ m.cotizaciones.anonimas }}</p>
                                        <p class="m-0">sin nombre de paciente</p>
                                    </div>
                                </div>
                            </template>
                        </PanelDashboard>

                        <PanelDashboard class="lg:col-span-5" titulo="Accesos rápidos">
                            <div class="grid grid-cols-2 gap-3">
                                <Link
                                    v-for="a in [
                                        { r: 'captura.resultados', t: 'Capturar resultados', d: 'Nueva orden de trabajo' },
                                        { r: 'cotizaciones', t: 'Cotizar', d: 'Presupuesto para paciente' },
                                        { r: 'reportes.index', t: 'Reportes', d: 'Consultar y reimprimir' },
                                        { r: 'pacientes', t: 'Pacientes', d: 'Expedientes' },
                                        { r: 'gestion.estudios', t: 'Estudios', d: 'Precios y costos' },
                                        { r: 'gestion.medicos', t: 'Médicos', d: 'Portal de referentes' },
                                    ]"
                                    :key="a.r"
                                    :href="route(a.r)"
                                    class="rounded-lg p-3 no-underline ring-1 ring-black/10 transition hover:bg-[#cde2fb]/40 hover:ring-[#2a78d6]"
                                >
                                    <p class="m-0 text-sm font-semibold text-[#0b0b0b]">{{ a.t }}</p>
                                    <p class="m-0 text-xs text-[#52514e]">{{ a.d }}</p>
                                </Link>
                            </div>
                        </PanelDashboard>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
