<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { IVA_PORCENTAJE, calcularTotales } from '@/utils/iva';
import EstadoBadge from '@/Components/Captura/EstadoBadge.vue';
import Interruptor from '@/Components/Captura/Interruptor.vue';
import ResultadoCelda from '@/Components/Captura/ResultadoCelda.vue';
import {
    aplicarMarcaManual,
    formatoFechaHora,
    marcarRango,
    recalcularOrden,
    usePermisos,
    useResultadosAnteriores,
} from '@/utils/captura';
import { enviarResultados } from '@/utils/envio';

const props = defineProps({
    reporte: Object,
});

const RESPONSABLE = 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS';

const permisos = usePermisos();
const esAdmin = computed(() => !!permisos.value.validar);
const anteriores = useResultadosAnteriores();

const estudiosDisponibles = ref([]);
const estudiosFiltrados = ref([]);
const busquedaEstudio = ref('');
const guardando = ref(false);
const validando = ref(false);
const medicosDisponibles = ref([]);
const modoMedico = ref('registrado');
const seleccionados = ref([]); // ids para reporte complementario
const hayCambios = ref(false);

const aviso = reactive({ visible: false, texto: '', tipo: 'ok' });
let temporizadorAviso = null;
const avisar = (texto, tipo = 'ok') => {
    aviso.texto = texto;
    aviso.tipo = tipo;
    aviso.visible = true;
    clearTimeout(temporizadorAviso);
    temporizadorAviso = setTimeout(() => { aviso.visible = false; }, 6000);
};

const normalizeDatetime = (value) => (value ? String(value).replace(' ', 'T').slice(0, 16) : '');
const normalizeDate = (value) => (value ? String(value).slice(0, 10) : '');

const form = reactive({
    id: null,
    folio: '',
    toma_muestra: '',
    fecha_reporte: '',
    fecha_validacion: '',
    medico_solicitante: '',
    medico_id: null,
    aplica_iva: false,
    descuento: 0,
    cliente: { nombre: '', email: '', fecha_nacimiento: '', edad: '', sexo: '' },
    estudios: [],
});

const prepararResultado = (resultado) => ({
    id: resultado.id,
    examen_id: resultado.examen_id,
    nombre_examen: resultado.nombre_examen || '',
    unidad: resultado.unidad || '',
    valor_referencia: resultado.valor_referencia || '',
    niveles: resultado.niveles || [],
    resultado: resultado.resultado || '',
    fuera_rango: Boolean(resultado.fuera_rango),
    direccion_rango: resultado.direccion_rango || null,
    calculado: false,
    formula: '',
    // La marca guardada se respeta hasta que se vuelva a escribir el valor
    marca_manual: true,
});

const hydrateForm = () => {
    const data = props.reporte;

    form.id = data.id;
    form.folio = data.folio;
    form.toma_muestra = normalizeDatetime(data.toma_muestra);
    form.fecha_reporte = normalizeDatetime(data.fecha_reporte);
    form.fecha_validacion = normalizeDatetime(data.fecha_validacion);
    form.medico_solicitante = data.medico_solicitante || '';
    form.medico_id = data.medico_id ?? null;
    form.aplica_iva = Boolean(data.aplica_iva);
    form.descuento = Number(data.descuento) || 0;

    if (data.medico_id) {
        modoMedico.value = 'registrado';
    } else if (!data.medico_solicitante || data.medico_solicitante === 'N/A') {
        modoMedico.value = 'na';
        form.medico_solicitante = 'N/A';
    } else {
        modoMedico.value = 'otro';
    }

    form.cliente.nombre = data.nombre_cliente || '';
    form.cliente.email = data.email || '';
    form.cliente.fecha_nacimiento = normalizeDate(data.fecha_nacimiento);
    form.cliente.edad = data.edad ?? '';
    form.cliente.sexo = data.sexo || '';

    form.estudios = (data.estudios || []).map((estudio) => ({
        ...estudio,
        tipo_muestra: estudio.tipo_muestra || '',
        metodo: estudio.metodo || '',
        elaboro: estudio.elaboro || '',
        valido: estudio.valido || '',
        observaciones: estudio.observaciones || '',
        precio: Number(estudio.precio || 0),
        leyenda: estudio.leyenda || '',
        resultados: (estudio.resultados || []).map(prepararResultado),
    }));

    seleccionados.value = seleccionados.value.filter((id) => form.estudios.some((e) => e.id === id));
    hayCambios.value = false;
};

const pacienteParaRango = computed(() => ({ sexo: form.cliente.sexo, edad: form.cliente.edad }));
const listasDeExamenes = () => form.estudios.map((e) => e.resultados);

const onEscribir = (resultado) => {
    resultado.marca_manual = false;
    marcarRango(resultado, pacienteParaRango.value);
    recalcularOrden(listasDeExamenes(), pacienteParaRango.value);
};

const calcularEdad = (fechaNacimiento) => {
    if (!fechaNacimiento) return '';
    const nacimiento = new Date(`${String(fechaNacimiento).slice(0, 10)}T00:00:00`);
    if (Number.isNaN(nacimiento.getTime())) return '';
    const referencia = form.toma_muestra ? new Date(form.toma_muestra) : new Date();
    const base = Number.isNaN(referencia.getTime()) ? new Date() : referencia;
    let edad = base.getFullYear() - nacimiento.getFullYear();
    const mes = base.getMonth() - nacimiento.getMonth();
    if (mes < 0 || (mes === 0 && base.getDate() < nacimiento.getDate())) edad--;
    return edad >= 0 ? edad : '';
};

const onFechaNacimientoChange = () => {
    form.cliente.edad = calcularEdad(form.cliente.fecha_nacimiento);
};

watch(() => props.reporte, hydrateForm, { immediate: true });
watch(form, () => { hayCambios.value = true; }, { deep: true, flush: 'sync' });
// Tras hidratar no hay cambios pendientes
watch(() => props.reporte, () => { hayCambios.value = false; }, { flush: 'post' });

onMounted(async () => {
    hayCambios.value = false;
    const [estudiosRes, medicosRes] = await Promise.all([axios.get('/api/estudios'), axios.get('/api/medicos')]);
    estudiosDisponibles.value = estudiosRes.data;
    medicosDisponibles.value = medicosRes.data.filter((m) => m.activo);
    anteriores.cargar(props.reporte.paciente_id, props.reporte.id);
});

const onModoMedicoChange = (modo) => {
    modoMedico.value = modo;
    form.medico_id = null;
    form.medico_solicitante = modo === 'na' ? 'N/A' : '';
};

const formatCurrency = (value) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);

const totalPrecio = computed(() => form.estudios.reduce((sum, estudio) => sum + (Number(estudio.precio) || 0), 0));
const totales = computed(() => calcularTotales(totalPrecio.value, form.descuento, form.aplica_iva));
const montoDescuento = computed(() => totales.value.montoDescuento);
const montoIva = computed(() => totales.value.iva);
const totalConIva = computed(() => totales.value.total);

// ===== Estado de la orden (T2 / T6) =====
const incluidos = computed(() => form.estudios.filter((e) => e.incluir_en_reporte));
const porValidar = computed(() => form.estudios.filter((e) => e.id && e.estado === 'capturado'));
const listoParaEntregar = computed(() =>
    incluidos.value.length > 0 && incluidos.value.every((e) => ['validado', 'entregado'].includes(e.estado)),
);
const motivoNoListo = computed(() => {
    if (!incluidos.value.length) return 'Ningún estudio está marcado para incluirse en el reporte.';
    const faltan = incluidos.value.filter((e) => !['validado', 'entregado'].includes(e.estado));
    if (!faltan.length) return '';
    return `Falta validar: ${faltan.map((e) => e.nombre).join(', ')}`;
});

const filtrarEstudios = () => {
    const termino = busquedaEstudio.value.toLowerCase().trim();
    estudiosFiltrados.value = termino
        ? estudiosDisponibles.value.filter((estudio) => estudio.nombre.toLowerCase().includes(termino))
        : [...estudiosDisponibles.value];
};

const cerrarListaEstudios = () => setTimeout(() => { estudiosFiltrados.value = []; }, 150);

const agregarEstudio = (estudio) => {
    busquedaEstudio.value = '';
    estudiosFiltrados.value = [];

    if (form.estudios.some((item) => Number(item.estudio_id) === Number(estudio.id))) {
        avisar('Este estudio ya está en la orden.', 'error');
        return;
    }

    form.estudios.push({
        id: null,
        estudio_id: estudio.id,
        nombre: estudio.nombre,
        tipo_muestra: estudio.tipo_muestra || '',
        metodo: estudio.metodo || '',
        elaboro: esAdmin.value ? RESPONSABLE : '',
        valido: RESPONSABLE,
        observaciones: '',
        precio: Number(estudio.precio || 0),
        leyenda: estudio.leyenda || '',
        incluir_en_reporte: true,
        estado: 'pendiente',
        resultados: (estudio.examenes || []).map((examen) => ({
            ...prepararResultado({ ...examen, examen_id: examen.id, id: null, resultado: '' }),
            marca_manual: false,
        })),
    });
};

const eliminarEstudio = (index) => {
    const estudio = form.estudios[index];
    if (!esAdmin.value && ['validado', 'entregado'].includes(estudio.estado)) {
        avisar('Un estudio validado solo lo puede quitar el administrador.', 'error');
        return;
    }
    if (!window.confirm(`¿Quitar "${estudio.nombre}" de la orden? Se borrarán sus resultados al guardar.`)) return;
    form.estudios.splice(index, 1);
    recalcularOrden(listasDeExamenes(), pacienteParaRango.value);
};

const moverEstudio = (index, direccion) => {
    const destino = index + direccion;
    if (destino < 0 || destino >= form.estudios.length) return;
    const [estudio] = form.estudios.splice(index, 1);
    form.estudios.splice(destino, 0, estudio);
};

const textoNivel = (nivel) => {
    const n = (v) => (v === null || v === undefined || v === '' ? null : Number(v));
    const desde = n(nivel.desde);
    const hasta = n(nivel.hasta);
    if (desde !== null && hasta !== null) return `${desde} – ${hasta}`;
    if (desde !== null) return `≥ ${desde}`;
    if (hasta !== null) return `≤ ${hasta}`;
    return '—';
};

const mensajeError = (error, porDefecto) => {
    const errores = error.response?.data?.errors;
    return errores ? Object.values(errores).flat()[0] : (error.response?.data?.message || porDefecto);
};

const guardarCambios = async () => {
    guardando.value = true;

    try {
        await axios.put(`/api/reportes/${form.id}`, {
            id: form.id,
            toma_muestra: form.toma_muestra,
            fecha_reporte: form.fecha_reporte,
            fecha_validacion: form.fecha_validacion,
            medico_solicitante: form.medico_solicitante,
            medico_id: form.medico_id,
            aplica_iva: form.aplica_iva,
            descuento: Number(form.descuento) || 0,
            cliente: { ...form.cliente },
            estudios: form.estudios.map((estudio, index) => ({
                id: estudio.id,
                estudio_id: estudio.estudio_id,
                orden: index,
                tipo_muestra: estudio.tipo_muestra,
                metodo: estudio.metodo,
                elaboro: estudio.elaboro,
                valido: estudio.valido,
                observaciones: estudio.observaciones,
                precio: Number(estudio.precio || 0),
                incluir_en_reporte: estudio.incluir_en_reporte,
                resultados: (estudio.resultados || []).map((resultado) => ({
                    id: resultado.id,
                    examen_id: resultado.examen_id,
                    resultado: resultado.resultado,
                    fuera_rango: Boolean(resultado.fuera_rango),
                    direccion_rango: resultado.fuera_rango ? resultado.direccion_rango : null,
                })),
            })),
        });
        avisar(esAdmin.value ? 'Cambios guardados.' : 'Cambios guardados. Los resultados modificados quedan pendientes de validar.');
        router.reload({ only: ['reporte'] });
    } catch (error) {
        console.error(error);
        avisar(mensajeError(error, 'No se pudieron guardar los cambios.'), 'error');
    } finally {
        guardando.value = false;
    }
};

// T2: el interruptor se guarda al momento, sin tener que guardar todo el reporte
const cambiarInclusion = async (estudio, incluir) => {
    if (!estudio.id) {
        estudio.incluir_en_reporte = incluir;
        return;
    }
    const anterior = estudio.incluir_en_reporte;
    estudio.incluir_en_reporte = incluir;
    try {
        const { data } = await axios.patch(`/api/reportes/${form.id}/estudios/${estudio.id}/incluir`, { incluir_en_reporte: incluir });
        avisar(`${estudio.nombre}: ${data.message.toLowerCase()}.`);
    } catch (error) {
        estudio.incluir_en_reporte = anterior;
        avisar(mensajeError(error, 'No se pudo cambiar.'), 'error');
    }
};

const validar = async (ids = null) => {
    if (hayCambios.value && !window.confirm('Tienes cambios sin guardar. Se validará lo último que se guardó. ¿Continuar?')) return;
    validando.value = true;
    try {
        const { data } = await axios.post(`/api/reportes/${form.id}/validar`, ids ? { estudios: ids } : {});
        avisar(`${data.message}.`);
        router.reload({ only: ['reporte'] });
    } catch (error) {
        avisar(mensajeError(error, 'No se pudo validar.'), 'error');
    } finally {
        validando.value = false;
    }
};

const urlPdf = (tipo, ids = null) => {
    const base = `/api/reportes/${props.reporte.id}/${tipo === 'descargar' ? 'pdf' : 'pdf-preview'}`;
    return ids?.length ? `${base}?estudios=${ids.join(',')}` : base;
};

const abrirPdf = (tipo, ids = null) => {
    if (!esAdmin.value && !listoParaEntregar.value && !ids) {
        avisar(motivoNoListo.value, 'error');
        return;
    }
    window.open(urlPdf(tipo, ids), '_blank');
};

const descargarOrden = () => window.open(`/api/reportes/${props.reporte.id}/orden`, '_blank');

const enviar = async (canal) => {
    if (!listoParaEntregar.value) {
        avisar(`No se puede enviar todavía. ${motivoNoListo.value}`, 'error');
        return;
    }
    const resultado = await enviarResultados(props.reporte.id, canal);
    avisar(resultado.mensaje, resultado.ok ? 'ok' : 'error');
    if (resultado.ok) router.reload({ only: ['reporte'] });
};

const seleccionValidada = computed(() =>
    seleccionados.value.every((id) => ['validado', 'entregado'].includes(form.estudios.find((e) => e.id === id)?.estado)),
);

const imprimirComplementario = () => {
    if (!esAdmin.value && !seleccionValidada.value) {
        avisar('Solo se pueden imprimir estudios ya validados.', 'error');
        return;
    }
    abrirPdf('ver', seleccionados.value);
};
</script>

<template>
    <Head :title="`Reporte ${reporte.folio}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">Reporte {{ form.folio }}</h2>
                    <EstadoBadge :estado="reporte.estado" tipo="orden" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="abrirPdf('ver')"
                        class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700"
                        :class="{ 'opacity-50': !esAdmin && !listoParaEntregar }"
                        :title="!listoParaEntregar ? motivoNoListo : ''">
                        Ver PDF
                    </button>
                    <button type="button" @click="abrirPdf('descargar')"
                        class="px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700"
                        :class="{ 'opacity-50': !esAdmin && !listoParaEntregar }"
                        :title="!listoParaEntregar ? motivoNoListo : ''">
                        {{ listoParaEntregar ? 'Descargar PDF' : (esAdmin ? 'Descargar preliminar' : 'Descargar PDF') }}
                    </button>
                    <button type="button" @click="enviar('whatsapp')"
                        class="px-3 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700"
                        :class="{ 'opacity-50': !listoParaEntregar }" :title="motivoNoListo">
                        Enviar por WhatsApp
                    </button>
                    <button type="button" @click="enviar('correo')"
                        class="px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50"
                        :class="{ 'opacity-50': !listoParaEntregar }" :title="motivoNoListo">
                        Enviar por correo
                    </button>
                    <button type="button" @click="descargarOrden"
                        class="px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">
                        Orden de trabajo
                    </button>
                    <Link v-if="reporte.paciente_id" :href="route('pacientes.historial', reporte.paciente_id)"
                        class="px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">
                        Historial del paciente
                    </Link>
                    <Link :href="route('reportes.index')"
                        class="px-3 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700">
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Pendientes de validar -->
                <div v-if="porValidar.length" class="flex flex-wrap items-center gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3">
                    <p class="flex-1 text-sm text-amber-900">
                        <strong>{{ porValidar.length }} {{ porValidar.length === 1 ? 'estudio capturado espera' : 'estudios capturados esperan' }} validación</strong>
                        <span v-if="!esAdmin"> del Q.F.B. Hasta entonces no se puede descargar ni enviar el PDF.</span>
                        <span v-else>. Revisa los resultados y valida.</span>
                    </p>
                    <button v-if="esAdmin" type="button" @click="validar()" :disabled="validando"
                        class="px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 disabled:opacity-50">
                        {{ validando ? 'Validando…' : 'Validar todo' }}
                    </button>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="folio" class="block text-sm font-medium text-gray-700 mb-1">Folio</label>
                            <input id="folio" type="text" :value="form.folio" disabled class="w-full rounded-lg border-gray-300 bg-gray-100 text-sm" />
                        </div>
                        <div>
                            <label for="toma" class="block text-sm font-medium text-gray-700 mb-1">Toma de muestra</label>
                            <input id="toma" v-model="form.toma_muestra" type="datetime-local" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label for="freporte" class="block text-sm font-medium text-gray-700 mb-1">Fecha de reporte</label>
                            <input id="freporte" v-model="form.fecha_reporte" type="datetime-local" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label for="fvalidacion" class="block text-sm font-medium text-gray-700 mb-1">Fecha de validación</label>
                            <input id="fvalidacion" v-model="form.fecha_validacion" type="datetime-local" :readonly="!esAdmin"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" :class="{ 'bg-gray-100': !esAdmin }" />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4">
                            <span class="block text-sm font-medium text-gray-700 mb-1">Médico solicitante</span>
                            <div class="flex rounded-lg border border-gray-300 overflow-hidden text-xs font-medium mb-2" role="group" aria-label="Tipo de médico">
                                <button type="button" @click="onModoMedicoChange('registrado')" :aria-pressed="modoMedico === 'registrado'"
                                    :class="['flex-1 py-1.5', modoMedico === 'registrado' ? 'bg-blue-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50']">Registrado</button>
                                <button type="button" @click="onModoMedicoChange('otro')" :aria-pressed="modoMedico === 'otro'"
                                    :class="['flex-1 py-1.5 border-x border-gray-300', modoMedico === 'otro' ? 'bg-blue-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50']">Otro</button>
                                <button type="button" @click="onModoMedicoChange('na')" :aria-pressed="modoMedico === 'na'"
                                    :class="['flex-1 py-1.5', modoMedico === 'na' ? 'bg-gray-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50']">N/A</button>
                            </div>
                            <select v-if="modoMedico === 'registrado'" v-model="form.medico_id" aria-label="Médico registrado"
                                @change="form.medico_solicitante = medicosDisponibles.find(m => m.id === form.medico_id)?.nombre ?? ''"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 bg-white px-3 py-2">
                                <option :value="null">— Selecciona un médico —</option>
                                <option v-for="m in medicosDisponibles" :key="m.id" :value="m.id">{{ m.nombre }}{{ m.especialidad ? ` (${m.especialidad})` : '' }}</option>
                            </select>
                            <input v-else-if="modoMedico === 'otro'" v-model="form.medico_solicitante" type="text" placeholder="Nombre del médico" aria-label="Nombre del médico"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2" />
                            <div v-else class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-500">N/A — Sin médico solicitante</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Datos del paciente</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                <input id="nombre" v-model="form.cliente.nombre" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input id="email" v-model="form.cliente.email" type="email" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label for="nacimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha de nacimiento</label>
                                <input id="nacimiento" v-model="form.cliente.fecha_nacimiento" type="date" @change="onFechaNacimientoChange"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label for="edad" class="block text-sm font-medium text-gray-700 mb-1">Edad</label>
                                <input id="edad" v-model="form.cliente.edad" type="number" min="0" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label for="sexo" class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                                <select id="sexo" v-model="form.cliente.sexo" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Seleccione</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-visible shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <label for="agregar-estudio" class="block text-base sm:text-lg font-semibold text-gray-900 mb-4">Agregar estudio a la orden</label>
                        <div class="relative z-30 max-w-2xl">
                            <input id="agregar-estudio" v-model="busquedaEstudio" type="text" autocomplete="off" placeholder="Escribe para buscar estudios..."
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                @focus="filtrarEstudios" @input="filtrarEstudios" @keydown.esc="estudiosFiltrados = []"
                                @blur="cerrarListaEstudios" />
                            <ul v-if="estudiosFiltrados.length > 0" class="absolute z-40 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto">
                                <li v-for="estudio in estudiosFiltrados" :key="estudio.id"
                                    class="px-4 py-2 text-sm hover:bg-blue-50 cursor-pointer flex items-center justify-between"
                                    @mousedown.prevent="agregarEstudio(estudio)">
                                    <span>{{ estudio.nombre }}</span>
                                    <span class="font-semibold text-gray-700">{{ formatCurrency(estudio.precio) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <article
                    v-for="(estudio, index) in form.estudios"
                    :key="`${estudio.estudio_id}-${estudio.id ?? 'nuevo'}`"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg border"
                    :class="estudio.incluir_en_reporte ? 'border-transparent' : 'border-dashed border-gray-300'"
                >
                    <div class="p-4 sm:p-6 space-y-4">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900">{{ estudio.nombre }}</h3>
                                        <EstadoBadge :estado="estudio.estado" />
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 space-x-2">
                                        <span v-if="estudio.capturado_por">Capturó {{ estudio.capturado_por }} · {{ formatoFechaHora(estudio.capturado_at) }}</span>
                                        <span v-if="estudio.validado_por">· Validó {{ estudio.validado_por }} · {{ formatoFechaHora(estudio.validado_at) }}</span>
                                        <span v-if="estudio.entregado_at">· Entregado {{ formatoFechaHora(estudio.entregado_at) }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <Interruptor
                                    :model-value="estudio.incluir_en_reporte"
                                    @update:model-value="(v) => cambiarInclusion(estudio, v)"
                                    etiqueta="Incluir en reporte"
                                    :descripcion="estudio.incluir_en_reporte ? 'Sale en el PDF' : 'No sale en el PDF'"
                                />
                                <label v-if="estudio.id" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                                    title="Imprime solo los estudios marcados, con el mismo folio">
                                    <input type="checkbox" :value="estudio.id" v-model="seleccionados"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    Imprimir aparte
                                </label>
                                <button v-if="esAdmin && estudio.estado === 'capturado'" type="button" @click="validar([estudio.id])" :disabled="validando"
                                    class="px-3 py-2 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 disabled:opacity-50">
                                    Validar
                                </button>
                                <button type="button" @click="moverEstudio(index, -1)" :disabled="index === 0" aria-label="Subir estudio"
                                    class="px-2.5 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 disabled:opacity-40">↑</button>
                                <button type="button" @click="moverEstudio(index, 1)" :disabled="index === form.estudios.length - 1" aria-label="Bajar estudio"
                                    class="px-2.5 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 disabled:opacity-40">↓</button>
                                <button type="button" @click="eliminarEstudio(index)"
                                    class="px-3 py-2 bg-red-50 text-red-700 text-sm rounded-lg hover:bg-red-100">Quitar</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" :class="esAdmin ? 'lg:grid-cols-5' : 'lg:grid-cols-4'">
                            <div>
                                <label :for="`muestra-${index}`" class="block text-sm font-medium text-gray-700 mb-1">Tipo de muestra</label>
                                <input :id="`muestra-${index}`" v-model="estudio.tipo_muestra" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label :for="`metodo-${index}`" class="block text-sm font-medium text-gray-700 mb-1">Método</label>
                                <input :id="`metodo-${index}`" v-model="estudio.metodo" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label :for="`elaboro-${index}`" class="block text-sm font-medium text-gray-700 mb-1">Elaboró</label>
                                <input :id="`elaboro-${index}`" v-model="estudio.elaboro" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div v-if="esAdmin">
                                <label :for="`valido-${index}`" class="block text-sm font-medium text-gray-700 mb-1">Validó</label>
                                <input :id="`valido-${index}`" v-model="estudio.valido" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label :for="`precio-${index}`" class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                                <input :id="`precio-${index}`" v-model.number="estudio.precio" type="number" min="0" step="0.01" :readonly="!permisos.editar_precios"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" :class="{ 'bg-gray-100': !permisos.editar_precios }" />
                            </div>
                        </div>

                        <div>
                            <label :for="`obs-${index}`" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                            <textarea :id="`obs-${index}`" v-model="estudio.observaciones" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>

                        <div v-if="estudio.leyenda" class="p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded text-sm text-gray-700">
                            <span class="font-semibold">Leyenda:</span> {{ estudio.leyenda }}
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Examen</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Referencia</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase min-w-[20rem]">Resultado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <tr v-for="(resultado, rIndex) in estudio.resultados" :key="`${resultado.examen_id}-${resultado.id || 'nuevo'}`" class="align-top">
                                        <td class="px-3 py-2 text-sm text-gray-900">
                                            <label :for="`res-${index}-${rIndex}`">{{ resultado.nombre_examen }}</label>
                                            <span v-if="resultado.unidad" class="block text-xs text-gray-500">{{ resultado.unidad }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-700 max-w-xs">
                                            <ul v-if="resultado.niveles?.length" class="space-y-0.5 text-xs">
                                                <li v-for="nivel in resultado.niveles" :key="nivel.id" :class="nivel.es_normal ? 'font-semibold text-gray-800' : ''">
                                                    {{ textoNivel(nivel) }} · {{ nivel.interpretacion }}
                                                </li>
                                            </ul>
                                            <span v-else>{{ resultado.valor_referencia || '—' }}</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <ResultadoCelda
                                                :examen="resultado"
                                                :input-id="`res-${index}-${rIndex}`"
                                                :anterior="anteriores.anteriorDe(resultado.examen_id, resultado.nombre_examen)"
                                                @escribir="onEscribir(resultado)"
                                                @marca="(valor) => { resultado.marca_manual = true; aplicarMarcaManual(resultado, valor); }"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>

                <div class="bg-slate-800 overflow-hidden shadow-lg sm:rounded-lg">
                    <div class="p-4 sm:p-6 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold">Total de la orden</h3>
                            <p class="text-sm opacity-90">{{ form.estudios.length }} estudios</p>
                            <label class="mt-3 inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 rounded border-slate-400 text-emerald-500 focus:ring-emerald-400" v-model="form.aplica_iva" />
                                <span class="text-sm">Aplicar IVA ({{ IVA_PORCENTAJE }}%)</span>
                            </label>
                            <label class="mt-2 flex items-center gap-2">
                                <span class="text-sm">Descuento (%)</span>
                                <input type="number" min="0" max="100" step="0.01" v-model.number="form.descuento" :readonly="!permisos.editar_precios"
                                    class="w-20 px-2 py-1 text-sm text-right text-gray-900 rounded border-slate-400 focus:ring-emerald-400" :class="{ 'bg-gray-200': !permisos.editar_precios }" />
                            </label>
                        </div>
                        <div class="text-right">
                            <template v-if="form.aplica_iva || montoDescuento > 0">
                                <p class="text-sm opacity-90">Subtotal: {{ formatCurrency(totalPrecio) }}</p>
                                <p v-if="montoDescuento > 0" class="text-sm opacity-90">Descuento ({{ form.descuento }}%): -{{ formatCurrency(montoDescuento) }}</p>
                                <p v-if="form.aplica_iva" class="text-sm opacity-90">IVA ({{ IVA_PORCENTAJE }}%): {{ formatCurrency(montoIva) }}</p>
                            </template>
                            <p class="text-2xl font-bold">{{ formatCurrency(totalConIva) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Reporte complementario (T2): solo los estudios marcados, mismo folio -->
                <div v-if="seleccionados.length" class="sticky bottom-4 z-20 flex flex-wrap items-center gap-3 rounded-xl border border-indigo-200 bg-white px-4 py-3 shadow-lg">
                    <p class="flex-1 min-w-[14rem] text-sm text-gray-700">
                        <strong>{{ seleccionados.length }}</strong> {{ seleccionados.length === 1 ? 'estudio marcado' : 'estudios marcados' }}
                        para imprimir aparte como reporte complementario (mismo folio {{ form.folio }}).
                    </p>
                    <button type="button" @click="seleccionados = []" class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900">Quitar selección</button>
                    <button type="button" @click="imprimirComplementario"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700"
                        :class="{ 'opacity-50': !esAdmin && !seleccionValidada }">
                        Ver reporte complementario
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 pb-12">
                    <p v-if="hayCambios" class="text-sm text-amber-700">Tienes cambios sin guardar</p>
                    <button type="button" @click="guardarCambios" :disabled="guardando"
                        class="px-5 py-2.5 rounded-lg text-white text-sm font-semibold"
                        :class="guardando ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'">
                        {{ guardando ? 'Guardando…' : 'Guardar cambios' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="aviso.visible" role="status" aria-live="polite"
            class="fixed top-20 right-6 z-50 max-w-md rounded-lg px-5 py-3 shadow-xl"
            :class="aviso.tipo === 'ok' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'">
            <p class="text-sm font-medium">{{ aviso.texto }}</p>
        </div>
    </AuthenticatedLayout>
</template>
