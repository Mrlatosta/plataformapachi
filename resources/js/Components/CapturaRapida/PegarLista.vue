<script setup>
import { ref, computed } from 'vue';
import { interpretarLista } from '@/utils/capturaRapida';

defineProps({
    guardando: { type: Boolean, default: false },
});

const emit = defineEmits(['cerrar', 'agregar']);

const texto = ref('');
const personas = computed(() => interpretarLista(texto.value));
const sinSexo = computed(() => personas.value.filter((p) => !p.sexo).length);
const sinEdad = computed(() => personas.value.filter((p) => p.edad === null).length);

const formatoFecha = (iso) => (iso ? iso.split('-').reverse().join('/') : '');
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.esc="emit('cerrar')">
        <form
            class="flex max-h-[92vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-2xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-pegar"
            @submit.prevent="personas.length && emit('agregar', personas)"
        >
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <div>
                    <h3 id="titulo-pegar" class="text-lg font-bold text-gray-800">Pegar lista de colaboradores</h3>
                    <p class="text-xs text-gray-500">Copia las columnas desde Excel y pégalas aquí: una persona por renglón.</p>
                </div>
                <button type="button" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100" @click="emit('cerrar')">
                    <span class="sr-only">Cerrar</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="space-y-4 overflow-y-auto p-6">
                <div>
                    <label for="pegar-lista" class="mb-1 block text-sm font-medium text-gray-700">Lista</label>
                    <textarea
                        id="pegar-lista"
                        v-model="texto"
                        rows="8"
                        autofocus
                        class="w-full rounded-lg border-gray-300 font-mono text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Pérez López Juan	M	15/03/1990&#10;Ruiz Gómez María	F	35"
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Columnas en cualquier orden: nombre (puede venir en varias columnas), sexo (M/F, H/M, Hombre/Mujer) y fecha de nacimiento (dd/mm/aaaa) o edad. Los datos que falten se completan después.
                    </p>
                </div>

                <div v-if="personas.length" class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-semibold text-gray-800">{{ personas.length }} colaboradores detectados</span>
                        <span v-if="sinSexo" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ sinSexo }} sin sexo</span>
                        <span v-if="sinEdad" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ sinEdad }} sin edad</span>
                    </div>
                    <div class="max-h-64 overflow-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-sm">
                            <thead class="sticky top-0 bg-gray-100 text-left text-xs uppercase tracking-wide text-gray-600">
                                <tr>
                                    <th class="px-3 py-2">#</th>
                                    <th class="px-3 py-2">Nombre</th>
                                    <th class="px-3 py-2">Sexo</th>
                                    <th class="px-3 py-2">F. nacimiento</th>
                                    <th class="px-3 py-2">Edad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(p, i) in personas" :key="i">
                                    <td class="px-3 py-1.5 text-gray-400">{{ i + 1 }}</td>
                                    <td class="px-3 py-1.5 text-gray-900">{{ p.nombre }}</td>
                                    <td class="px-3 py-1.5" :class="p.sexo ? 'text-gray-700' : 'text-amber-700'">{{ p.sexo || 'Falta' }}</td>
                                    <td class="px-3 py-1.5 text-gray-700">{{ formatoFecha(p.fecha_nacimiento) || '—' }}</td>
                                    <td class="px-3 py-1.5" :class="p.edad !== null ? 'text-gray-700' : 'text-amber-700'">{{ p.edad ?? 'Falta' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-6 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200" @click="emit('cerrar')">Cancelar</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50" :disabled="guardando || !personas.length">
                    {{ guardando ? 'Agregando…' : `Agregar ${personas.length || ''} colaboradores` }}
                </button>
            </div>
        </form>
    </div>
</template>
