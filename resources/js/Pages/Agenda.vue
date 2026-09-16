<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TarjetaCita from '@/Components/Agenda/TarjetaCita.vue';
import FormularioCita from '@/Components/Agenda/FormularioCita.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import {
    ESTILOS_ESTADO, ESTADOS_INACTIVOS, moneda, sumarDias, inicioSemana, diaSemana,
    formatoFecha, aMinutos, deMinutos, hoyEn, horaActualEn,
} from '@/utils/agenda';

const props = defineProps({
    hoy: { type: String, required: true },
    config: { type: Object, required: true },
    catalogo: { type: Array, default: () => [] },
    direccionLaboratorio: { type: String, default: '' },
});

const VISTAS = [
    { valor: 'dia', etiqueta: 'Día' },
    { valor: 'semana', etiqueta: 'Semana' },
    { valor: 'proximas', etiqueta: 'Próximas' },
];
const DIAS_PROXIMAS = 30;

// ---------- Vista y fecha (se guardan en la URL para poder recargar) ----------
const parametros = new URLSearchParams(window.location.search);
const vista = ref(VISTAS.some((v) => v.valor === parametros.get('vista')) ? parametros.get('vista') : 'dia');
const fecha = ref(/^\d{4}-\d{2}-\d{2}$/.test(parametros.get('fecha') ?? '') ? parametros.get('fecha') : props.hoy);

watch([vista, fecha], () => {
    const url = new URL(window.location.href);
    url.searchParams.set('vista', vista.value);
    url.searchParams.set('fecha', fecha.value);
    window.history.replaceState(window.history.state, '', url);
});

const rango = computed(() => {
    if (vista.value === 'semana') {
        const lunes = inicioSemana(fecha.value);
        return { desde: lunes, hasta: sumarDias(lunes, 6) };
    }
    if (vista.value === 'proximas') {
        const hoy = hoyEn(props.config.zona_horaria);
        return { desde: hoy, hasta: sumarDias(hoy, DIAS_PROXIMAS) };
    }
    return { desde: fecha.value, hasta: fecha.value };
});

// Solo la primera letra en mayúscula ("Miércoles, 16 de septiembre")
const capitalizar = (texto) => texto.charAt(0).toUpperCase() + texto.slice(1);

const titulo = computed(() => {
    if (vista.value === 'proximas') return `Próximos ${DIAS_PROXIMAS} días`;
    if (vista.value === 'semana') {
        const { desde, hasta } = rango.value;
        return `${formatoFecha(desde, { day: 'numeric', month: 'short' })} – ${formatoFecha(hasta, { day: 'numeric', month: 'short', year: 'numeric' })}`;
    }
    return capitalizar(formatoFecha(fecha.value, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
});

const esHoy = computed(() => fecha.value === hoyEn(props.config.zona_horaria));

const mover = (sentido) => {
    fecha.value = sumarDias(fecha.value, sentido * (vista.value === 'semana' ? 7 : 1));
};

const irAHoy = () => {
    fecha.value = hoyEn(props.config.zona_horaria);
};

const abrirDia = (dia) => {
    fecha.value = dia;
    vista.value = 'dia';
};

// ---------- Datos ----------
const citas = ref([]);
const cargando = ref(true);
const errorCarga = ref('');
let peticion = 0;

const cargar = async () => {
    const actual = ++peticion;
    cargando.value = true;
    errorCarga.value = '';
    try {
        const { data } = await axios.get('/api/citas', { params: rango.value });
        if (actual === peticion) citas.value = data;
    } catch (error) {
        if (actual === peticion) {
            errorCarga.value = error.response?.data?.message || 'No pudimos cargar la agenda. Revisa tu conexión e intenta de nuevo.';
        }
    } finally {
        if (actual === peticion) cargando.value = false;
    }
};

watch(rango, cargar, { immediate: true });

// ---------- Filtros ----------
const filtroEstado = ref('activas');
const busqueda = ref('');

const filtros = computed(() => [
    { valor: 'activas', etiqueta: 'Activas' },
    { valor: 'todas', etiqueta: 'Todas' },
    ...Object.entries(props.config.estados).map(([valor, etiqueta]) => ({ valor, etiqueta })),
].map((f) => ({ ...f, total: contarPorFiltro(f.valor) })));

function contarPorFiltro(valor) {
    if (valor === 'todas') return citas.value.length;
    if (valor === 'activas') return citas.value.filter((c) => !ESTADOS_INACTIVOS.includes(c.estado)).length;
    return citas.value.filter((c) => c.estado === valor).length;
}

const normalizar = (texto) => String(texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const citasFiltradas = computed(() => {
    const termino = normalizar(busqueda.value.trim());
    return citas.value.filter((c) => {
        if (filtroEstado.value === 'activas' && ESTADOS_INACTIVOS.includes(c.estado)) return false;
        if (!['activas', 'todas'].includes(filtroEstado.value) && c.estado !== filtroEstado.value) return false;
        if (!termino) return true;
        return normalizar([c.nombre, c.telefono, c.direccion, ...(c.estudios ?? []).map((e) => e.nombre)].join(' ')).includes(termino);
    });
});

// ---------- Resumen ----------
const resumen = computed(() => {
    const activas = citas.value.filter((c) => !ESTADOS_INACTIVOS.includes(c.estado));
    return {
        activas: activas.length,
        porConfirmar: activas.filter((c) => c.estado === 'programada').length,
        domicilio: activas.filter((c) => c.modalidad === 'domicilio').length,
        ingreso: activas.reduce((suma, c) => suma + Number(c.total_estimado || 0), 0),
    };
});

// ---------- Vista día ----------
const horarioDelDia = computed(() => props.config.horario[diaSemana(fecha.value)] ?? null);

const horas = computed(() => {
    const lista = new Set();
    for (let h = props.config.vista_dia.desde; h < props.config.vista_dia.hasta; h++) lista.add(h);
    citasFiltradas.value.forEach((c) => lista.add(Math.floor(aMinutos(c.hora_inicio) / 60)));
    return [...lista].sort((a, b) => a - b);
});

const citasPorHora = computed(() => {
    const grupos = {};
    citasFiltradas.value.forEach((c) => {
        const hora = Math.floor(aMinutos(c.hora_inicio) / 60);
        (grupos[hora] ??= []).push(c);
    });
    return grupos;
});

const dentroDeHorario = (hora) => {
    if (!horarioDelDia.value) return false;
    return hora * 60 >= aMinutos(horarioDelDia.value[0]) && hora * 60 < aMinutos(horarioDelDia.value[1]);
};

const horaActual = computed(() => (esHoy.value ? Math.floor(aMinutos(horaActualEn(props.config.zona_horaria)) / 60) : null));

// Citas activas que se traslapan con otra el mismo día
const empalmadas = computed(() => {
    const ids = new Set();
    const activas = citas.value.filter((c) => !ESTADOS_INACTIVOS.includes(c.estado));
    activas.forEach((a, i) => {
        activas.slice(i + 1).forEach((b) => {
            if (a.fecha === b.fecha && aMinutos(a.hora_inicio) < aMinutos(b.hora_fin) && aMinutos(b.hora_inicio) < aMinutos(a.hora_fin)) {
                ids.add(a.id);
                ids.add(b.id);
            }
        });
    });
    return ids;
});

// ---------- Vista semana y próximas ----------
const diasSemana = computed(() => Array.from({ length: 7 }, (_, i) => {
    const dia = sumarDias(rango.value.desde, i);
    return {
        fecha: dia,
        citas: citasFiltradas.value.filter((c) => c.fecha === dia),
        cerrado: !props.config.horario[diaSemana(dia)],
        esHoy: dia === hoyEn(props.config.zona_horaria),
    };
}));

const proximasPorDia = computed(() => {
    const grupos = [];
    citasFiltradas.value.forEach((c) => {
        const ultimo = grupos[grupos.length - 1];
        if (ultimo?.fecha === c.fecha) ultimo.citas.push(c);
        else grupos.push({ fecha: c.fecha, citas: [c] });
    });
    return grupos;
});

// ---------- Avisos ----------
const aviso = ref(null);
let temporizadorAviso;
const mostrarAviso = (texto, tipo = 'ok') => {
    aviso.value = { texto, tipo };
    clearTimeout(temporizadorAviso);
    temporizadorAviso = setTimeout(() => { aviso.value = null; }, 5000);
};

// ---------- Formulario ----------
const formulario = ref(null); // { cita, inicial }
const pacientes = ref([]);
const cargandoPacientes = ref(false);
let pacientesCargados = false;

const cargarPacientes = async () => {
    if (pacientesCargados) return;
    cargandoPacientes.value = true;
    try {
        pacientes.value = (await axios.get('/api/pacientes')).data;
        pacientesCargados = true;
    } catch {
        mostrarAviso('No pudimos cargar la lista de pacientes; puedes agendar como "Nuevo o sin registro".', 'error');
    } finally {
        cargandoPacientes.value = false;
    }
};

const nuevaCita = (inicial = {}) => {
    formulario.value = { cita: null, inicial: { fecha: fecha.value, ...inicial } };
    cargarPacientes();
};

const editarCita = (cita) => {
    formulario.value = { cita, inicial: {} };
    cargarPacientes();
};

const alGuardar = (cita, eraEdicion) => {
    formulario.value = null;
    if (!eraEdicion && cita.paciente_id && !pacientes.value.some((p) => p.id === cita.paciente_id)) {
        pacientesCargados = false; // se creó un paciente nuevo
    }
    const cuando = `${formatoFecha(cita.fecha, { weekday: 'long', day: 'numeric', month: 'long' })} a las ${cita.hora_inicio}`;
    mostrarAviso(eraEdicion ? `Cita de ${cita.nombre} actualizada.` : `Cita de ${cita.nombre} agendada para el ${cuando}.`);

    const fueraDeRango = cita.fecha < rango.value.desde || cita.fecha > rango.value.hasta;
    if (fueraDeRango && vista.value !== 'proximas') fecha.value = cita.fecha;
    else cargar();
};

// ---------- Acciones ----------
const cambiarEstado = async (cita, estado) => {
    if (estado === 'cancelada' && !confirm(`¿Cancelar la cita de ${cita.nombre}?`)) return;
    const anterior = cita.estado;
    cita.estado = estado;
    try {
        await axios.patch(`/api/citas/${cita.id}/estado`, { estado });
        mostrarAviso(`Cita de ${cita.nombre}: ${props.config.estados[estado].toLowerCase()}.`);
    } catch (error) {
        cita.estado = anterior;
        mostrarAviso(error.response?.data?.message || 'No se pudo cambiar el estado.', 'error');
    }
};

const atender = async (cita) => {
    try {
        await axios.patch(`/api/citas/${cita.id}/estado`, { estado: 'atendida' });
        router.visit(route('captura.resultados', { cita_id: cita.id }));
    } catch (error) {
        mostrarAviso(error.response?.data?.message || 'No se pudo marcar la cita como atendida.', 'error');
    }
};

const eliminar = async (cita) => {
    if (!confirm(`¿Eliminar la cita de ${cita.nombre}? Si solo no vendrá, mejor márcala como cancelada para conservar el historial.`)) return;
    try {
        await axios.delete(`/api/citas/${cita.id}`);
        citas.value = citas.value.filter((c) => c.id !== cita.id);
        mostrarAviso('Cita eliminada.');
    } catch (error) {
        mostrarAviso(error.response?.data?.message || 'No se pudo eliminar la cita.', 'error');
    }
};
</script>

<template>
    <Head title="Agenda" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Agenda</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- Encabezado -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Citas y tomas de muestra</h1>
                        <p class="text-sm text-gray-500">Agenda visitas al laboratorio y tomas a domicilio, y confírmalas por WhatsApp.</p>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700" @click="nuevaCita()">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Nueva cita
                    </button>
                </div>

                <!-- Navegación -->
                <div class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <template v-if="vista !== 'proximas'">
                                <button type="button" class="rounded-lg border border-gray-300 p-2 text-gray-700 hover:bg-gray-50" @click="mover(-1)">
                                    <span class="sr-only">{{ vista === 'semana' ? 'Semana anterior' : 'Día anterior' }}</span>
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                                </button>
                                <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50" :disabled="esHoy && vista === 'dia'" @click="irAHoy">Hoy</button>
                                <button type="button" class="rounded-lg border border-gray-300 p-2 text-gray-700 hover:bg-gray-50" @click="mover(1)">
                                    <span class="sr-only">{{ vista === 'semana' ? 'Semana siguiente' : 'Día siguiente' }}</span>
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </button>
                                <label for="agenda-fecha" class="sr-only">Ir a la fecha</label>
                                <input id="agenda-fecha" v-model="fecha" type="date" required class="rounded-lg border-gray-300 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </template>
                            <p class="text-lg font-semibold text-gray-900" aria-live="polite">{{ titulo }}</p>
                        </div>

                        <div class="inline-flex self-start rounded-lg border border-gray-300 p-0.5" role="tablist" aria-label="Vista de la agenda">
                            <button
                                v-for="v in VISTAS"
                                :key="v.valor"
                                type="button"
                                role="tab"
                                :aria-selected="vista === v.valor"
                                class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
                                :class="vista === v.valor ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                                @click="vista = v.valor"
                            >{{ v.etiqueta }}</button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 md:flex-row md:items-center">
                        <div class="relative md:w-72">
                            <label for="agenda-buscar" class="sr-only">Buscar en la agenda</label>
                            <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input id="agenda-buscar" v-model="busqueda" type="search" placeholder="Paciente, teléfono o estudio" class="w-full rounded-lg border-gray-300 py-2 pl-10 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar por estado">
                            <button
                                v-for="f in filtros"
                                :key="f.valor"
                                type="button"
                                :aria-pressed="filtroEstado === f.valor"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm transition-colors"
                                :class="filtroEstado === f.valor ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                @click="filtroEstado = f.valor"
                            >
                                <span v-if="ESTILOS_ESTADO[f.valor]" class="h-2 w-2 rounded-full" :class="ESTILOS_ESTADO[f.valor].punto" aria-hidden="true"></span>
                                {{ f.etiqueta }}
                                <span class="rounded-full px-1.5 text-xs" :class="filtroEstado === f.valor ? 'bg-white/20' : 'bg-gray-100 text-gray-600'">{{ f.total }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Resumen del periodo -->
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Citas activas</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ resumen.activas }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Por confirmar</p>
                        <p class="mt-1 text-2xl font-bold" :class="resumen.porConfirmar ? 'text-sky-700' : 'text-gray-900'">{{ resumen.porConfirmar }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">A domicilio</p>
                        <p class="mt-1 text-2xl font-bold text-orange-700">{{ resumen.domicilio }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Ingreso estimado</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-700">{{ moneda.format(resumen.ingreso) }}</p>
                    </div>
                </div>

                <!-- Aviso -->
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

                <!-- Error de carga -->
                <div v-if="errorCarga" role="alert" class="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 sm:flex-row sm:items-center sm:justify-between">
                    <span>{{ errorCarga }}</span>
                    <button type="button" class="rounded-lg bg-white px-3 py-1.5 font-semibold text-red-700 shadow-sm hover:bg-red-100" @click="cargar">Reintentar</button>
                </div>

                <!-- Cargando -->
                <div v-else-if="cargando && !citas.length" class="space-y-3" aria-busy="true">
                    <div v-for="n in 4" :key="n" class="h-20 animate-pulse rounded-xl border border-gray-200 bg-white"></div>
                </div>

                <!-- ===== Vista día ===== -->
                <section v-else-if="vista === 'dia'" aria-label="Citas del día" :aria-busy="cargando" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <p v-if="!horarioDelDia" class="border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900">El laboratorio no abre este día. Aún puedes registrar citas especiales.</p>
                    <p v-else class="border-b border-gray-100 bg-gray-50 px-4 py-2 text-xs text-gray-500">Horario de atención: {{ horarioDelDia[0] }} a {{ horarioDelDia[1] }}</p>

                    <ol class="divide-y divide-gray-100">
                        <li
                            v-for="hora in horas"
                            :key="hora"
                            class="flex flex-col gap-1 px-3 py-2 sm:flex-row sm:gap-4 sm:px-4"
                            :class="[!dentroDeHorario(hora) && 'bg-gray-50/70', hora === horaActual && 'bg-blue-50/60']"
                        >
                            <!-- En móvil la hora va arriba para dejar todo el ancho a las tarjetas -->
                            <div class="flex shrink-0 items-baseline gap-2 pt-1 sm:block sm:w-14 sm:pt-2 sm:text-right">
                                <span class="text-sm font-semibold" :class="dentroDeHorario(hora) ? 'text-gray-700' : 'text-gray-400'">{{ deMinutos(hora * 60) }}</span>
                                <span v-if="hora === horaActual" class="text-[10px] font-bold uppercase text-blue-700 sm:block">Ahora</span>
                            </div>
                            <div class="min-w-0 flex-1 space-y-2 py-1">
                                <TarjetaCita
                                    v-for="cita in citasPorHora[hora] ?? []"
                                    :key="cita.id"
                                    :cita="cita"
                                    :estados="config.estados"
                                    :direccion-laboratorio="direccionLaboratorio"
                                    :ocupada="empalmadas.has(cita.id)"
                                    @editar="editarCita"
                                    @estado="cambiarEstado"
                                    @atender="atender"
                                    @eliminar="eliminar"
                                />
                                <button
                                    v-if="dentroDeHorario(hora) || (citasPorHora[hora] ?? []).length === 0 && horarioDelDia === null"
                                    type="button"
                                    class="w-full rounded-lg border border-dashed border-gray-300 px-3 py-2 text-left text-sm text-gray-500 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    @click="nuevaCita({ hora: deMinutos(hora * 60) })"
                                >+ Agendar a las {{ deMinutos(hora * 60) }}</button>
                            </div>
                        </li>
                    </ol>
                </section>

                <!-- ===== Vista semana ===== -->
                <section v-else-if="vista === 'semana'" aria-label="Citas de la semana" :aria-busy="cargando" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
                    <div
                        v-for="dia in diasSemana"
                        :key="dia.fecha"
                        class="flex min-h-[10rem] flex-col rounded-xl border bg-white shadow-sm"
                        :class="dia.esHoy ? 'border-blue-400 ring-1 ring-blue-400' : 'border-gray-200'"
                    >
                        <button
                            type="button"
                            class="flex items-baseline justify-between gap-2 rounded-t-xl border-b border-gray-100 px-3 py-2 text-left hover:bg-gray-50"
                            :class="dia.cerrado && 'bg-gray-50'"
                            @click="abrirDia(dia.fecha)"
                        >
                            <span>
                                <span class="block text-xs font-semibold uppercase text-gray-500">{{ formatoFecha(dia.fecha, { weekday: 'short' }) }}</span>
                                <span class="text-lg font-bold" :class="dia.esHoy ? 'text-blue-700' : 'text-gray-900'">{{ formatoFecha(dia.fecha, { day: 'numeric' }) }}</span>
                            </span>
                            <span class="text-xs text-gray-500">{{ dia.cerrado ? 'Cerrado' : `${dia.citas.length} ${dia.citas.length === 1 ? 'cita' : 'citas'}` }}</span>
                            <span class="sr-only">Ver el día completo</span>
                        </button>
                        <div class="flex flex-1 flex-col gap-2 p-2">
                            <TarjetaCita
                                v-for="cita in dia.citas"
                                :key="cita.id"
                                :cita="cita"
                                :estados="config.estados"
                                compacta
                                @editar="editarCita"
                            />
                            <button
                                v-if="!dia.cerrado"
                                type="button"
                                class="mt-auto rounded-lg border border-dashed border-gray-300 px-2 py-1.5 text-xs text-gray-500 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700"
                                @click="nuevaCita({ fecha: dia.fecha })"
                            >+ Agendar<span class="sr-only"> el {{ formatoFecha(dia.fecha, { weekday: 'long', day: 'numeric' }) }}</span></button>
                        </div>
                    </div>
                </section>

                <!-- ===== Vista próximas ===== -->
                <section v-else aria-label="Próximas citas" :aria-busy="cargando" class="space-y-6">
                    <div v-if="!proximasPorDia.length" class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                        <p class="font-semibold text-gray-800">No hay citas {{ filtroEstado === 'activas' ? 'activas' : 'con este filtro' }} en los próximos {{ DIAS_PROXIMAS }} días</p>
                        <p class="mt-1 text-sm text-gray-500">Cuando un paciente escriba por WhatsApp, agéndalo aquí para no perderlo.</p>
                        <button type="button" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700" @click="nuevaCita({ fecha: hoy })">Agendar una cita</button>
                    </div>
                    <div v-for="grupo in proximasPorDia" :key="grupo.fecha" class="space-y-2">
                        <h3 class="flex items-baseline justify-between gap-3 text-sm font-semibold text-gray-700">
                            <button type="button" class="hover:text-blue-700 hover:underline" @click="abrirDia(grupo.fecha)">
                                {{ capitalizar(formatoFecha(grupo.fecha, { weekday: 'long', day: 'numeric', month: 'long' })) }}
                            </button>
                            <span class="text-xs font-normal text-gray-500">{{ grupo.citas.length }} {{ grupo.citas.length === 1 ? 'cita' : 'citas' }}</span>
                        </h3>
                        <TarjetaCita
                            v-for="cita in grupo.citas"
                            :key="cita.id"
                            :cita="cita"
                            :estados="config.estados"
                            :direccion-laboratorio="direccionLaboratorio"
                            :ocupada="empalmadas.has(cita.id)"
                            @editar="editarCita"
                            @estado="cambiarEstado"
                            @atender="atender"
                            @eliminar="eliminar"
                        />
                    </div>
                </section>

                <!-- Día sin resultados de búsqueda -->
                <p v-if="!cargando && !errorCarga && vista === 'dia' && citas.length && !citasFiltradas.length" class="text-center text-sm text-gray-500">
                    Ninguna cita de este día coincide con el filtro o la búsqueda.
                </p>
            </div>
        </div>

        <Teleport to="body">
            <FormularioCita
                v-if="formulario"
                :cita="formulario.cita"
                :inicial="formulario.inicial"
                :config="config"
                :catalogo="catalogo"
                :pacientes="pacientes"
                :cargando-pacientes="cargandoPacientes"
                @cerrar="formulario = null"
                @guardada="alGuardar"
            />
        </Teleport>
    </AuthenticatedLayout>
</template>
