<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import FormularioJornada from '@/Components/CapturaRapida/FormularioJornada.vue';
import PegarLista from '@/Components/CapturaRapida/PegarLista.vue';
import CeldaResultado from '@/Components/CapturaRapida/CeldaResultado.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { opcionesCualitativas, esFueraDeRango } from '@/utils/rangoReferencia';
import { normalizar, calcularEdad } from '@/utils/capturaRapida';
import { descargarRespuesta } from '@/utils/descargas';

const props = defineProps({
    jornada: { type: Object, required: true },
    participantes: { type: Array, default: () => [] },
    catalogo: { type: Array, required: true },
    medicos: { type: Array, default: () => [] },
});

const jornada = ref(props.jornada);
const participantes = ref(props.participantes);
const base = computed(() => `/api/jornadas/${jornada.value.id}`);

// ---------- Avisos flotantes (no empujan el contenido) ----------
const avisos = ref([]);
let siguienteAviso = 0;

const cerrarAviso = (id) => {
    avisos.value = avisos.value.filter((a) => a.id !== id);
};

const mostrarAviso = (texto, tipo = 'exito') => {
    const id = ++siguienteAviso;
    // Máximo 3 a la vez; los errores se quedan hasta que se cierran
    avisos.value = [...avisos.value.slice(-2), { id, texto, tipo }];
    if (tipo === 'exito') setTimeout(() => cerrarAviso(id), 5000);
};

const mensajeError = (error, respaldo) => {
    const errores = error.response?.data?.errors;
    if (errores) return Object.values(errores).flat()[0];
    if (!error.response) return 'Sin conexión con el servidor. Revisa el internet y vuelve a intentar.';
    return error.response.data?.message || respaldo;
};

// ---------- Diálogo de confirmación (reemplaza confirm() del navegador) ----------
const dialogo = reactive({ abierto: false, titulo: '', mensaje: '', detalles: [], confirmar: 'Continuar', peligro: false });
let resolverDialogo = null;

const preguntar = (opciones) => new Promise((resolve) => {
    Object.assign(dialogo, { detalles: [], peligro: false, confirmar: 'Continuar', ...opciones, abierto: true });
    resolverDialogo = resolve;
});

const cerrarDialogo = (respuesta) => {
    dialogo.abierto = false;
    resolverDialogo?.(respuesta);
    resolverDialogo = null;
};

// ---------- Columnas: un examen por columna ----------
const columnas = computed(() => jornada.value.estudios.flatMap((estudio) => estudio.examenes.map((examen) => ({
    ...examen,
    estudio_id: estudio.estudio_id,
    opciones: opcionesCualitativas(examen.valor_referencia),
}))));

const variosEstudios = computed(() => jornada.value.estudios.length > 1);

// Exámenes agrupados por estudio para el panel de la vista compacta
const grupos = computed(() => jornada.value.estudios.map((estudio) => ({
    id: estudio.estudio_id,
    nombre: estudio.nombre,
    columnas: columnas.value.filter((c) => c.estudio_id === estudio.estudio_id),
})));

// ---------- Vista completa (una columna por examen) o compacta (resumen + panel) ----------
const LLAVE_VISTA = 'captura-rapida-vista';
const leerVista = () => {
    try {
        return localStorage.getItem(LLAVE_VISTA);
    } catch {
        return null;
    }
};
// Con muchos exámenes la tabla completa obliga a desplazarse de lado
const vista = ref(['completa', 'compacta'].includes(leerVista()) ? leerVista() : (columnas.value.length > 6 ? 'compacta' : 'completa'));
const compacta = computed(() => vista.value === 'compacta');

const cambiarVista = (valor) => {
    vista.value = valor;
    try {
        localStorage.setItem(LLAVE_VISTA, valor);
    } catch {
        // Sin almacenamiento solo no se recuerda la preferencia
    }
};

const expandido = ref(null);
// Panel que el usuario cerró a mano aunque la búsqueda lo hubiera abierto solo
const ocultado = ref(null);

const alternarPanel = (p) => {
    if (panelAbierto(p)) {
        expandido.value = null;
        ocultado.value = p.id;
    } else {
        expandido.value = p.id;
        ocultado.value = null;
    }
};

// Columnas de la tabla: #, colaborador, resultados (1 o N), estado y acciones
const totalColumnas = computed(() => (compacta.value ? 5 : columnas.value.length + 4));

// Lo que no se ha tocado toma el resultado por defecto de la jornada
// (un resultado vaciado a propósito vuelve del servidor como null)
const valorDe = (p, columna) => (p.resultados && columna.id in p.resultados
    ? (p.resultados[columna.id] ?? '')
    : (columna.predeterminado ?? ''));
const esAnormal = (p, columna) => esFueraDeRango(valorDe(p, columna), columna.valor_referencia);
const tieneAnormales = (p) => columnas.value.some((c) => esAnormal(p, c));

// 'pendiente' | 'listo' | 'reportado'
const etapa = (p) => (p.reporte ? 'reportado' : (p.estado || 'pendiente'));

// ---------- Progreso y filtros ----------
const resumen = computed(() => {
    const r = { total: 0, pendiente: 0, listo: 0, reportado: 0, anormales: 0 };
    for (const p of participantes.value) {
        r.total++;
        r[etapa(p)]++;
        if (etapa(p) !== 'pendiente' && tieneAnormales(p)) r.anormales++;
    }
    return r;
});

const capturados = computed(() => resumen.value.listo + resumen.value.reportado);
const porcentaje = (parte) => (resumen.value.total ? (parte / resumen.value.total) * 100 : 0);

const filtro = ref('todos');
const busqueda = ref('');
const buscador = ref(null);

// La leyenda de la barra de progreso es también el filtro
const filtros = computed(() => [
    { valor: 'todos', etiqueta: 'Todos', total: resumen.value.total, punto: null },
    { valor: 'pendiente', etiqueta: 'Por capturar', total: resumen.value.pendiente, punto: 'bg-gray-300' },
    { valor: 'listo', etiqueta: 'Listos para reporte', total: resumen.value.listo, punto: 'bg-emerald-500' },
    { valor: 'reportado', etiqueta: 'Con reporte', total: resumen.value.reportado, punto: 'bg-blue-600' },
    { valor: 'anormales', etiqueta: 'Fuera de rango', total: resumen.value.anormales, punto: 'bg-red-600' },
]);

const numeros = computed(() => new Map(participantes.value.map((p, i) => [p.id, i + 1])));

const visibles = computed(() => {
    // Cada palabra por separado: "perez juan" encuentra a "Juan Pérez López"
    const palabras = normalizar(busqueda.value).split(/\s+/).filter(Boolean);
    return participantes.value.filter((p) => {
        const nombre = normalizar(p.nombre);
        if (!palabras.every((palabra) => nombre.includes(palabra))) return false;
        if (filtro.value === 'todos') return true;
        if (filtro.value === 'anormales') return etapa(p) !== 'pendiente' && tieneAnormales(p);
        return etapa(p) === filtro.value;
    });
});

// Vista compacta: si la búsqueda deja a una sola persona, su panel se abre solo
const panelAbierto = (p) => expandido.value === p.id || (visibles.value.length === 1 && ocultado.value !== p.id);

// Con una búsqueda nueva vuelve a aplicar la apertura automática
watch(busqueda, () => {
    ocultado.value = null;
});
const anormalesDe = (p) => columnas.value.filter((c) => esAnormal(p, c));

// ---------- Guardado por renglón ----------
// Cada colaborador tiene su propia cola para que los cambios lleguen en orden
const guardandoFila = reactive({});
const errorFila = reactive({});
const colas = new Map();

const guardarParticipante = (p, cambios) => {
    guardandoFila[p.id] = (guardandoFila[p.id] ?? 0) + 1;

    const tarea = (colas.get(p.id) ?? Promise.resolve()).then(async () => {
        try {
            const { data } = await axios.patch(`${base.value}/participantes/${p.id}`, cambios);
            // Los resultados locales mandan: pudieron cambiar mientras viajaba la petición
            const { resultados, ...resto } = data.participante;
            Object.assign(p, resto);
            delete errorFila[p.id];
            return true;
        } catch (error) {
            // Un 422 (p. ej. falta el sexo) no se arregla reintentando: hay que corregir el dato
            const validacion = error.response?.status === 422;
            errorFila[p.id] = { mensaje: mensajeError(error, 'No se guardó el cambio.'), cambios, validacion };
            return false;
        } finally {
            guardandoFila[p.id]--;
        }
    });

    colas.set(p.id, tarea);
    return tarea;
};

const reintentar = (p) => guardarParticipante(p, errorFila[p.id].cambios);
const esperarGuardados = () => Promise.all(colas.values());
const guardandoAlgo = computed(() => Object.values(guardandoFila).some((n) => n > 0));
// Solo cuentan los que fallaron por conexión o servidor; los de validación se corrigen en su renglón
const sinGuardar = computed(() => Object.values(errorFila).filter((e) => !e.validacion).length);

const reintentarTodo = () => {
    participantes.value.filter((p) => errorFila[p.id] && !errorFila[p.id].validacion).forEach(reintentar);
};

// ---------- Conexión y estado de guardado (siempre visible) ----------
const enLinea = ref(navigator.onLine);

const alConectar = () => {
    enLinea.value = true;
    if (sinGuardar.value) reintentarTodo();
};
const alDesconectar = () => {
    enLinea.value = false;
};

const estadoGuardado = computed(() => {
    if (!enLinea.value) return { tipo: 'error', texto: 'Sin internet: los cambios no se guardan' };
    if (guardandoAlgo.value) return { tipo: 'guardando', texto: 'Guardando…' };
    if (sinGuardar.value) return { tipo: 'error', texto: sinGuardar.value === 1 ? '1 cambio sin guardar' : `${sinGuardar.value} cambios sin guardar` };
    return { tipo: 'ok', texto: 'Todo guardado' };
});

// Evita cerrar la pestaña con cambios todavía en camino
const avisarSalida = (evento) => {
    if (guardandoAlgo.value || sinGuardar.value) {
        evento.preventDefault();
        evento.returnValue = '';
    }
};

// Atajo: "/" enfoca el buscador desde cualquier parte de la página
const atajos = (evento) => {
    const escribiendo = ['INPUT', 'TEXTAREA', 'SELECT'].includes(evento.target.tagName);
    const ventanaAbierta = dialogo.abierto || mostrarPegar.value || mostrarConfig.value;
    if (evento.key === '/' && !escribiendo && !ventanaAbierta) {
        evento.preventDefault();
        buscador.value?.focus();
    }
};

onMounted(() => {
    window.addEventListener('beforeunload', avisarSalida);
    window.addEventListener('online', alConectar);
    window.addEventListener('offline', alDesconectar);
    document.addEventListener('keydown', atajos);
});
onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', avisarSalida);
    window.removeEventListener('online', alConectar);
    window.removeEventListener('offline', alDesconectar);
    document.removeEventListener('keydown', atajos);
});

// ---------- Captura ----------
const alternar = (p, columna) => {
    if (p.reporte) return;
    const [normal, anormal] = columna.opciones;
    const actual = normalizar(valorDe(p, columna));
    p.resultados = { ...p.resultados, [columna.id]: actual === normalizar(normal) ? anormal : normal };
    guardarParticipante(p, { resultados: p.resultados });
};

const escribir = (p, columna, valor) => {
    const limpio = valor.trim();
    if (p.reporte || limpio === valorDe(p, columna)) return;
    p.resultados = { ...p.resultados, [columna.id]: limpio };
    guardarParticipante(p, { resultados: p.resultados });
};

const faltanDatos = (p) => !p.sexo || (p.edad === null && !p.fecha_nacimiento);

const marcarListo = async (p) => {
    expandido.value = null;
    // Se congelan los valores por defecto vigentes al momento de capturar
    p.resultados = Object.fromEntries(columnas.value.map((c) => [c.id, valorDe(p, c)]));
    const guardado = await guardarParticipante(p, { resultados: p.resultados, estado: 'listo' });

    if (!guardado) {
        if (faltanDatos(p)) abrirEdicion(p);
        return;
    }

    // Listo para buscar al siguiente colaborador
    if (busqueda.value) {
        busqueda.value = '';
        nextTick(() => buscador.value?.focus());
    }
};

const deshacerListo = (p) => guardarParticipante(p, { estado: 'pendiente' });

// ---------- Editar / quitar colaborador ----------
const edicion = reactive({ id: null, nombre: '', sexo: '', fecha_nacimiento: '', edad: '' });

const abrirEdicion = (p) => {
    Object.assign(edicion, {
        id: p.id,
        nombre: p.nombre,
        sexo: p.sexo ?? '',
        fecha_nacimiento: p.fecha_nacimiento ?? '',
        edad: p.edad ?? '',
    });
};

const guardarEdicion = async (p) => {
    const guardado = await guardarParticipante(p, {
        nombre: edicion.nombre,
        sexo: edicion.sexo || null,
        fecha_nacimiento: edicion.fecha_nacimiento || null,
        edad: edicion.edad === '' || edicion.edad === null ? null : Number(edicion.edad),
    });
    if (guardado) edicion.id = null;
};

const quitar = async (p) => {
    const confirmado = await preguntar({
        titulo: `¿Quitar a ${p.nombre}?`,
        mensaje: 'Se borra de la lista de esta jornada junto con lo que se le haya capturado.',
        confirmar: 'Quitar de la jornada',
        peligro: true,
    });
    if (!confirmado) return;

    try {
        await axios.delete(`${base.value}/participantes/${p.id}`);
        participantes.value = participantes.value.filter((x) => x.id !== p.id);
        delete errorFila[p.id];
        mostrarAviso(`${p.nombre} se quitó de la jornada.`);
    } catch (error) {
        mostrarAviso(mensajeError(error, 'No se pudo quitar al colaborador.'), 'error');
    }
};

// ---------- Agregar colaboradores ----------
const nuevo = reactive({ nombre: '', sexo: '', fecha_nacimiento: '', edad: '' });
const campoNombre = ref(null);
const agregando = ref(false);
const mostrarPegar = ref(false);

// Con fecha de nacimiento la edad se muestra calculada a la fecha de la toma
// (el servidor hace el mismo cálculo al guardar)
const edadALaToma = (fecha) => calcularEdad(fecha, new Date(jornada.value.fecha_toma)) ?? '';
watch(() => nuevo.fecha_nacimiento, (fecha) => {
    if (fecha) nuevo.edad = edadALaToma(fecha);
});
watch(() => edicion.fecha_nacimiento, (fecha) => {
    if (fecha) edicion.edad = edadALaToma(fecha);
});

const agregarLista = async (lista) => {
    agregando.value = true;
    try {
        const { data } = await axios.post(`${base.value}/participantes`, { participantes: lista });
        participantes.value.push(...data.participantes);
        return data;
    } catch (error) {
        mostrarAviso(mensajeError(error, 'No se pudieron agregar los colaboradores.'), 'error');
        return null;
    } finally {
        agregando.value = false;
    }
};

const agregarUno = async () => {
    if (!nuevo.nombre.trim()) {
        campoNombre.value?.focus();
        return;
    }

    const data = await agregarLista([{
        nombre: nuevo.nombre,
        sexo: nuevo.sexo || null,
        fecha_nacimiento: nuevo.fecha_nacimiento || null,
        edad: nuevo.edad === '' ? null : Number(nuevo.edad),
    }]);
    if (!data) return;

    // Queda a la vista para capturarlo de inmediato
    busqueda.value = data.participantes[0].nombre;
    filtro.value = 'todos';
    Object.assign(nuevo, { nombre: '', sexo: '', fecha_nacimiento: '', edad: '' });
    mostrarAviso(`${data.participantes[0].nombre} agregado. Ya puedes capturarlo abajo.`);
    nextTick(() => campoNombre.value?.focus());
};

const agregarDesdeBusqueda = () => {
    nuevo.nombre = busqueda.value.trim();
    nextTick(() => {
        campoNombre.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        campoNombre.value?.focus();
    });
};

const agregarPegados = async (lista) => {
    const data = await agregarLista(lista);
    if (data) {
        mostrarPegar.value = false;
        mostrarAviso(data.message);
    }
};

// ---------- Reportes ----------
const generando = ref(false);

const generarReportes = async (ids = null) => {
    const porEmitir = participantes.value.filter((p) => etapa(p) === 'listo' && (!ids || ids.includes(p.id)));
    if (!porEmitir.length) return;

    const conAnormales = porEmitir.filter(tieneAnormales).length;
    const confirmado = await preguntar({
        titulo: porEmitir.length === 1 ? `¿Emitir el reporte de ${porEmitir[0].nombre}?` : `¿Emitir ${porEmitir.length} reportes?`,
        mensaje: 'Cada reporte se crea con folio consecutivo, código QR y la fecha de hoy, y aparece en Lista de reportes.',
        detalles: [
            conAnormales ? `${conAnormales} con resultados fuera de rango.` : 'Ninguno tiene resultados fuera de rango.',
            'Después de emitirlos, las correcciones se hacen desde Lista de reportes.',
        ],
        confirmar: porEmitir.length === 1 ? 'Emitir reporte' : `Emitir ${porEmitir.length} reportes`,
    });
    if (!confirmado) return;

    generando.value = true;
    try {
        await esperarGuardados();
        const { data } = await axios.post(`${base.value}/reportes`, { participantes: ids });
        participantes.value = data.participantes;
        if (data.omitidos.length) {
            mostrarAviso(`${data.message}. No se emitieron: ${data.omitidos.join('; ')}.`, 'error');
        } else {
            mostrarAviso(`${data.message}. Ya aparecen en Lista de reportes.`);
        }
    } catch (error) {
        mostrarAviso(mensajeError(error, 'No se pudieron generar los reportes.'), 'error');
    } finally {
        generando.value = false;
    }
};

const descarga = reactive({ activa: false, hechas: 0, total: 0, cancelar: false });
const visiblesConReporte = computed(() => visibles.value.filter((p) => p.reporte));

const descargarPdfs = async () => {
    const lista = visiblesConReporte.value;
    if (!lista.length) return;

    Object.assign(descarga, { activa: true, hechas: 0, total: lista.length, cancelar: false });
    try {
        for (const p of lista) {
            if (descarga.cancelar) break;
            const respuesta = await axios.get(`/api/reportes/${p.reporte.id}/pdf`, { responseType: 'blob' });
            descargarRespuesta(respuesta, `${p.nombre} - ${p.reporte.folio}.pdf`);
            descarga.hechas++;
        }
        mostrarAviso(`${descarga.hechas} PDF descargados.`);
    } catch {
        mostrarAviso(`La descarga se detuvo en ${descarga.hechas + 1} de ${descarga.total}. Vuelve a intentarlo.`, 'error');
    } finally {
        descarga.activa = false;
    }
};

const verPdf = (p) => window.open(`/api/reportes/${p.reporte.id}/pdf-preview`, '_blank');

// ---------- Configuración ----------
const mostrarConfig = ref(false);

const configGuardada = (datos) => {
    jornada.value = datos;
    mostrarConfig.value = false;
    mostrarAviso('Configuración guardada.');
};

// ---------- Presentación ----------
const fechaToma = computed(() => new Date(jornada.value.fecha_toma).toLocaleString('es-MX', {
    weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
}));

// Fondo suave + franja de color a la izquierda según la etapa del colaborador
const fondoFila = (p) => {
    if (errorFila[p.id]) return 'bg-red-50';
    if (etapa(p) === 'reportado') return 'bg-slate-50';
    // Colores opacos: la columna del nombre es fija y no debe transparentar lo que pasa debajo
    if (etapa(p) === 'listo') return 'bg-emerald-50';
    return 'bg-white';
};

const franjaFila = (p) => {
    if (errorFila[p.id]) return 'border-l-red-500';
    if (etapa(p) === 'reportado') return 'border-l-blue-600';
    if (etapa(p) === 'listo') return 'border-l-emerald-500';
    return 'border-l-transparent';
};

const claseCampo = 'w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500';
const claseBotonSecundario = 'inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:opacity-50';
</script>

<template>
    <Head :title="jornada.nombre" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Captura rápida</h2>
        </template>

        <!-- relative: los textos sr-only (absolute) de cada renglón quedan dentro del área con scroll;
             sin esto alargan la ventana y aparece una segunda barra de desplazamiento -->
        <div class="relative pt-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- Encabezado -->
                <header class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 space-y-2">
                        <Link :href="route('captura.rapida')" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            Jornadas
                        </Link>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">{{ jornada.nombre }}</h1>
                        <dl class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-600">
                            <div v-if="jornada.empresa" class="flex items-center gap-1.5">
                                <dt class="sr-only">Empresa</dt>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <dd>{{ jornada.empresa }}</dd>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <dt class="sr-only">Toma de muestra</dt>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <dd>{{ fechaToma }}</dd>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <dt class="sr-only">Médico solicitante</dt>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                <dd>{{ jornada.medico_solicitante || 'A quien corresponda' }}</dd>
                            </div>
                        </dl>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="estudio in jornada.estudios" :key="estudio.estudio_id" class="rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-200">{{ estudio.nombre }}</span>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                        <!-- Estado de guardado: siempre a la vista -->
                        <div
                            role="status"
                            aria-live="polite"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium"
                            :class="{
                                'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200': estadoGuardado.tipo === 'ok',
                                'bg-blue-50 text-blue-800 ring-1 ring-inset ring-blue-200': estadoGuardado.tipo === 'guardando',
                                'bg-red-50 text-red-800 ring-1 ring-inset ring-red-200': estadoGuardado.tipo === 'error',
                            }"
                        >
                            <svg v-if="estadoGuardado.tipo === 'guardando'" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <svg v-else-if="estadoGuardado.tipo === 'ok'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" /></svg>
                            {{ estadoGuardado.texto }}
                            <button v-if="sinGuardar && enLinea" type="button" class="font-semibold underline" @click="reintentarTodo">Reintentar</button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" :class="claseBotonSecundario" @click="mostrarConfig = true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.3 4.3c.4-1.7 3-1.7 3.4 0a1.7 1.7 0 002.6 1.1c1.5-.9 3.3.8 2.4 2.4a1.7 1.7 0 001 2.5c1.8.4 1.8 3 0 3.4a1.7 1.7 0 00-1 2.6c.9 1.5-.9 3.3-2.4 2.4a1.7 1.7 0 00-2.6 1c-.4 1.8-3 1.8-3.4 0a1.7 1.7 0 00-2.5-1c-1.6.9-3.4-.9-2.4-2.4a1.7 1.7 0 00-1.1-2.6c-1.7-.4-1.7-3 0-3.4a1.7 1.7 0 001.1-2.5c-1-1.6.8-3.4 2.4-2.4 1 .6 2.3.1 2.5-1.1z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Configurar
                            </button>
                            <a :href="`${base}/csv`" :class="claseBotonSecundario">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Exportar a Excel
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Sin internet -->
                <div v-if="!enLinea" role="alert" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.4 5.6a9 9 0 010 12.8m-2.8-2.8a5 5 0 000-7.1M3 3l18 18M8.5 8.5a5 5 0 000 7.1M5.6 18.4a9 9 0 010-12.8" /></svg>
                    <p><strong>Se perdió la conexión a internet.</strong> Anota los resultados en papel; cuando regrese la señal, los cambios pendientes se vuelven a enviar solos.</p>
                </div>

                <!-- Progreso: la leyenda también filtra la tabla -->
                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="titulo-progreso">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <h3 id="titulo-progreso" class="text-sm font-medium text-gray-500">Avance de la jornada</h3>
                        <p class="text-sm text-gray-600">
                            <span class="text-2xl font-bold tabular-nums text-gray-900">{{ capturados }}</span>
                            de <span class="tabular-nums">{{ resumen.total }}</span> capturados
                        </p>
                    </div>
                    <div
                        class="mt-3 flex h-3 overflow-hidden rounded-full bg-gray-100"
                        role="progressbar"
                        :aria-valuenow="capturados"
                        aria-valuemin="0"
                        :aria-valuemax="resumen.total"
                        :aria-label="`${capturados} de ${resumen.total} capturados`"
                    >
                        <div class="bg-blue-600 transition-all duration-500" :style="{ width: `${porcentaje(resumen.reportado)}%` }"></div>
                        <div class="bg-emerald-500 transition-all duration-500" :style="{ width: `${porcentaje(resumen.listo)}%` }"></div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Filtrar colaboradores">
                        <button
                            v-for="f in filtros"
                            :key="f.valor"
                            type="button"
                            :aria-pressed="filtro === f.valor"
                            class="inline-flex min-h-[2.25rem] items-center gap-2 rounded-full border px-3 text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            :class="filtro === f.valor ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                            @click="filtro = f.valor"
                        >
                            <span v-if="f.punto" class="h-2.5 w-2.5 rounded-full" :class="f.punto" aria-hidden="true"></span>
                            {{ f.etiqueta }}
                            <span class="font-semibold tabular-nums" :class="f.valor === 'anormales' && f.total && filtro !== f.valor ? 'text-red-600' : ''">{{ f.total }}</span>
                        </button>
                    </div>
                </section>

                <!-- Agregar colaborador -->
                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="titulo-agregar">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 id="titulo-agregar" class="text-base font-semibold text-gray-900">Agregar colaborador</h3>
                            <p class="text-sm text-gray-500">Presiona Enter para agregarlo y seguir con el siguiente.</p>
                        </div>
                        <button type="button" :class="claseBotonSecundario" @click="mostrarPegar = true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            Pegar lista de Excel
                        </button>
                    </div>
                    <form class="grid grid-cols-2 gap-3 md:grid-cols-12 md:items-end" @submit.prevent="agregarUno">
                        <div class="col-span-2 md:col-span-4">
                            <label for="nuevo-nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre completo <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="nuevo-nombre" ref="campoNombre" v-model="nuevo.nombre" type="text" required autocomplete="off" placeholder="Apellidos y nombre" :class="claseCampo" />
                        </div>
                        <fieldset class="col-span-2 sm:col-span-1 md:col-span-3">
                            <legend class="mb-1 block text-sm font-medium text-gray-700">Sexo</legend>
                            <div class="grid grid-cols-2 gap-1 rounded-lg border border-gray-300 p-0.5">
                                <label
                                    v-for="opcion in [{ valor: 'Masculino', texto: 'Hombre' }, { valor: 'Femenino', texto: 'Mujer' }]"
                                    :key="opcion.valor"
                                    class="cursor-pointer rounded-md px-2 py-1.5 text-center text-sm font-medium transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-500"
                                    :class="nuevo.sexo === opcion.valor ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                                >
                                    <input v-model="nuevo.sexo" type="radio" name="nuevo-sexo" :value="opcion.valor" class="sr-only" />
                                    {{ opcion.texto }}
                                </label>
                            </div>
                        </fieldset>
                        <div class="md:col-span-2">
                            <label for="nuevo-fecha" class="mb-1 block text-sm font-medium text-gray-700">F. nacimiento</label>
                            <input id="nuevo-fecha" v-model="nuevo.fecha_nacimiento" type="date" :class="claseCampo" />
                        </div>
                        <div class="md:col-span-1">
                            <label for="nuevo-edad" class="mb-1 block text-sm font-medium text-gray-700">o Edad</label>
                            <input id="nuevo-edad" v-model="nuevo.edad" type="number" min="0" max="120" :disabled="!!nuevo.fecha_nacimiento" :class="[claseCampo, 'disabled:bg-gray-100 disabled:text-gray-500']" />
                        </div>
                        <button type="submit" class="col-span-2 inline-flex min-h-[2.5rem] items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:opacity-50 md:col-span-2" :disabled="agregando">
                            <svg v-if="!agregando" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                            {{ agregando ? 'Agregando…' : 'Agregar' }}
                        </button>
                    </form>
                </section>
            </div>

            <!-- Barra fija: búsqueda y vista siempre a la mano al recorrer la lista -->
            <div class="sticky top-0 z-30 mt-5 border-y border-gray-200 bg-gray-50/95 backdrop-blur supports-[backdrop-filter]:bg-gray-50/80">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <div class="relative w-full sm:max-w-md">
                        <label for="buscar-colaborador" class="sr-only">Buscar colaborador</label>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            id="buscar-colaborador"
                            ref="buscador"
                            v-model="busqueda"
                            type="search"
                            autocomplete="off"
                            placeholder="Buscar colaborador por nombre"
                            class="w-full rounded-lg border-gray-300 py-2.5 pl-10 pr-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            @keydown.esc="busqueda = ''"
                        />
                        <kbd class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 rounded border border-gray-300 bg-white px-1.5 text-xs font-medium text-gray-500 sm:block" aria-hidden="true">/</kbd>
                    </div>
                    <div class="flex items-center justify-between gap-3 sm:justify-end">
                        <p class="whitespace-nowrap text-sm text-gray-500" aria-live="polite">
                            <span class="font-semibold tabular-nums text-gray-900">{{ visibles.length }}</span>
                            {{ visibles.length === 1 ? 'colaborador' : 'colaboradores' }}
                        </p>
                        <div class="inline-flex shrink-0 rounded-lg border border-gray-300 bg-white p-0.5 shadow-sm" role="radiogroup" aria-label="Vista de la tabla">
                            <button
                                v-for="v in [{ valor: 'completa', etiqueta: 'Completa', icono: 'M3 10h18M3 14h18M10 3v18M14 3v18' }, { valor: 'compacta', etiqueta: 'Compacta', icono: 'M4 6h16M4 12h16M4 18h16' }]"
                                :key="v.valor"
                                type="button"
                                role="radio"
                                :aria-checked="vista === v.valor"
                                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                :class="vista === v.valor ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100'"
                                @click="cambiarVista(v.valor)"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" :d="v.icono" /></svg>
                                {{ v.etiqueta }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mx-auto max-w-7xl px-4 pb-8 pt-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" aria-label="Captura de resultados">
                    <!-- Sin colaboradores -->
                    <div v-if="!participantes.length" class="flex flex-col items-center gap-3 px-6 py-14 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </span>
                        <p class="font-semibold text-gray-900">Todavía no hay colaboradores</p>
                        <p class="max-w-sm text-sm text-gray-500">Agrégalos uno por uno en el formulario de arriba, o pega la lista completa desde Excel.</p>
                        <button type="button" class="mt-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700" @click="mostrarPegar = true">Pegar lista de Excel</button>
                    </div>

                    <!-- Sin coincidencias -->
                    <div v-else-if="!visibles.length" class="flex flex-col items-center gap-3 px-6 py-14 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </span>
                        <p class="font-semibold text-gray-900">Nadie coincide con la búsqueda o el filtro</p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <button v-if="busqueda.trim()" type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700" @click="agregarDesdeBusqueda">
                                Agregar a «{{ busqueda.trim() }}» como nuevo
                            </button>
                            <button type="button" :class="claseBotonSecundario" @click="busqueda = ''; filtro = 'todos'">Ver a todos</button>
                        </div>
                    </div>

                    <!-- relative: recorta aquí también los sr-only al desplazar la tabla de lado -->
                    <div v-else class="relative overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr v-if="variosEstudios && !compacta">
                                    <th class="sticky left-0 z-10 bg-gray-50" colspan="2"></th>
                                    <th v-for="estudio in jornada.estudios" :key="estudio.estudio_id" :colspan="estudio.examenes.length" class="border-l border-gray-200 px-2 pt-2 text-center normal-case text-purple-700">{{ estudio.nombre }}</th>
                                    <th colspan="2"></th>
                                </tr>
                                <tr>
                                    <th scope="col" class="w-8 py-3 pl-3 pr-1 text-left sm:w-12 sm:pl-4 sm:pr-2">#</th>
                                    <th scope="col" class="sticky left-0 z-10 min-w-[10rem] bg-gray-50 px-3 py-3 text-left sm:min-w-[14rem]">Colaborador</th>
                                    <th v-if="compacta" scope="col" class="px-3 py-3 text-left">Resultados · {{ columnas.length }} exámenes</th>
                                    <th v-for="columna in (compacta ? [] : columnas)" :key="columna.id" scope="col" class="min-w-[7rem] px-2 py-3 text-center" :title="`Referencia: ${columna.valor_referencia || '—'}`">{{ columna.nombre_examen }}</th>
                                    <th scope="col" class="min-w-[12rem] px-3 py-3 text-left">Estado</th>
                                    <th scope="col" class="px-3 py-3"><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template v-for="p in visibles" :key="p.id">
                                    <tr :class="fondoFila(p)" class="transition-colors">
                                        <td class="border-l-4 py-3 pl-2 pr-1 align-middle text-xs tabular-nums text-gray-400 sm:pl-3 sm:pr-2" :class="franjaFila(p)">{{ numeros.get(p.id) }}</td>
                                        <td class="sticky left-0 z-10 px-3 py-3" :class="fondoFila(p)">
                                            <p class="flex items-center gap-2 font-semibold text-gray-900">
                                                {{ p.nombre }}
                                                <svg v-if="guardandoFila[p.id] > 0" class="h-3.5 w-3.5 shrink-0 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24" aria-label="Guardando"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            </p>
                                            <p class="mt-0.5 flex flex-wrap gap-x-2 text-xs text-gray-500">
                                                <span :class="!p.sexo && 'font-medium text-amber-700'">{{ p.sexo || 'Falta sexo' }}</span>
                                                <span aria-hidden="true">·</span>
                                                <span :class="p.edad === null && 'font-medium text-amber-700'">{{ p.edad !== null ? `${p.edad} años` : 'Falta edad' }}</span>
                                            </p>
                                            <p v-if="errorFila[p.id]" class="mt-1 text-xs font-medium text-red-700" role="alert">
                                                {{ errorFila[p.id].mensaje }}
                                                <button v-if="!errorFila[p.id].validacion" type="button" class="ml-1 font-semibold underline" @click="reintentar(p)">Reintentar</button>
                                            </p>
                                        </td>

                                        <!-- Compacta: resumen y botón para abrir los exámenes -->
                                        <td v-if="compacta" class="px-3 py-3">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <template v-if="anormalesDe(p).length">
                                                    <span v-for="columna in anormalesDe(p)" :key="columna.id" class="inline-flex items-center gap-1 whitespace-nowrap rounded-md bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v4m0 4h.01" /></svg>
                                                        {{ columna.nombre_examen }}: {{ valorDe(p, columna) }}
                                                    </span>
                                                </template>
                                                <span v-else class="inline-flex items-center gap-1 whitespace-nowrap rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                                    Todo normal
                                                </span>
                                                <button
                                                    type="button"
                                                    class="inline-flex min-h-[2rem] items-center gap-1 rounded-lg border border-gray-300 bg-white px-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                                    :aria-expanded="panelAbierto(p)"
                                                    @click="alternarPanel(p)"
                                                >
                                                    {{ panelAbierto(p) ? 'Ocultar' : (p.reporte ? 'Ver' : 'Capturar') }}
                                                    <svg class="h-3.5 w-3.5 transition-transform" :class="panelAbierto(p) && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                                </button>
                                            </div>
                                        </td>

                                        <!-- Completa: una columna por examen -->
                                        <td v-for="columna in (compacta ? [] : columnas)" :key="columna.id" class="px-2 py-3 text-center">
                                            <CeldaResultado
                                                :valor="valorDe(p, columna)"
                                                :opciones="columna.opciones"
                                                :anormal="esAnormal(p, columna)"
                                                :bloqueado="!!p.reporte"
                                                :etiqueta="`${columna.nombre_examen} de ${p.nombre}`"
                                                @alternar="alternar(p, columna)"
                                                @escribir="escribir(p, columna, $event)"
                                            />
                                        </td>

                                        <td class="px-3 py-3">
                                            <button
                                                v-if="etapa(p) === 'pendiente'"
                                                type="button"
                                                class="inline-flex min-h-[2.5rem] items-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                                                @click="marcarListo(p)"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                Listo
                                            </button>
                                            <div v-else-if="etapa(p) === 'listo'" class="space-y-1">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                                    Capturado
                                                </span>
                                                <div class="flex gap-3 text-xs">
                                                    <button type="button" class="font-medium text-gray-500 hover:text-gray-900 hover:underline" @click="deshacerListo(p)">Deshacer</button>
                                                    <button type="button" class="font-medium text-blue-700 hover:text-blue-900 hover:underline disabled:opacity-50" :disabled="generando" @click="generarReportes([p.id])">Emitir reporte</button>
                                                </div>
                                            </div>
                                            <div v-else-if="p.reporte" class="flex flex-wrap items-center gap-2">
                                                <Link :href="route('reportes.show', p.reporte.id)" class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 font-mono text-xs font-semibold text-blue-800 ring-1 ring-inset ring-blue-200 hover:bg-blue-100">{{ p.reporte.folio }}</Link>
                                                <button type="button" class="inline-flex items-center gap-1 text-xs font-medium text-blue-700 hover:text-blue-900 hover:underline" @click="verPdf(p)">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                    Ver PDF
                                                </button>
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-3 text-right">
                                            <div v-if="!p.reporte" class="inline-flex gap-1">
                                                <button type="button" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" :title="`Editar datos de ${p.nombre}`" @click="abrirEdicion(p)">
                                                    <span class="sr-only">Editar datos de {{ p.nombre }}</span>
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button type="button" class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500" :title="`Quitar a ${p.nombre}`" @click="quitar(p)">
                                                    <span class="sr-only">Quitar a {{ p.nombre }}</span>
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Vista compacta: todos los exámenes en cuadrícula, sin desplazarse de lado -->
                                    <tr v-if="compacta && panelAbierto(p)" :class="fondoFila(p)">
                                        <td :colspan="totalColumnas" class="border-l-4 px-3 pb-4 pt-1" :class="franjaFila(p)">
                                            <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                                <div v-for="grupo in grupos" :key="grupo.id">
                                                    <p v-if="variosEstudios" class="mb-2 text-xs font-semibold uppercase tracking-wide text-purple-700">{{ grupo.nombre }}</p>
                                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                                                        <div v-for="columna in grupo.columnas" :key="columna.id" class="min-w-0 space-y-1">
                                                            <p class="truncate text-xs font-medium text-gray-700" :title="`${columna.nombre_examen} · Referencia: ${columna.valor_referencia || '—'}`">{{ columna.nombre_examen }}</p>
                                                            <CeldaResultado
                                                                :valor="valorDe(p, columna)"
                                                                :opciones="columna.opciones"
                                                                :anormal="esAnormal(p, columna)"
                                                                :bloqueado="!!p.reporte"
                                                                :etiqueta="`${columna.nombre_examen} de ${p.nombre}`"
                                                                ancho
                                                                @alternar="alternar(p, columna)"
                                                                @escribir="escribir(p, columna, $event)"
                                                            />
                                                            <p v-if="!columna.opciones && columna.valor_referencia" class="truncate text-[11px] text-gray-400">Ref. {{ columna.valor_referencia }} {{ columna.unidad }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-if="etapa(p) === 'pendiente'" class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3">
                                                    <p class="text-xs text-gray-500">Revisa los resultados y confirma.</p>
                                                    <button type="button" class="inline-flex min-h-[2.5rem] items-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2" @click="marcarListo(p)">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                        Listo
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Edición de datos personales -->
                                    <tr v-if="edicion.id === p.id" class="bg-blue-50/70">
                                        <td :colspan="totalColumnas" class="border-l-4 border-l-blue-500 px-3 py-4">
                                            <form class="grid grid-cols-2 gap-3 md:grid-cols-12 md:items-end" @submit.prevent="guardarEdicion(p)" @keydown.esc="edicion.id = null">
                                                <label class="col-span-2 text-sm font-medium text-gray-700 md:col-span-4">Nombre
                                                    <input v-model="edicion.nombre" type="text" required :class="[claseCampo, 'mt-1']" />
                                                </label>
                                                <label class="text-sm font-medium text-gray-700 md:col-span-2">Sexo
                                                    <select v-model="edicion.sexo" :class="[claseCampo, 'mt-1']">
                                                        <option value="">—</option>
                                                        <option value="Masculino">Hombre</option>
                                                        <option value="Femenino">Mujer</option>
                                                    </select>
                                                </label>
                                                <label class="text-sm font-medium text-gray-700 md:col-span-2">F. nacimiento
                                                    <input v-model="edicion.fecha_nacimiento" type="date" :class="[claseCampo, 'mt-1']" />
                                                </label>
                                                <label class="text-sm font-medium text-gray-700 md:col-span-1">o Edad
                                                    <input v-model="edicion.edad" type="number" min="0" max="120" :disabled="!!edicion.fecha_nacimiento" :class="[claseCampo, 'mt-1 disabled:bg-gray-100 disabled:text-gray-500']" />
                                                </label>
                                                <div class="col-span-2 flex gap-2 md:col-span-3">
                                                    <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Guardar</button>
                                                    <button type="button" :class="claseBotonSecundario" @click="edicion.id = null">Cancelar</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- Barra fija inferior: las acciones de reportes siempre a la mano -->
            <div v-if="resumen.listo || resumen.reportado" class="sticky bottom-0 z-30 border-t border-gray-200 bg-white/95 shadow-[0_-4px_16px_-8px_rgba(0,0,0,0.15)] backdrop-blur supports-[backdrop-filter]:bg-white/85">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <div v-if="descarga.activa" class="min-w-0 flex-1 sm:max-w-sm" aria-live="polite">
                        <p class="text-sm font-medium text-gray-800">Descargando PDF {{ descarga.hechas }} de {{ descarga.total }}…</p>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full bg-blue-600 transition-all" :style="{ width: `${(descarga.hechas / descarga.total) * 100}%` }"></div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Si el navegador pregunta, permite descargar varios archivos.</p>
                    </div>
                    <p v-else class="text-sm text-gray-600">
                        <template v-if="resumen.listo">
                            <span class="font-semibold text-gray-900">{{ resumen.listo }}</span> {{ resumen.listo === 1 ? 'colaborador listo' : 'colaboradores listos' }} para emitir su reporte.
                        </template>
                        <template v-else>Todos los capturados ya tienen reporte.</template>
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button v-if="descarga.activa" type="button" :class="claseBotonSecundario" @click="descarga.cancelar = true">Detener descarga</button>
                        <button
                            v-else
                            type="button"
                            :class="claseBotonSecundario"
                            :disabled="!visiblesConReporte.length"
                            title="Descarga los PDF de los colaboradores que se ven en la tabla"
                            @click="descargarPdfs"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            Descargar PDFs ({{ visiblesConReporte.length }})
                        </button>
                        <button
                            v-if="resumen.listo"
                            type="button"
                            class="inline-flex min-h-[2.5rem] items-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:opacity-50"
                            :disabled="generando"
                            @click="generarReportes()"
                        >
                            <svg v-if="generando" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            {{ generando ? 'Emitiendo reportes…' : `Generar reportes (${resumen.listo})` }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avisos flotantes -->
        <div class="pointer-events-none fixed bottom-24 right-4 z-50 flex w-full max-w-sm flex-col gap-2 sm:right-6" aria-live="polite">
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="a in avisos"
                    :key="a.id"
                    :role="a.tipo === 'error' ? 'alert' : 'status'"
                    class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-4 text-sm shadow-lg"
                    :class="a.tipo === 'error' ? 'border-red-200' : 'border-emerald-200'"
                >
                    <svg v-if="a.tipo === 'error'" class="h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <svg v-else class="h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="flex-1 text-gray-800">{{ a.texto }}</p>
                    <button type="button" class="rounded p-0.5 text-gray-400 hover:text-gray-700" @click="cerrarAviso(a.id)">
                        <span class="sr-only">Cerrar aviso</span>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>

        <!-- Confirmaciones -->
        <Modal :show="dialogo.abierto" max-width="md" @close="cerrarDialogo(false)">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="dialogo.peligro ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-700'">
                        <svg v-if="dialogo.peligro" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </span>
                    <div class="min-w-0 space-y-2">
                        <h3 class="text-lg font-semibold text-gray-900">{{ dialogo.titulo }}</h3>
                        <p class="text-sm text-gray-600">{{ dialogo.mensaje }}</p>
                        <ul v-if="dialogo.detalles.length" class="list-disc space-y-1 pl-5 text-sm text-gray-600">
                            <li v-for="detalle in dialogo.detalles" :key="detalle">{{ detalle }}</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" :class="claseBotonSecundario" @click="cerrarDialogo(false)">Cancelar</button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                        :class="dialogo.peligro ? 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-500' : 'bg-emerald-600 hover:bg-emerald-700 focus-visible:ring-emerald-500'"
                        @click="cerrarDialogo(true)"
                    >{{ dialogo.confirmar }}</button>
                </div>
            </div>
        </Modal>

        <FormularioJornada
            v-if="mostrarConfig"
            :jornada="jornada"
            :con-reportes="resumen.reportado > 0"
            :catalogo="catalogo"
            :medicos="medicos"
            @cerrar="mostrarConfig = false"
            @guardada="configGuardada"
        />

        <PegarLista
            v-if="mostrarPegar"
            :guardando="agregando"
            @cerrar="mostrarPegar = false"
            @agregar="agregarPegados"
        />
    </AuthenticatedLayout>
</template>
