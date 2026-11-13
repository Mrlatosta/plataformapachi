<script setup>
import { computed } from 'vue';
import { ESTADOS_ESTUDIO, ESTADOS_ORDEN } from '@/utils/captura';

const props = defineProps({
    estado: { type: String, default: '' },
    tipo: { type: String, default: 'estudio' }, // 'estudio' | 'orden'
});

const info = computed(() => (props.tipo === 'orden' ? ESTADOS_ORDEN : ESTADOS_ESTUDIO)[props.estado]
    ?? { etiqueta: props.estado, clase: 'bg-gray-100 text-gray-600 ring-gray-200' });
</script>

<template>
    <span
        class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
        :class="info.clase"
        :title="info.ayuda"
    >
        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" aria-hidden="true" />
        {{ info.etiqueta }}
    </span>
</template>
