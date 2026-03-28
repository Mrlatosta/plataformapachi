<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    reporte: Object,
});

const estudiosDisponibles = ref([]);
const estudiosFiltrados = ref([]);
const busquedaEstudio = ref('');
const guardando = ref(false);

const normalizeDatetime = (value) => {
    if (!value) return '';
    return String(value).replace(' ', 'T').slice(0, 16);
};

const normalizeDate = (value) => {
    if (!value) return '';
    return String(value).slice(0, 10);
};

const form = reactive({
    id: null,
    folio: '',
    toma_muestra: '',
    fecha_reporte: '',
    fecha_validacion: '',
    medico_solicitante: '',
    cliente: {
        nombre: '',
        email: '',
        fecha_nacimiento: '',
        edad: '',
        sexo: '',
    },
    estudios: [],
});

const hydrateForm = () => {
    const data = props.reporte;

    form.id = data.id;
    form.folio = data.folio;
    form.toma_muestra = normalizeDatetime(data.toma_muestra);
    form.fecha_reporte = normalizeDatetime(data.fecha_reporte);
    form.fecha_validacion = normalizeDatetime(data.fecha_validacion);
    form.medico_solicitante = data.medico_solicitante || '';

    form.cliente.nombre = data.nombre_cliente || '';
    form.cliente.email = data.email || '';
    form.cliente.fecha_nacimiento = normalizeDate(data.fecha_nacimiento);
    form.cliente.edad = data.edad ?? '';
    form.cliente.sexo = data.sexo || '';

    form.estudios = (data.estudios || []).map((estudio) => ({
        id: estudio.id,
        estudio_id: estudio.estudio_id,
        nombre: estudio.nombre,
        tipo_muestra: estudio.tipo_muestra || '',
        metodo: estudio.metodo || '',
        elaboro: estudio.elaboro || '',
        valido: estudio.valido || '',
        observaciones: estudio.observaciones || '',
        precio: Number(estudio.precio || 0),
        leyenda: estudio.leyenda || '',
        resultados: (estudio.resultados || []).map((resultado) => ({
            id: resultado.id,
            examen_id: resultado.examen_id,
            nombre_examen: resultado.nombre_examen || '',
            unidad: resultado.unidad || '',
            valor_referencia: resultado.valor_referencia || '',
            resultado: resultado.resultado || '',
            fuera_rango: Boolean(resultado.fuera_rango),
        })),
    }));
};

watch(() => props.reporte, hydrateForm, { immediate: true });

onMounted(async () => {
    const response = await axios.get('/api/estudios');
    estudiosDisponibles.value = response.data;
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(value || 0);
};

const totalPrecio = computed(() => {
    return form.estudios.reduce((sum, estudio) => sum + (Number(estudio.precio) || 0), 0);
});

const filtrarEstudios = () => {
    const termino = busquedaEstudio.value.toLowerCase().trim();
    if (!termino) {
        estudiosFiltrados.value = [...estudiosDisponibles.value];
        return;
    }

    estudiosFiltrados.value = estudiosDisponibles.value.filter((estudio) =>
        estudio.nombre.toLowerCase().includes(termino),
    );
};

const mostrarTodosEstudios = () => {
    estudiosFiltrados.value = [...estudiosDisponibles.value];
};

const agregarEstudio = (estudio) => {
    const yaExiste = form.estudios.some((item) => Number(item.estudio_id) === Number(estudio.id));
    if (yaExiste) {
        window.alert('Este estudio ya esta agregado en el reporte.');
        return;
    }

    form.estudios.push({
        id: null,
        estudio_id: estudio.id,
        nombre: estudio.nombre,
        tipo_muestra: estudio.tipo_muestra || '',
        metodo: estudio.metodo || '',
        elaboro: 'Q.F.B ANGEL AUGUSTO PEREZ ARIAS',
        valido: 'Q.F.B ANGEL AUGUSTO PEREZ ARIAS',
        observaciones: '',
        precio: Number(estudio.precio || 0),
        leyenda: estudio.leyenda || '',
        resultados: (estudio.examenes || []).map((examen) => ({
            id: null,
            examen_id: examen.id,
            nombre_examen: examen.nombre_examen,
            unidad: examen.unidad,
            valor_referencia: examen.valor_referencia,
            resultado: '',
            fuera_rango: false,
        })),
    });

    busquedaEstudio.value = '';
    estudiosFiltrados.value = [];
};

const eliminarEstudio = (index) => {
    if (!window.confirm('Deseas eliminar este estudio del reporte?')) {
        return;
    }

    form.estudios.splice(index, 1);
};

const guardarCambios = async () => {
    guardando.value = true;

    try {
        const payload = {
            id: form.id,
            toma_muestra: form.toma_muestra,
            fecha_reporte: form.fecha_reporte,
            fecha_validacion: form.fecha_validacion,
            medico_solicitante: form.medico_solicitante,
            cliente: {
                nombre: form.cliente.nombre,
                email: form.cliente.email,
                fecha_nacimiento: form.cliente.fecha_nacimiento,
                edad: form.cliente.edad,
                sexo: form.cliente.sexo,
            },
            estudios: form.estudios.map((estudio) => ({
                id: estudio.id,
                estudio_id: estudio.estudio_id,
                tipo_muestra: estudio.tipo_muestra,
                metodo: estudio.metodo,
                elaboro: estudio.elaboro,
                valido: estudio.valido,
                observaciones: estudio.observaciones,
                precio: Number(estudio.precio || 0),
                resultados: (estudio.resultados || []).map((resultado) => ({
                    id: resultado.id,
                    examen_id: resultado.examen_id,
                    resultado: resultado.resultado,
                    fuera_rango: Boolean(resultado.fuera_rango),
                })),
            })),
        };

        await axios.put(`/api/reportes/${form.id}`, payload);
        window.alert('Cambios guardados correctamente.');
        router.reload({ only: ['reporte'] });
    } catch (error) {
        console.error(error);
        window.alert('No se pudieron guardar los cambios del reporte.');
    } finally {
        guardando.value = false;
    }
};

const descargarPDF = () => {
    window.open(`/api/reportes/${props.reporte.id}/pdf`, '_blank');
};

const visualizarPDF = () => {
    window.open(`/api/reportes/${props.reporte.id}/pdf-preview`, '_blank');
};

const descargarOrden = () => {
    window.open(`/api/reportes/${props.reporte.id}/orden`, '_blank');
};
</script>

<template>
    <Head :title="`Editar Reporte ${reporte.folio}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3">
                <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
                    Editar Reporte - {{ form.folio }}
                </h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        @click="visualizarPDF"
                        class="flex items-center justify-center px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                        Visualizar PDF
                    </button>
                    <button
                        @click="descargarPDF"
                        class="flex items-center justify-center px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors"
                    >
                        Descargar PDF
                    </button>
                    <button
                        @click="descargarOrden"
                        class="flex items-center justify-center px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
                    >
                        Orden de Trabajo
                    </button>
                    <Link
                        :href="route('reportes.index')"
                        class="flex items-center justify-center px-3 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 transition-colors"
                    >
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Folio</label>
                            <input
                                type="text"
                                :value="form.folio"
                                disabled
                                class="w-full rounded-lg border-gray-300 bg-gray-100 text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Toma de muestra</label>
                            <input
                                v-model="form.toma_muestra"
                                type="datetime-local"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de reporte</label>
                            <input
                                v-model="form.fecha_reporte"
                                type="datetime-local"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de validacion</label>
                            <input
                                v-model="form.fecha_validacion"
                                type="datetime-local"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medico solicitante</label>
                            <input
                                v-model="form.medico_solicitante"
                                type="text"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Datos del Paciente</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                <input
                                    v-model="form.cliente.nombre"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input
                                    v-model="form.cliente.email"
                                    type="email"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de nacimiento</label>
                                <input
                                    v-model="form.cliente.fecha_nacimiento"
                                    type="date"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Edad</label>
                                <input
                                    v-model="form.cliente.edad"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                                <select
                                    v-model="form.cliente.sexo"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Seleccione</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-visible shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Agregar Estudio</h3>
                        <div class="relative z-30 max-w-2xl">
                            <input
                                v-model="busquedaEstudio"
                                type="text"
                                placeholder="Escribe para buscar estudios..."
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                @focus="mostrarTodosEstudios"
                                @input="filtrarEstudios"
                            />
                            <ul
                                v-if="estudiosFiltrados.length > 0"
                                class="absolute z-40 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto"
                            >
                                <li
                                    v-for="estudio in estudiosFiltrados"
                                    :key="estudio.id"
                                    class="px-4 py-2 text-sm hover:bg-blue-50 cursor-pointer flex items-center justify-between"
                                    @click="agregarEstudio(estudio)"
                                >
                                    <span>{{ estudio.nombre }}</span>
                                    <span class="font-semibold text-gray-700">{{ formatCurrency(estudio.precio) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div
                    v-for="(estudio, index) in form.estudios"
                    :key="`${estudio.estudio_id}-${index}`"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                >
                    <div class="p-4 sm:p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900">{{ estudio.nombre }}</h3>
                            <button
                                @click="eliminarEstudio(index)"
                                class="px-3 py-2 bg-red-100 text-red-700 text-sm rounded-lg hover:bg-red-200 transition-colors"
                            >
                                Eliminar Estudio
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de muestra</label>
                                <input
                                    v-model="estudio.tipo_muestra"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Metodo</label>
                                <input
                                    v-model="estudio.metodo"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Elaboro</label>
                                <input
                                    v-model="estudio.elaboro"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valido</label>
                                <input
                                    v-model="estudio.valido"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                                <input
                                    v-model.number="estudio.precio"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                            <textarea
                                v-model="estudio.observaciones"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <div v-if="estudio.leyenda" class="p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded text-sm text-gray-700">
                            <span class="font-semibold">Leyenda:</span> {{ estudio.leyenda }}
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Examen</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unidad</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Referencia</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Resultado</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">F.R.</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="resultado in estudio.resultados" :key="`${resultado.examen_id}-${resultado.id || 'new'}`">
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ resultado.nombre_examen }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-700">{{ resultado.unidad || '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-700">{{ resultado.valor_referencia || '-' }}</td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model="resultado.resultado"
                                                type="text"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                            />
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input
                                                v-model="resultado.fuera_rango"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-slate-800 to-slate-700 overflow-hidden shadow-lg sm:rounded-lg">
                    <div class="p-4 sm:p-6 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold">Total del Reporte</h3>
                            <p class="text-sm opacity-90">{{ form.estudios.length }} estudios agregados</p>
                        </div>
                        <div class="text-2xl font-bold">
                            {{ formatCurrency(totalPrecio) }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pb-12">
                    <button
                        @click="guardarCambios"
                        :disabled="guardando"
                        class="px-5 py-2.5 rounded-lg text-white text-sm font-semibold transition-colors"
                        :class="guardando ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                    >
                        {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
