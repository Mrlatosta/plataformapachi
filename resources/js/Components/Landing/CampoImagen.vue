<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';

// Selector de imagen para Gestión Landing. Reduce la foto en el navegador
// (máx. 1600 px, WebP) para no chocar con el límite de subida del servidor.
const props = defineProps({
    id: { type: String, required: true },
    etiqueta: { type: String, default: 'Imagen' },
    urlActual: { type: String, default: null },
    archivo: { type: Object, default: null },
    quitar: { type: Boolean, default: false },
    alt: { type: String, default: '' },
    requerida: { type: Boolean, default: false },
    recomendacion: { type: String, default: '' },
    error: { type: String, default: '' },
    errorAlt: { type: String, default: '' },
});

const emit = defineEmits(['update:archivo', 'update:quitar', 'update:alt']);

const ANCHO_MAXIMO = 1600;
const PESO_MAXIMO = 15 * 1024 * 1024;

const procesando = ref(false);
const errorLocal = ref('');
const arrastrando = ref(false);
const vistaPrevia = ref(null);
const detalles = ref('');
const input = ref(null);

const urlMostrada = computed(() => vistaPrevia.value || (props.quitar ? null : props.urlActual));
const mensajeError = computed(() => errorLocal.value || props.error);

const liberarVistaPrevia = () => {
    if (vistaPrevia.value) URL.revokeObjectURL(vistaPrevia.value);
    vistaPrevia.value = null;
};
onBeforeUnmount(liberarVistaPrevia);

// Al reiniciar el formulario desde fuera también se limpia la vista previa
watch(() => props.archivo, (nuevo) => {
    if (!nuevo) {
        liberarVistaPrevia();
        detalles.value = '';
    }
});

const lienzoABlob = (lienzo, tipo, calidad) => new Promise((resolver) => lienzo.toBlob(resolver, tipo, calidad));

const reducir = async (archivo) => {
    const bitmap = await createImageBitmap(archivo, { imageOrientation: 'from-image' });
    const escala = Math.min(1, ANCHO_MAXIMO / bitmap.width);
    const lienzo = document.createElement('canvas');
    lienzo.width = Math.round(bitmap.width * escala);
    lienzo.height = Math.round(bitmap.height * escala);
    const ctx = lienzo.getContext('2d');
    ctx.drawImage(bitmap, 0, 0, lienzo.width, lienzo.height);
    bitmap.close?.();

    let blob = await lienzoABlob(lienzo, 'image/webp', 0.85);
    if (!blob || blob.type !== 'image/webp') {
        // Navegadores sin WebP: JPEG sobre fondo blanco
        ctx.globalCompositeOperation = 'destination-over';
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, lienzo.width, lienzo.height);
        blob = await lienzoABlob(lienzo, 'image/jpeg', 0.85);
    }

    const extension = blob.type === 'image/webp' ? 'webp' : 'jpg';
    return {
        archivo: new File([blob], `imagen.${extension}`, { type: blob.type }),
        ancho: lienzo.width,
        alto: lienzo.height,
    };
};

const elegir = async (archivo) => {
    errorLocal.value = '';
    if (!archivo) return;
    if (!/^image\/(jpeg|png|webp)$/.test(archivo.type)) {
        errorLocal.value = 'Elige una imagen JPG, PNG o WebP.';
        return;
    }
    if (archivo.size > PESO_MAXIMO) {
        errorLocal.value = 'La imagen pesa más de 15 MB. Elige una más ligera.';
        return;
    }

    procesando.value = true;
    try {
        const resultado = await reducir(archivo);
        liberarVistaPrevia();
        vistaPrevia.value = URL.createObjectURL(resultado.archivo);
        detalles.value = `${resultado.ancho} × ${resultado.alto} px · ${Math.round(resultado.archivo.size / 1024)} KB`;
        emit('update:archivo', resultado.archivo);
        emit('update:quitar', false);
    } catch {
        errorLocal.value = 'No pudimos leer la imagen. Prueba con otro archivo.';
    } finally {
        procesando.value = false;
        if (input.value) input.value.value = '';
    }
};

const alSoltar = (evento) => {
    arrastrando.value = false;
    elegir(evento.dataTransfer?.files?.[0]);
};

const quitarImagen = () => {
    liberarVistaPrevia();
    detalles.value = '';
    errorLocal.value = '';
    emit('update:archivo', null);
    emit('update:quitar', Boolean(props.urlActual));
};
</script>

<template>
    <div>
        <p :id="`${id}-etiqueta`" class="block text-sm font-medium text-gray-700 mb-1">
            {{ etiqueta }} <span v-if="requerida" class="text-red-600">*</span>
        </p>

        <div
            class="relative rounded-xl border-2 border-dashed transition-colors focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-1"
            :class="arrastrando
                ? 'border-blue-500 bg-blue-50'
                : mensajeError ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-gray-50'"
            @dragover.prevent="arrastrando = true"
            @dragleave.prevent="arrastrando = false"
            @drop.prevent="alSoltar"
        >
            <!-- Con imagen -->
            <div v-if="urlMostrada" class="flex flex-col sm:flex-row gap-4 p-3">
                <img :src="urlMostrada" alt="" class="w-full sm:w-48 aspect-[16/10] object-cover rounded-lg bg-white border border-gray-200" />
                <div class="flex flex-col justify-center gap-2 text-sm">
                    <p class="text-gray-700 font-medium">{{ archivo ? 'Imagen nueva lista para guardar' : 'Imagen actual' }}</p>
                    <p v-if="detalles" class="text-xs text-gray-500">{{ detalles }}</p>
                    <div class="flex flex-wrap gap-2">
                        <label :for="id" class="cursor-pointer px-3 py-2 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 font-medium text-gray-700">
                            Cambiar imagen
                        </label>
                        <button type="button" @click="quitarImagen" class="px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 font-medium">
                            Quitar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sin imagen: toda la zona es clicable -->
            <div v-else class="flex flex-col items-center justify-center gap-2 px-4 py-8 text-center pointer-events-none">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-sm font-semibold text-blue-700">Elige una imagen o arrástrala aquí</span>
                <span class="text-xs text-gray-500">JPG, PNG o WebP. {{ recomendacion }}</span>
            </div>

            <input
                :id="id"
                ref="input"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                :aria-labelledby="`${id}-etiqueta`"
                :aria-describedby="mensajeError ? `${id}-error` : undefined"
                :aria-invalid="mensajeError ? 'true' : undefined"
                :class="urlMostrada ? 'sr-only' : 'absolute inset-0 w-full h-full opacity-0 cursor-pointer'"
                @change="elegir($event.target.files[0])"
            />

            <div v-if="procesando" class="absolute inset-0 grid place-items-center rounded-xl bg-white/80" role="status">
                <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                    </svg>
                    Preparando imagen…
                </span>
            </div>
        </div>

        <p v-if="quitar && !archivo" class="mt-1 text-xs text-amber-700">La imagen se quitará al guardar.</p>
        <p v-if="mensajeError" :id="`${id}-error`" class="mt-1 text-sm text-red-600">{{ mensajeError }}</p>

        <div v-if="urlMostrada" class="mt-3">
            <label :for="`${id}-alt`" class="block text-sm font-medium text-gray-700 mb-1">
                Describe la imagen <span class="font-normal text-gray-500">(la leen las personas con lector de pantalla)</span>
            </label>
            <input
                :id="`${id}-alt`"
                :value="alt"
                type="text"
                maxlength="150"
                placeholder="Ej. Listón rosa de prevención del cáncer de mama"
                class="w-full px-3 py-2 text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                @input="emit('update:alt', $event.target.value)"
            />
            <p v-if="errorAlt" class="mt-1 text-sm text-red-600">{{ errorAlt }}</p>
        </div>
    </div>
</template>
