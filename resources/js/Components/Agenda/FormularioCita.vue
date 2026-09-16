<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { aMinutos, deMinutos, avisosCita, indicacionesSugeridas, moneda, formatoFecha, ESTILOS_ESTADO } from '@/utils/agenda';

const props = defineProps({
    cita: { type: Object, default: null },
    inicial: { type: Object, default: () => ({}) },
    config: { type: Object, required: true },
    catalogo: { type: Array, required: true },
    pacientes: { type: Array, default: () => [] },
    cargandoPacientes: { type: Boolean, default: false },
});

const emit = defineEmits(['cerrar', 'guardada']);

const editando = computed(() => Boolean(props.cita));
const normalizar = (texto) => String(texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

// ---------- Estado del formulario ----------
const form = reactive({
    paciente_id: props.cita?.paciente_id ?? null,
    nombre: props.cita?.nombre ?? '',
    telefono: props.cita?.telefono ?? '',
    email: props.cita?.email ?? '',
    guardar_paciente: true,
    fecha: props.cita?.fecha ?? props.inicial.fecha ?? '',
    hora_inicio: props.cita?.hora_inicio ?? props.inicial.hora ?? '08:00',
    duracion_minutos: props.cita?.duracion_minutos ?? props.config.duracion_predeterminada,
    modalidad: props.cita?.modalidad ?? 'laboratorio',
    direccion: props.cita?.direccion ?? '',
    estado: props.cita?.estado ?? 'programada',
    origen: props.cita?.origen ?? 'whatsapp',
    indicaciones: props.cita?.indicaciones ?? '',
    notas: props.cita?.notas ?? '',
});

const modoPaciente = ref(props.cita && !props.cita.paciente_id ? 'nuevo' : 'registrado');
const pacienteSeleccionado = ref(props.cita?.paciente ?? null);
const busquedaPaciente = ref('');
const estudios = ref((props.cita?.estudios ?? [])
    .filter((e) => e.estudio_id)
    .map((e) => ({ id: e.estudio_id, nombre: e.nombre, precio: Number(e.precio) })));
const busquedaEstudio = ref('');
const errores = ref({});
const errorGeneral = ref('');
const guardando = ref(false);
const avisoIndicaciones = ref('');

// ---------- Paciente ----------
const pacientesFiltrados = computed(() => {
    const termino = normalizar(busquedaPaciente.value);
    if (!termino) return [];
    return props.pacientes
        .filter((p) => normalizar(p.nombre).includes(termino) || String(p.telefono ?? '').includes(termino))
        .slice(0, 8);
});

const seleccionarPaciente = (paciente) => {
    pacienteSeleccionado.value = paciente;
    form.paciente_id = paciente.id;
    form.nombre = paciente.nombre;
    form.telefono = paciente.telefono ?? '';
    form.email = paciente.email ?? '';
    if (form.modalidad === 'domicilio' && !form.direccion) form.direccion = paciente.direccion ?? '';
    busquedaPaciente.value = '';
};

const quitarPaciente = () => {
    pacienteSeleccionado.value = null;
    form.paciente_id = null;
};

const cambiarModoPaciente = (modo) => {
    modoPaciente.value = modo;
    if (modo === 'nuevo') quitarPaciente();
};

watch(() => form.modalidad, (modalidad) => {
    if (modalidad === 'domicilio' && !form.direccion && pacienteSeleccionado.value?.direccion) {
        form.direccion = pacienteSeleccionado.value.direccion;
    }
});

// ---------- Estudios ----------
const estudiosFiltrados = computed(() => {
    const termino = normalizar(busquedaEstudio.value);
    if (!termino) return [];
    const elegidos = new Set(estudios.value.map((e) => e.id));
    return props.catalogo
        .filter((e) => !elegidos.has(e.id) && normalizar(e.nombre).includes(termino))
        .slice(0, 8);
});

const agregarEstudio = (estudio) => {
    estudios.value.push({ id: estudio.id, nombre: estudio.nombre, precio: Number(estudio.precio) });
    busquedaEstudio.value = '';
};

const quitarEstudio = (id) => {
    estudios.value = estudios.value.filter((e) => e.id !== id);
};

const total = computed(() => estudios.value.reduce((suma, e) => suma + (e.precio || 0), 0));

const sugerirIndicaciones = () => {
    const sugerencia = indicacionesSugeridas(estudios.value);
    if (sugerencia) {
        form.indicaciones = sugerencia;
        avisoIndicaciones.value = '';
    } else {
        avisoIndicaciones.value = estudios.value.length
            ? 'Estos estudios no requieren preparación especial.'
            : 'Agrega primero los estudios para sugerir la preparación.';
    }
};

// ---------- Fecha, hora y avisos ----------
const duraciones = computed(() => {
    const lista = [...props.config.duraciones];
    if (!lista.includes(Number(form.duracion_minutos))) lista.push(Number(form.duracion_minutos));
    return lista.sort((a, b) => a - b);
});

const horaFin = computed(() => {
    if (!form.hora_inicio) return '';
    const fin = aMinutos(form.hora_inicio) + Number(form.duracion_minutos);
    return fin >= 24 * 60 ? '' : deMinutos(fin);
});

const citasDelDia = ref([]);
const cargandoDia = ref(false);

const cargarDia = async () => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(form.fecha)) {
        citasDelDia.value = [];
        return;
    }
    cargandoDia.value = true;
    try {
        const { data } = await axios.get('/api/citas', { params: { desde: form.fecha, hasta: form.fecha } });
        citasDelDia.value = data;
    } catch {
        citasDelDia.value = [];
    } finally {
        cargandoDia.value = false;
    }
};

watch(() => form.fecha, cargarDia);
onMounted(cargarDia);

const otrasCitasDelDia = computed(() => citasDelDia.value.filter((c) => c.id !== props.cita?.id));

const avisos = computed(() => avisosCita(form, {
    horario: props.config.horario,
    zona: props.config.zona_horaria,
    citasDelDia: citasDelDia.value,
    idActual: props.cita?.id ?? null,
}));

// ---------- Guardar ----------
const err = (campo) => errores.value[campo]?.[0] ?? '';

const guardar = async () => {
    errores.value = {};
    errorGeneral.value = '';

    if (modoPaciente.value === 'registrado' && !form.paciente_id) {
        errores.value = { paciente_id: ['Elige un paciente de la lista, o cambia a "Nuevo o sin registro".'] };
        errorGeneral.value = 'Revisa los campos marcados en rojo.';
        return;
    }

    const datos = {
        ...form,
        paciente_id: modoPaciente.value === 'registrado' ? form.paciente_id : null,
        guardar_paciente: modoPaciente.value === 'nuevo' && form.guardar_paciente,
        estudios: estudios.value.map((e) => e.id),
    };

    guardando.value = true;
    try {
        const { data } = editando.value
            ? await axios.put(`/api/citas/${props.cita.id}`, datos)
            : await axios.post('/api/citas', datos);
        emit('guardada', data.cita, editando.value);
    } catch (error) {
        errores.value = error.response?.data?.errors ?? {};
        errorGeneral.value = error.response?.status === 422
            ? 'Revisa los campos marcados en rojo.'
            : (error.response?.data?.message || 'No se pudo guardar la cita. Intenta de nuevo.');
    } finally {
        guardando.value = false;
    }
};

const claseCampo = 'w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500';
const claseError = 'border-red-400 bg-red-50';
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.esc="emit('cerrar')">
        <form
            class="flex max-h-[92vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-2xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-cita"
            novalidate
            @submit.prevent="guardar"
        >
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h3 id="titulo-cita" class="text-lg font-bold text-gray-800">{{ editando ? 'Editar cita' : 'Nueva cita' }}</h3>
                <button type="button" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100" @click="emit('cerrar')">
                    <span class="sr-only">Cerrar</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="space-y-6 overflow-y-auto p-6">
                <div v-if="errorGeneral" role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ errorGeneral }}</div>

                <!-- Paciente -->
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-gray-800">Paciente</legend>
                    <div class="inline-flex rounded-lg border border-gray-300 p-0.5" role="radiogroup" aria-label="Tipo de paciente">
                        <button
                            v-for="opcion in [{ v: 'registrado', t: 'Paciente registrado' }, { v: 'nuevo', t: 'Nuevo o sin registro' }]"
                            :key="opcion.v"
                            type="button"
                            role="radio"
                            :aria-checked="modoPaciente === opcion.v"
                            class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="modoPaciente === opcion.v ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                            @click="cambiarModoPaciente(opcion.v)"
                        >{{ opcion.t }}</button>
                    </div>

                    <template v-if="modoPaciente === 'registrado'">
                        <div v-if="pacienteSeleccionado" class="flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-emerald-900">{{ pacienteSeleccionado.nombre }}</p>
                                <p class="text-xs text-emerald-800">{{ pacienteSeleccionado.telefono || 'Sin teléfono' }}<span v-if="pacienteSeleccionado.email"> · {{ pacienteSeleccionado.email }}</span></p>
                            </div>
                            <button type="button" class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium text-emerald-800 hover:bg-emerald-100" @click="quitarPaciente">Cambiar</button>
                        </div>
                        <div v-else class="relative">
                            <label for="cita-buscar-paciente" class="mb-1 block text-sm font-medium text-gray-700">Buscar paciente *</label>
                            <input
                                id="cita-buscar-paciente"
                                v-model="busquedaPaciente"
                                type="search"
                                autocomplete="off"
                                placeholder="Nombre o teléfono"
                                :class="[claseCampo, err('paciente_id') && claseError]"
                                :aria-invalid="!!err('paciente_id')"
                                aria-describedby="cita-paciente-ayuda"
                                autofocus
                            />
                            <p id="cita-paciente-ayuda" class="mt-1 text-xs" :class="err('paciente_id') ? 'text-sm text-red-600' : 'text-gray-500'">
                                {{ err('paciente_id') || (cargandoPacientes ? 'Cargando pacientes…' : 'Escribe al menos 2 letras del nombre.') }}
                            </p>
                            <ul v-if="pacientesFiltrados.length" class="absolute z-10 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg" role="listbox">
                                <li v-for="p in pacientesFiltrados" :key="p.id">
                                    <button type="button" class="w-full px-4 py-2 text-left text-sm hover:bg-blue-50 focus:bg-blue-50 focus:outline-none" @click="seleccionarPaciente(p)">
                                        <span class="font-medium text-gray-900">{{ p.nombre }}</span>
                                        <span class="block text-xs text-gray-500">{{ p.telefono || 'Sin teléfono' }}</span>
                                    </button>
                                </li>
                            </ul>
                            <p v-else-if="busquedaPaciente.length >= 2 && !cargandoPacientes" class="mt-1 text-sm text-gray-600">
                                No encontramos a "{{ busquedaPaciente }}".
                                <button type="button" class="font-semibold text-blue-700 hover:underline" @click="form.nombre = busquedaPaciente; cambiarModoPaciente('nuevo')">Agendarlo como nuevo</button>
                            </p>
                        </div>
                    </template>

                    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-3">
                            <label for="cita-nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre completo *</label>
                            <input id="cita-nombre" v-model="form.nombre" type="text" maxlength="255" autocomplete="off" :class="[claseCampo, err('nombre') && claseError]" :aria-invalid="!!err('nombre')" aria-describedby="cita-nombre-error" autofocus />
                            <p id="cita-nombre-error" class="mt-1 text-sm text-red-600">{{ err('nombre') }}</p>
                        </div>
                        <div>
                            <label for="cita-telefono" class="mb-1 block text-sm font-medium text-gray-700">WhatsApp / teléfono</label>
                            <input id="cita-telefono" v-model="form.telefono" type="tel" inputmode="tel" maxlength="20" placeholder="998 123 4567" :class="[claseCampo, err('telefono') && claseError]" aria-describedby="cita-telefono-ayuda" />
                            <p id="cita-telefono-ayuda" class="mt-1 text-xs" :class="err('telefono') ? 'text-sm text-red-600' : 'text-gray-500'">{{ err('telefono') || 'Con 10 dígitos para poder avisarle por WhatsApp.' }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="cita-email" class="mb-1 block text-sm font-medium text-gray-700">Correo <span class="font-normal text-gray-500">(opcional)</span></label>
                            <input id="cita-email" v-model="form.email" type="email" maxlength="255" :class="[claseCampo, err('email') && claseError]" />
                            <p class="mt-1 text-sm text-red-600">{{ err('email') }}</p>
                        </div>
                        <label v-if="!editando" class="flex items-start gap-2 text-sm sm:col-span-3">
                            <input v-model="form.guardar_paciente" type="checkbox" class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            <span>Guardarlo también en <strong>Pacientes</strong> <span class="text-gray-500">(recomendado si vendrá a hacerse estudios)</span></span>
                        </label>
                    </div>
                </fieldset>

                <!-- Cuándo -->
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-gray-800">Cuándo</legend>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="cita-fecha" class="mb-1 block text-sm font-medium text-gray-700">Fecha *</label>
                            <input id="cita-fecha" v-model="form.fecha" type="date" :class="[claseCampo, err('fecha') && claseError]" :aria-invalid="!!err('fecha')" />
                            <p class="mt-1 text-sm text-red-600">{{ err('fecha') }}</p>
                        </div>
                        <div>
                            <label for="cita-hora" class="mb-1 block text-sm font-medium text-gray-700">Hora *</label>
                            <input id="cita-hora" v-model="form.hora_inicio" type="time" step="900" :class="[claseCampo, err('hora_inicio') && claseError]" :aria-invalid="!!err('hora_inicio')" />
                            <p class="mt-1 text-sm text-red-600">{{ err('hora_inicio') }}</p>
                        </div>
                        <div>
                            <label for="cita-duracion" class="mb-1 block text-sm font-medium text-gray-700">Duración</label>
                            <select id="cita-duracion" v-model.number="form.duracion_minutos" :class="[claseCampo, err('duracion_minutos') && claseError]" aria-describedby="cita-duracion-ayuda">
                                <option v-for="d in duraciones" :key="d" :value="d">{{ d < 60 ? `${d} min` : `${d / 60} h`.replace('.5 h', ' h 30 min') }}</option>
                            </select>
                            <p id="cita-duracion-ayuda" class="mt-1 text-xs" :class="err('duracion_minutos') ? 'text-sm text-red-600' : 'text-gray-500'">
                                {{ err('duracion_minutos') || (horaFin ? `Termina a las ${horaFin}` : '') }}
                            </p>
                        </div>
                    </div>

                    <div v-if="avisos.length" role="status" class="space-y-1 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p v-for="aviso in avisos" :key="aviso" class="flex gap-2"><span aria-hidden="true">⚠</span>{{ aviso }}</p>
                        <p class="text-xs text-amber-800">Puedes guardar de todos modos.</p>
                    </div>

                    <details v-if="form.fecha" class="rounded-lg border border-gray-200 text-sm" :open="otrasCitasDelDia.length > 0 && otrasCitasDelDia.length <= 6">
                        <summary class="cursor-pointer px-4 py-2 font-medium text-gray-700">
                            {{ cargandoDia ? 'Revisando la agenda…' : `Agenda del ${formatoFecha(form.fecha, { weekday: 'long', day: 'numeric', month: 'long' })}: ${otrasCitasDelDia.length} ${otrasCitasDelDia.length === 1 ? 'cita' : 'citas'}` }}
                        </summary>
                        <ul v-if="otrasCitasDelDia.length" class="divide-y divide-gray-100 border-t border-gray-100">
                            <li v-for="c in otrasCitasDelDia" :key="c.id" class="flex items-center gap-3 px-4 py-1.5" :class="['cancelada', 'no_asistio'].includes(c.estado) && 'text-gray-400 line-through'">
                                <span class="h-2 w-2 shrink-0 rounded-full" :class="ESTILOS_ESTADO[c.estado]?.punto" aria-hidden="true"></span>
                                <span class="w-24 shrink-0 font-medium">{{ c.hora_inicio }}–{{ c.hora_fin }}</span>
                                <span class="truncate">{{ c.nombre }}</span>
                                <span v-if="c.modalidad === 'domicilio'" class="ml-auto shrink-0 text-xs text-orange-700">Domicilio</span>
                            </li>
                        </ul>
                        <p v-else-if="!cargandoDia" class="border-t border-gray-100 px-4 py-2 text-gray-500">Día libre.</p>
                    </details>
                </fieldset>

                <!-- Dónde -->
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-gray-800">Dónde</legend>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label
                            v-for="opcion in [{ v: 'laboratorio', t: 'En el laboratorio', d: 'El paciente viene a BIOLAB.' }, { v: 'domicilio', t: 'A domicilio', d: 'Vamos a tomar la muestra.' }]"
                            :key="opcion.v"
                            class="flex cursor-pointer gap-3 rounded-lg border p-3 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-500"
                        >
                            <input v-model="form.modalidad" type="radio" name="cita-modalidad" :value="opcion.v" class="mt-1 text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm"><span class="font-semibold text-gray-800">{{ opcion.t }}</span><br /><span class="text-gray-500">{{ opcion.d }}</span></span>
                        </label>
                    </div>
                    <div v-if="form.modalidad === 'domicilio'">
                        <label for="cita-direccion" class="mb-1 block text-sm font-medium text-gray-700">Dirección *</label>
                        <textarea id="cita-direccion" v-model="form.direccion" rows="2" maxlength="500" placeholder="Calle, número, colonia (SM/Región) y referencias" :class="[claseCampo, err('direccion') && claseError]" :aria-invalid="!!err('direccion')" aria-describedby="cita-direccion-error"></textarea>
                        <p id="cita-direccion-error" class="mt-1 text-sm text-red-600">{{ err('direccion') }}</p>
                    </div>
                </fieldset>

                <!-- Estudios -->
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-gray-800">Estudios</legend>
                    <div class="relative">
                        <label for="cita-buscar-estudio" class="sr-only">Buscar estudio para agregar</label>
                        <input id="cita-buscar-estudio" v-model="busquedaEstudio" type="search" autocomplete="off" placeholder="Buscar estudio para agregar (ej. biometría)" :class="[claseCampo, err('estudios') && claseError]" />
                        <ul v-if="estudiosFiltrados.length" class="absolute z-10 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                            <li v-for="e in estudiosFiltrados" :key="e.id">
                                <button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm hover:bg-blue-50 focus:bg-blue-50 focus:outline-none" @click="agregarEstudio(e)">
                                    <span class="text-gray-900">{{ e.nombre }}</span>
                                    <span class="shrink-0 font-semibold text-gray-700">{{ moneda.format(e.precio) }}</span>
                                </button>
                            </li>
                        </ul>
                        <p v-else-if="busquedaEstudio.length >= 2" class="mt-1 text-sm text-gray-500">No hay estudios con ese nombre en el catálogo.</p>
                        <p v-if="err('estudios') || err('estudios.0')" class="mt-1 text-sm text-red-600">{{ err('estudios') || err('estudios.0') }}</p>
                    </div>

                    <ul v-if="estudios.length" class="divide-y divide-gray-100 rounded-lg border border-gray-200">
                        <li v-for="e in estudios" :key="e.id" class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                            <span class="text-gray-800">{{ e.nombre }}</span>
                            <span class="flex shrink-0 items-center gap-3">
                                <span class="font-medium text-gray-700">{{ moneda.format(e.precio) }}</span>
                                <button type="button" class="rounded p-1 text-red-600 hover:bg-red-50" @click="quitarEstudio(e.id)">
                                    <span class="sr-only">Quitar {{ e.nombre }}</span>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </span>
                        </li>
                        <li class="flex justify-between bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-900">
                            <span>Total estimado</span><span>{{ moneda.format(total) }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">Opcional: puedes agendar sin estudios y definirlos después.</p>
                </fieldset>

                <!-- Detalles -->
                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold text-gray-800">Detalles</legend>
                    <div>
                        <div class="mb-1 flex items-center justify-between gap-3">
                            <label for="cita-indicaciones" class="block text-sm font-medium text-gray-700">Indicaciones para el paciente</label>
                            <button type="button" class="text-sm font-semibold text-blue-700 hover:underline" @click="sugerirIndicaciones">Sugerir según los estudios</button>
                        </div>
                        <textarea id="cita-indicaciones" v-model="form.indicaciones" rows="2" maxlength="1000" placeholder="Ej. Acudir con 8 horas de ayuno" :class="claseCampo" aria-describedby="cita-indicaciones-ayuda"></textarea>
                        <p id="cita-indicaciones-ayuda" class="mt-1 text-xs text-gray-500">{{ avisoIndicaciones || 'Se incluyen en el mensaje de WhatsApp de confirmación.' }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="cita-origen" class="mb-1 block text-sm font-medium text-gray-700">¿Cómo agendó?</label>
                            <select id="cita-origen" v-model="form.origen" :class="claseCampo">
                                <option v-for="(etiqueta, valor) in config.origenes" :key="valor" :value="valor">{{ etiqueta }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="cita-estado" class="mb-1 block text-sm font-medium text-gray-700">Estado</label>
                            <select id="cita-estado" v-model="form.estado" :class="claseCampo">
                                <option v-for="(etiqueta, valor) in config.estados" :key="valor" :value="valor">{{ etiqueta }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="cita-notas" class="mb-1 block text-sm font-medium text-gray-700">Notas internas <span class="font-normal text-gray-500">(el paciente no las ve)</span></label>
                        <textarea id="cita-notas" v-model="form.notas" rows="2" maxlength="1000" :class="claseCampo"></textarea>
                    </div>
                </fieldset>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                <button type="button" class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200" @click="emit('cerrar')">Cancelar</button>
                <button type="submit" :disabled="guardando" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <svg v-if="guardando" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" /></svg>
                    {{ guardando ? 'Guardando…' : (editando ? 'Guardar cambios' : 'Agendar cita') }}
                </button>
            </div>
        </form>
    </div>
</template>
