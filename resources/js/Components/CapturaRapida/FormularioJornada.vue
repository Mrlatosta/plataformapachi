<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { opcionesCualitativas } from '@/utils/rangoReferencia';
import { normalizar, RESPONSABLE_PREDETERMINADO } from '@/utils/capturaRapida';
import { moneda } from '@/utils/agenda';

const props = defineProps({
    jornada: { type: Object, default: null },
    conReportes: { type: Boolean, default: false },
    catalogo: { type: Array, required: true },
    medicos: { type: Array, default: () => [] },
});

const emit = defineEmits(['cerrar', 'guardada']);

const editando = computed(() => Boolean(props.jornada));

const hoyLocal = () => {
    const ahora = new Date();
    ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
    return ahora.toISOString().slice(0, 10);
};

// ---------- Datos generales ----------
const form = reactive({
    nombre: props.jornada?.nombre ?? '',
    empresa: props.jornada?.empresa ?? '',
    fecha_toma: props.jornada?.fecha_toma ?? `${hoyLocal()}T08:00`,
    medico_id: props.jornada?.medico_id ?? null,
    medico_solicitante: props.jornada?.medico_id ? '' : (props.jornada?.medico_solicitante ?? ''),
    aplica_iva: props.jornada?.aplica_iva ?? false,
    notas: props.jornada?.notas ?? '',
});

// Para empresas lo normal es "A quien corresponda" (sin médico)
const modoMedico = ref(props.jornada?.medico_id ? 'registrado' : (props.jornada?.medico_solicitante ? 'otro' : 'na'));

// ---------- Estudios ----------
const sinNA = (valor) => (valor && valor !== 'N/A' ? valor : '');

const estudios = ref((props.jornada?.estudios ?? []).map((e) => ({
    estudio_id: e.estudio_id,
    nombre: e.nombre,
    tipo_muestra: e.tipo_muestra ?? '',
    metodo: e.metodo ?? '',
    elaboro: e.elaboro ?? '',
    valido: e.valido ?? '',
    precio: Number(e.precio) || 0,
    examenes: e.examenes.map((x) => ({ ...x, predeterminado: x.predeterminado ?? '' })),
})));

const busquedaEstudio = ref('');

const estudiosFiltrados = computed(() => {
    const termino = normalizar(busquedaEstudio.value);
    if (!termino) return [];
    const elegidos = new Set(estudios.value.map((e) => e.estudio_id));
    return props.catalogo
        .filter((e) => !elegidos.has(e.id) && normalizar(e.nombre).includes(termino))
        .slice(0, 8);
});

const agregarEstudio = (estudio) => {
    estudios.value.push({
        estudio_id: estudio.id,
        nombre: estudio.nombre,
        tipo_muestra: sinNA(estudio.tipo_muestra),
        metodo: sinNA(estudio.metodo),
        elaboro: RESPONSABLE_PREDETERMINADO,
        valido: RESPONSABLE_PREDETERMINADO,
        precio: Number(estudio.precio) || 0,
        examenes: estudio.examenes.map((x) => ({
            id: x.id,
            nombre_examen: x.nombre_examen,
            unidad: x.unidad,
            valor_referencia: x.valor_referencia,
            predeterminado: opcionesCualitativas(x.valor_referencia)?.[0] ?? '',
        })),
    });
    busquedaEstudio.value = '';
};

const quitarEstudio = (id) => {
    estudios.value = estudios.value.filter((e) => e.estudio_id !== id);
};

const totalPorPersona = computed(() => estudios.value.reduce((suma, e) => suma + (Number(e.precio) || 0), 0));

// ---------- Guardar ----------
const errores = ref([]);
const guardando = ref(false);

const guardar = async () => {
    errores.value = [];

    const datos = {
        ...form,
        medico_id: modoMedico.value === 'registrado' ? form.medico_id : null,
        medico_solicitante: modoMedico.value === 'otro' ? form.medico_solicitante : null,
        estudios: estudios.value.map((e) => ({
            estudio_id: e.estudio_id,
            tipo_muestra: e.tipo_muestra,
            metodo: e.metodo,
            elaboro: e.elaboro,
            valido: e.valido,
            precio: e.precio,
            predeterminados: Object.fromEntries(e.examenes.map((x) => [x.id, x.predeterminado])),
        })),
    };

    guardando.value = true;
    try {
        if (editando.value) {
            const { data } = await axios.put(`/api/jornadas/${props.jornada.id}`, datos);
            emit('guardada', data.jornada);
        } else {
            const { data } = await axios.post('/api/jornadas', datos);
            emit('guardada', { id: data.id });
        }
    } catch (error) {
        const validacion = error.response?.data?.errors;
        errores.value = validacion
            ? [...new Set(Object.values(validacion).flat())]
            : [error.response?.data?.message || 'No se pudo guardar la jornada. Intenta de nuevo.'];
    } finally {
        guardando.value = false;
    }
};

const claseCampo = 'w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500';
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.esc="emit('cerrar')">
        <form
            class="flex max-h-[92vh] w-full max-w-4xl flex-col rounded-2xl bg-white shadow-2xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-jornada"
            novalidate
            @submit.prevent="guardar"
        >
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <div>
                    <h3 id="titulo-jornada" class="text-lg font-bold text-gray-800">{{ editando ? 'Configurar jornada' : 'Nueva jornada de captura rápida' }}</h3>
                    <p class="text-xs text-gray-500">Define una vez los estudios y sus resultados por defecto; después solo capturas lo que cambie por persona.</p>
                </div>
                <button type="button" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100" @click="emit('cerrar')">
                    <span class="sr-only">Cerrar</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="space-y-6 overflow-y-auto p-6">
                <div v-if="errores.length" role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    <p v-for="mensaje in errores" :key="mensaje">{{ mensaje }}</p>
                </div>
                <p v-if="conReportes" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                    Esta jornada ya tiene reportes emitidos. Los cambios solo aplican a los reportes que se generen a partir de ahora.
                </p>

                <!-- Datos generales -->
                <fieldset class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <legend class="mb-3 text-sm font-semibold text-gray-800">Datos de la jornada</legend>
                    <div>
                        <label for="jornada-nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre *</label>
                        <input id="jornada-nombre" v-model="form.nombre" type="text" required placeholder="Antidoping Empresa X – octubre" :class="claseCampo" autofocus />
                    </div>
                    <div>
                        <label for="jornada-empresa" class="mb-1 block text-sm font-medium text-gray-700">Empresa / cliente</label>
                        <input id="jornada-empresa" v-model="form.empresa" type="text" placeholder="Opcional" :class="claseCampo" />
                    </div>
                    <div>
                        <label for="jornada-fecha" class="mb-1 block text-sm font-medium text-gray-700">Toma de muestra *</label>
                        <input id="jornada-fecha" v-model="form.fecha_toma" type="datetime-local" required :class="claseCampo" />
                        <p class="mt-1 text-xs text-gray-500">Se imprime en todos los reportes de la jornada.</p>
                    </div>
                    <div class="space-y-2">
                        <span class="block text-sm font-medium text-gray-700">Médico solicitante</span>
                        <div class="inline-flex rounded-lg border border-gray-300 p-0.5 text-xs font-medium" role="radiogroup" aria-label="Médico solicitante">
                            <button
                                v-for="opcion in [{ v: 'na', t: 'A quien corresponda' }, { v: 'registrado', t: 'Registrado' }, { v: 'otro', t: 'Otro' }]"
                                :key="opcion.v"
                                type="button"
                                role="radio"
                                :aria-checked="modoMedico === opcion.v"
                                class="rounded-md px-3 py-1.5 transition-colors"
                                :class="modoMedico === opcion.v ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                                @click="modoMedico = opcion.v"
                            >{{ opcion.t }}</button>
                        </div>
                        <select v-if="modoMedico === 'registrado'" v-model="form.medico_id" :class="claseCampo" aria-label="Médico registrado">
                            <option :value="null">— Selecciona un médico —</option>
                            <option v-for="m in medicos" :key="m.id" :value="m.id">{{ m.nombre }}{{ m.especialidad ? ` (${m.especialidad})` : '' }}</option>
                        </select>
                        <input v-else-if="modoMedico === 'otro'" v-model="form.medico_solicitante" type="text" placeholder="Nombre del médico" :class="claseCampo" aria-label="Nombre del médico" />
                    </div>
                </fieldset>

                <!-- Estudios -->
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-gray-800">Estudios que se le hacen a cada persona *</legend>
                    <div class="relative">
                        <label for="jornada-buscar-estudio" class="sr-only">Buscar estudio</label>
                        <input
                            id="jornada-buscar-estudio"
                            v-model="busquedaEstudio"
                            type="search"
                            autocomplete="off"
                            placeholder="Busca el estudio, p. ej. antidoping"
                            :class="claseCampo"
                            @keydown.enter.prevent="estudiosFiltrados[0] && agregarEstudio(estudiosFiltrados[0])"
                        />
                        <ul v-if="estudiosFiltrados.length" class="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                            <li v-for="estudio in estudiosFiltrados" :key="estudio.id">
                                <button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm hover:bg-blue-50" @click="agregarEstudio(estudio)">
                                    <span class="font-medium text-gray-800">{{ estudio.nombre }}</span>
                                    <span class="shrink-0 text-xs text-gray-500">{{ estudio.examenes.length }} exámenes · {{ moneda.format(estudio.precio || 0) }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <p v-if="!estudios.length" class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                        Aún no hay estudios. Para antidoping busca "antidoping" y elige el de 5 elementos.
                    </p>

                    <div v-for="estudio in estudios" :key="estudio.estudio_id" class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <p class="font-semibold text-gray-900">{{ estudio.nombre }}</p>
                            <button type="button" class="shrink-0 rounded-lg px-2 py-1 text-sm text-red-600 hover:bg-red-50" @click="quitarEstudio(estudio.estudio_id)">Quitar</button>
                        </div>
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                            <label class="text-xs font-medium text-gray-600">Precio por persona
                                <input v-model.number="estudio.precio" type="number" min="0" step="0.01" :class="[claseCampo, 'mt-1 font-semibold']" />
                            </label>
                            <label class="text-xs font-medium text-gray-600">Tipo de muestra
                                <input v-model="estudio.tipo_muestra" type="text" :class="[claseCampo, 'mt-1']" />
                            </label>
                            <label class="text-xs font-medium text-gray-600">Método
                                <input v-model="estudio.metodo" type="text" :class="[claseCampo, 'mt-1']" />
                            </label>
                            <label class="text-xs font-medium text-gray-600">Elaboró
                                <input v-model="estudio.elaboro" type="text" :class="[claseCampo, 'mt-1']" />
                            </label>
                            <label class="text-xs font-medium text-gray-600">Validó
                                <input v-model="estudio.valido" type="text" :class="[claseCampo, 'mt-1']" />
                            </label>
                        </div>

                        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 bg-white">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100 text-left text-xs uppercase tracking-wide text-gray-600">
                                    <tr>
                                        <th class="px-3 py-2">Examen</th>
                                        <th class="px-3 py-2">Referencia</th>
                                        <th class="px-3 py-2">Resultado por defecto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="examen in estudio.examenes" :key="examen.id">
                                        <td class="px-3 py-2 text-gray-800">{{ examen.nombre_examen }}</td>
                                        <td class="px-3 py-2 text-gray-500">{{ examen.valor_referencia || '—' }} {{ examen.unidad }}</td>
                                        <td class="px-3 py-2">
                                            <div v-if="opcionesCualitativas(examen.valor_referencia)" class="inline-flex rounded-lg border border-gray-300 p-0.5 text-xs font-semibold">
                                                <button
                                                    v-for="opcion in [...opcionesCualitativas(examen.valor_referencia), '']"
                                                    :key="opcion"
                                                    type="button"
                                                    class="rounded-md px-2.5 py-1 transition-colors"
                                                    :class="examen.predeterminado === opcion ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                                                    @click="examen.predeterminado = opcion"
                                                >{{ opcion || 'Vacío' }}</button>
                                            </div>
                                            <input v-else v-model="examen.predeterminado" type="text" placeholder="Vacío" :class="[claseCampo, 'py-1.5']" :aria-label="`Resultado por defecto de ${examen.nombre_examen}`" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <legend class="sr-only">Cobro y notas</legend>
                    <label class="flex items-start gap-3 rounded-lg border border-gray-300 p-3">
                        <input v-model="form.aplica_iva" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm">
                            <span class="block font-medium text-gray-700">Aplicar IVA a las órdenes</span>
                            <span class="block text-xs text-gray-500">Total por persona sin IVA: <strong>{{ moneda.format(totalPorPersona) }}</strong></span>
                        </span>
                    </label>
                    <div>
                        <label for="jornada-notas" class="mb-1 block text-sm font-medium text-gray-700">Notas internas</label>
                        <textarea id="jornada-notas" v-model="form.notas" rows="2" placeholder="Contacto en la empresa, dirección, etc." :class="claseCampo"></textarea>
                    </div>
                </fieldset>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-6 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200" @click="emit('cerrar')">Cancelar</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50" :disabled="guardando">
                    {{ guardando ? 'Guardando…' : (editando ? 'Guardar cambios' : 'Crear jornada') }}
                </button>
            </div>
        </form>
    </div>
</template>
