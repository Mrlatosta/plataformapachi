<script setup>
defineProps({
    titulo: { type: String, required: true },
    subtitulo: { type: String, default: '' },
});
</script>

<template>
    <section class="flex min-w-0 flex-col rounded-xl bg-[#fcfcfb] p-5 shadow-sm ring-1 ring-black/5">
        <header class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="m-0 text-base font-semibold text-[#0b0b0b]">{{ titulo }}</h3>
                <p v-if="subtitulo" class="m-0 mt-0.5 text-xs text-[#52514e]">{{ subtitulo }}</p>
            </div>
            <div v-if="$slots.acciones" class="flex flex-wrap items-center gap-2">
                <slot name="acciones" />
            </div>
        </header>
        <div class="flex-1">
            <slot />
        </div>
        <footer v-if="$slots.pie" class="mt-4 border-t border-[#e1e0d9] pt-3 text-xs text-[#52514e]">
            <slot name="pie" />
        </footer>
    </section>
</template>
