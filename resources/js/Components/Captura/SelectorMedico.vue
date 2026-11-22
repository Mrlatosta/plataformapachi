<script setup>
// T12: el médico solicitante se elige de la lista (no se escribe a mano) para que
// no salga duplicado con otro nombre y su comisión se calcule sola.
// "+ Nuevo médico" lo da de alta; si ya existía escrito de otra forma, se elige ese.
import { computed, nextTick, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: { type: [Number, null], default: null },
    medicos: { type: Array, default: () => [] },
    // Órdenes anteriores con el nombre escrito a mano y sin médico de la lista
    legado: { type: String, default: '' },
    id: { type: String, default: 'medico-solicitante' },
});

const emit = defineEmits(['update:modelValue', 'nombre', 'creado']);

const valor = computed({
    get: () => props.modelValue ?? null,
    set: (id) => {
        emit('update:modelValue', id);
        emit('nombre', props.medicos.find((m) => m.id === id)?.nombre ?? '');
    },
});

const activos = computed(() => props.medicos.filter((m) => m.activo || m.id === props.modelValue));

const agregando = ref(false);
const nuevo = ref({ nombre: '', especialidad: '' });
const guardando = ref(false);
const mensaje = ref('');
const error = ref('');

const abrirNuevo = async () => {
    agregando.value = true;
    error.value = '';
    mensaje.value = '';
    nuevo.value = { nombre: props.legado && !props.modelValue ? props.legado : '', especialidad: '' };
    await nextTick();
    document.getElementById(`${props.id}-nuevo`)?.focus();
};

const guardarNuevo = async () => {
    if (!nuevo.value.nombre.trim() || guardando.value) return;
    guardando.value = true;
    error.value = '';
    try {
        const { data } = await axios.post('/api/medicos/rapido', nuevo.value);
        emit('creado', data);
        await nextTick();
        valor.value = data.id;
        mensaje.value = data.existente ? `Ya estaba registrado como «${data.nombre}»; quedó elegido.` : `${data.nombre} agregado.`;
        agregando.value = false;
    } catch (e) {
        const errores = e.response?.data?.errors;
        error.value = errores ? Object.values(errores).flat()[0] : (e.response?.data?.message || 'No se pudo agregar');
    } finally {
        guardando.value = false;
    }
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex gap-2">
            <select :id="id" v-model="valor"
                class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-blue-500">
                <option :value="null">A quien corresponda (sin médico)</option>
                <option v-for="m in activos" :key="m.id" :value="m.id">
                    {{ m.nombre }}{{ m.especialidad ? ` (${m.especialidad})` : '' }}
                </option>
            </select>
            <button type="button" @click="abrirNuevo"
                class="shrink-0 rounded-lg border border-blue-300 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                + Nuevo médico
            </button>
        </div>

        <p v-if="legado && !modelValue" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
            Esta orden tenía escrito «{{ legado }}». Elígelo de la lista (o agrégalo) para que cuente su comisión.
        </p>
        <p v-if="mensaje" role="status" class="text-xs text-emerald-700">{{ mensaje }}</p>

        <div v-if="agregando" class="rounded-lg border border-blue-200 bg-blue-50/50 p-3 space-y-2">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div>
                    <label :for="`${id}-nuevo`" class="block text-xs font-medium text-gray-700">Nombre del médico</label>
                    <input :id="`${id}-nuevo`" v-model="nuevo.nombre" type="text" placeholder="Dr. Nombre Apellido"
                        class="w-full rounded-lg border-gray-300 px-3 py-1.5 text-sm" @keydown.enter.prevent="guardarNuevo" />
                </div>
                <div>
                    <label :for="`${id}-esp`" class="block text-xs font-medium text-gray-700">Especialidad <span class="text-gray-400">(opcional)</span></label>
                    <input :id="`${id}-esp`" v-model="nuevo.especialidad" type="text"
                        class="w-full rounded-lg border-gray-300 px-3 py-1.5 text-sm" @keydown.enter.prevent="guardarNuevo" />
                </div>
            </div>
            <p v-if="error" class="text-xs text-red-700">{{ error }}</p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="agregando = false" class="rounded-lg px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100">Cancelar</button>
                <button type="button" @click="guardarNuevo" :disabled="guardando || !nuevo.nombre.trim()"
                    class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                    {{ guardando ? 'Agregando…' : 'Agregar y elegir' }}
                </button>
            </div>
        </div>
    </div>
</template>
