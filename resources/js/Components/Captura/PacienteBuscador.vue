<script setup>
// T1: campo de nombre que sugiere pacientes ya registrados mientras se escribe.
// Combobox accesible: flechas para moverse, Enter para elegir, Esc para cerrar.
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import { formatoFechaCorta } from '@/utils/captura';

const props = defineProps({
    modelValue: { type: String, default: '' },
    id: { type: String, default: 'paciente-nombre' },
    readonly: { type: Boolean, default: false },
    placeholder: { type: String, default: 'Escribe nombre y apellidos' },
});

const emit = defineEmits(['update:modelValue', 'seleccionar']);

const sugerencias = ref([]);
const abierto = ref(false);
const activo = ref(-1);
const buscando = ref(false);
let temporizador = null;
let ultimaConsulta = 0;

const listaId = computed(() => `${props.id}-sugerencias`);

const buscar = (texto) => {
    clearTimeout(temporizador);
    if (texto.trim().length < 2) {
        sugerencias.value = [];
        abierto.value = false;
        return;
    }

    temporizador = setTimeout(async () => {
        const consulta = ++ultimaConsulta;
        buscando.value = true;
        try {
            const { data } = await axios.get('/api/pacientes/buscar', { params: { q: texto } });
            if (consulta !== ultimaConsulta) return; // llegó una respuesta vieja
            sugerencias.value = data;
            activo.value = data.length ? 0 : -1;
            abierto.value = true;
        } catch (e) {
            console.error('Error al buscar pacientes', e);
        } finally {
            if (consulta === ultimaConsulta) buscando.value = false;
        }
    }, 180);
};

const onInput = (evento) => {
    emit('update:modelValue', evento.target.value);
    buscar(evento.target.value);
};

const elegir = (paciente) => {
    emit('seleccionar', paciente);
    cerrar();
};

const cerrar = () => {
    abierto.value = false;
    activo.value = -1;
};

const onKeydown = (evento) => {
    if (!abierto.value || !sugerencias.value.length) {
        if (evento.key === 'ArrowDown' && props.modelValue.trim().length >= 2) buscar(props.modelValue);
        return;
    }

    if (evento.key === 'ArrowDown') {
        evento.preventDefault();
        activo.value = (activo.value + 1) % sugerencias.value.length;
    } else if (evento.key === 'ArrowUp') {
        evento.preventDefault();
        activo.value = (activo.value - 1 + sugerencias.value.length) % sugerencias.value.length;
    } else if (evento.key === 'Enter' && activo.value >= 0) {
        evento.preventDefault();
        elegir(sugerencias.value[activo.value]);
    } else if (evento.key === 'Escape') {
        cerrar();
    }
};

// Cerrar al salir del campo, dejando tiempo al clic sobre una sugerencia
const onBlur = () => setTimeout(cerrar, 150);

watch(() => props.readonly, (soloLectura) => soloLectura && cerrar());
onBeforeUnmount(() => clearTimeout(temporizador));

// Resalta en negritas lo que se escribió
const resaltar = (nombre) => {
    const palabras = props.modelValue.trim().split(/\s+/).filter((p) => p.length > 1);
    if (!palabras.length) return [{ texto: nombre, marca: false }];

    const quitarAcentos = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const base = quitarAcentos(nombre);
    const marcas = new Array(nombre.length).fill(false);
    palabras.forEach((p) => {
        const q = quitarAcentos(p);
        let i = base.indexOf(q);
        while (i !== -1) {
            for (let k = i; k < i + q.length; k++) marcas[k] = true;
            i = base.indexOf(q, i + q.length);
        }
    });

    const partes = [];
    for (let i = 0; i < nombre.length; i++) {
        const ultima = partes[partes.length - 1];
        if (ultima && ultima.marca === marcas[i]) ultima.texto += nombre[i];
        else partes.push({ texto: nombre[i], marca: marcas[i] });
    }
    return partes;
};
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            type="text"
            role="combobox"
            autocomplete="off"
            :aria-expanded="abierto"
            :aria-controls="listaId"
            aria-autocomplete="list"
            :aria-activedescendant="activo >= 0 ? `${listaId}-${activo}` : undefined"
            :value="modelValue"
            :readonly="readonly"
            :placeholder="placeholder"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
            :class="{ 'bg-gray-50 text-gray-700': readonly }"
            @input="onInput"
            @keydown="onKeydown"
            @blur="onBlur"
            @focus="modelValue.trim().length >= 2 && !readonly && buscar(modelValue)"
        />
        <span v-if="buscando" class="absolute right-3 top-2.5 text-xs text-gray-400" aria-hidden="true">Buscando…</span>

        <ul
            v-show="abierto"
            :id="listaId"
            role="listbox"
            class="absolute z-50 mt-1 w-full max-h-72 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
        >
            <li
                v-for="(paciente, i) in sugerencias"
                :id="`${listaId}-${i}`"
                :key="paciente.id"
                role="option"
                :aria-selected="i === activo"
                class="cursor-pointer border-b border-gray-100 px-4 py-2.5 last:border-b-0"
                :class="i === activo ? 'bg-emerald-50' : 'hover:bg-gray-50'"
                @mousedown.prevent="elegir(paciente)"
                @mouseenter="activo = i"
            >
                <p class="text-sm text-gray-900">
                    <template v-for="(parte, k) in resaltar(paciente.nombre)" :key="k">
                        <strong v-if="parte.marca" class="font-semibold">{{ parte.texto }}</strong>
                        <span v-else>{{ parte.texto }}</span>
                    </template>
                </p>
                <p class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-gray-500">
                    <span>Nac. {{ paciente.fecha_nacimiento ? formatoFechaCorta(paciente.fecha_nacimiento + 'T00:00:00') : 'sin fecha' }}</span>
                    <span v-if="paciente.telefono">Tel. {{ paciente.telefono }}</span>
                    <span v-if="paciente.ordenes">{{ paciente.ordenes }} {{ paciente.ordenes === 1 ? 'orden' : 'órdenes' }}</span>
                </p>
            </li>
            <li v-if="!sugerencias.length" class="px-4 py-3 text-sm text-gray-500">
                No hay pacientes con ese nombre. Al guardar se registrará como paciente nuevo.
            </li>
            <li v-else class="bg-gray-50 px-4 py-2 text-xs text-gray-500">
                ¿No es ninguno? Sigue escribiendo para registrar un paciente nuevo.
            </li>
        </ul>
    </div>
</template>
