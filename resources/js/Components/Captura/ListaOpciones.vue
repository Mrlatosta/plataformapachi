<script setup>
// T13: tipo de muestra / método se eligen de una lista. Ya viene en el valor de
// siempre del estudio; las demás opciones las configura el administrador en
// Gestión de estudios. Recepción ve la lista bloqueada.
import { computed } from 'vue';

const props = defineProps({
    id: { type: String, required: true },
    modelValue: { type: String, default: '' },
    deSiempre: { type: String, default: '' },
    opciones: { type: Array, default: () => [] },
    // Opciones comunes para todos los estudios (p. ej. tipos de muestra)
    comunes: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const limpio = (v) => (String(v ?? '').trim() === 'N/A' ? '' : String(v ?? '').trim());

// Sin repetidos (sin importar mayúsculas); primero la de siempre y la elegida
const lista = computed(() => {
    const vistas = new Set();
    return [props.deSiempre, props.modelValue, ...props.opciones, ...props.comunes]
        .map(limpio)
        .filter((v) => {
            const clave = v.toLowerCase();
            if (!v || vistas.has(clave)) return false;
            vistas.add(clave);
            return true;
        });
});

const valor = computed({
    get: () => limpio(props.modelValue),
    set: (v) => emit('update:modelValue', v),
});

const esDeSiempre = (v) => v.toLowerCase() === limpio(props.deSiempre).toLowerCase();
</script>

<template>
    <select
        :id="id"
        v-model="valor"
        :disabled="disabled"
        :title="disabled ? 'Solo el administrador lo cambia' : ''"
        class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500 disabled:bg-gray-50 disabled:text-gray-600"
        :class="!esDeSiempre(valor) && valor ? 'border-amber-400 bg-amber-50' : ''"
    >
        <option value="">—</option>
        <option v-for="opcion in lista" :key="opcion" :value="opcion">
            {{ opcion }}{{ esDeSiempre(opcion) ? ' (de siempre)' : '' }}
        </option>
    </select>
</template>
