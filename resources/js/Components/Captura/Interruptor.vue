<script setup>
// Interruptor accesible (role="switch"). Se usa para "Incluir en reporte" (T2).
defineProps({
    modelValue: { type: Boolean, default: false },
    etiqueta: { type: String, required: true },
    descripcion: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <label class="inline-flex cursor-pointer items-center gap-3" :class="{ 'cursor-not-allowed opacity-60': disabled }">
        <button
            type="button"
            role="switch"
            :aria-checked="modelValue"
            :disabled="disabled"
            class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
            :class="modelValue ? 'bg-emerald-600' : 'bg-gray-300'"
            @click="emit('update:modelValue', !modelValue)"
        >
            <span
                aria-hidden="true"
                class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                :class="modelValue ? 'translate-x-5' : 'translate-x-0'"
            />
        </button>
        <span class="text-sm">
            <span class="block font-medium text-gray-800">{{ etiqueta }}</span>
            <span v-if="descripcion" class="block text-xs text-gray-500">{{ descripcion }}</span>
        </span>
    </label>
</template>
