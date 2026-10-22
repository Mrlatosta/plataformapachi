<script setup>
// Resultado de un examen en la captura rápida: botón que alterna normal / anormal
// para los cualitativos (Negativo / Positivo) y campo de texto para los demás.
defineProps({
    valor: { type: String, default: '' },
    opciones: { type: Array, default: null },
    anormal: { type: Boolean, default: false },
    bloqueado: { type: Boolean, default: false },
    etiqueta: { type: String, required: true },
    ancho: { type: Boolean, default: false },
});

const emit = defineEmits(['alternar', 'escribir']);
</script>

<template>
    <!-- El ícono distingue normal / anormal sin depender solo del color -->
    <button
        v-if="opciones"
        type="button"
        class="inline-flex min-h-[2.5rem] w-full items-center justify-center gap-1 rounded-lg border px-2 text-xs font-bold tracking-wide transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1"
        :class="[
            anormal ? 'border-red-600 bg-red-600 text-white shadow-sm' : 'border-emerald-200 bg-emerald-50 text-emerald-800',
            bloqueado ? 'cursor-default opacity-60' : (anormal ? 'hover:bg-red-700' : 'hover:border-emerald-300 hover:bg-emerald-100'),
        ]"
        :disabled="bloqueado"
        :aria-label="`${etiqueta}: ${valor || 'vacío'}. Clic para cambiar.`"
        @click="emit('alternar')"
    >
        <svg v-if="anormal" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" /></svg>
        <svg v-else class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
        <span class="truncate">{{ valor || '—' }}</span>
    </button>
    <input
        v-else
        type="text"
        :value="valor"
        :disabled="bloqueado"
        class="min-h-[2.5rem] rounded-lg px-2 text-center text-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-transparent"
        :class="[ancho ? 'w-full' : 'w-24', anormal ? 'border-red-500 bg-red-50 font-bold text-red-700' : 'border-gray-300']"
        :aria-label="etiqueta"
        @change="emit('escribir', $event.target.value)"
    />
</template>
