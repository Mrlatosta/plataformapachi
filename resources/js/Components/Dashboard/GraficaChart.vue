<script setup>
// Envoltura de Chart.js: crea la grafica y la redibuja cuando cambian los datos.
import { ref, onMounted, onBeforeUnmount, watch, markRaw } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    tipo: { type: String, required: true },
    datos: { type: Object, required: true },
    opciones: { type: Object, default: () => ({}) },
    etiqueta: { type: String, default: '' },
});

const lienzo = ref(null);
let grafica = null;

const dibujar = () => {
    if (!lienzo.value) return;
    if (grafica) {
        grafica.data = props.datos;
        grafica.options = props.opciones;
        grafica.update();
        return;
    }
    grafica = markRaw(new Chart(lienzo.value, {
        type: props.tipo,
        data: props.datos,
        options: props.opciones,
    }));
};

onMounted(dibujar);
watch(() => [props.datos, props.opciones], dibujar);
onBeforeUnmount(() => grafica?.destroy());
</script>

<template>
    <div class="relative h-full w-full">
        <canvas ref="lienzo" role="img" :aria-label="etiqueta"></canvas>
    </div>
</template>
