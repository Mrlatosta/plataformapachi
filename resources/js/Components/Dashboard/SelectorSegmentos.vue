<script setup>
// Control segmentado (tipo pestañas) con v-model
defineProps({
    opciones: { type: Array, required: true }, // [{ valor, label }]
    modelValue: { type: [String, Boolean], required: true },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <div class="inline-flex rounded-lg bg-[#f0efec] p-0.5" role="tablist">
        <button
            v-for="op in opciones"
            :key="String(op.valor)"
            type="button"
            role="tab"
            :aria-selected="modelValue === op.valor"
            class="rounded-md px-2.5 py-1 text-xs font-medium transition-colors"
            :class="modelValue === op.valor
                ? 'bg-white text-[#0b0b0b] shadow-sm'
                : 'text-[#52514e] hover:text-[#0b0b0b]'"
            @click="$emit('update:modelValue', op.valor)"
        >
            {{ op.label }}
        </button>
    </div>
</template>
