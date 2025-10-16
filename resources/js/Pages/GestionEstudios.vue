<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden"
                >
                    <div class="p-6 text-gray-900">
                    </div>
                    <div class="p-6 text-gray-800">
                    
                        <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow mt-8">
        <h2 class="text-2xl font-bold mb-4 text-center">Gestión de Estudios</h2>

        <!-- Select para estudios existentes o crear nuevo -->
        <div class="mb-4">
            <label class="block mb-1">Selecciona un Estudio</label>
            <select v-model="selectedEstudioId" class="w-full p-2 border rounded" @change="cargarEstudio">
                <option value="">-- Nuevo Estudio --</option>
                <option v-for="estudio in estudios" :key="estudio.id" :value="estudio.id">
                    {{ estudio.nombre }}
                </option>
            </select>
        </div>

       <!-- Nombre del nuevo estudio -->
        <div v-if="!selectedEstudioId" class="mb-4">
        <label class="block mb-1">Nombre del Estudio</label>
        <input v-model="nuevoEstudio.nombre" type="text" class="w-full p-2 border rounded" />
        </div>

        <!-- NUEVO: Tipo de muestra -->
        <div class="mb-4">
        <label class="block mb-1">Tipo de Muestra</label>
        <input v-model="nuevoEstudio.tipo_muestra" type="text" class="w-full p-2 border rounded" />
        </div>

        <!-- NUEVO: Método -->
        <div class="mb-4">
        <label class="block mb-1">Método</label>
        <input v-model="nuevoEstudio.metodo" type="text" class="w-full p-2 border rounded" />
        </div>

        <div class="mb-4">
        <label class="block mb-1">Precio del Estudio ($)</label>
        <input v-model="nuevoEstudio.precio" type="number" step="0.01" min="0" class="w-full p-2 border rounded" />
        </div>

        <div class="mb-4">
        <label class="block mb-1">Leyenda</label>
        <textarea v-model="nuevoEstudio.leyenda" class="w-full p-2 border rounded"></textarea>
        </div>



        <!-- Tabla de exámenes -->
        <div>
            <h3 class="text-lg font-semibold mb-2">Exámenes</h3>
            <table class="w-full border mb-4">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-2 py-1">Examen</th>
                        <th class="border px-2 py-1">Unidad</th>
                        <th class="border px-2 py-1">Valor de Referencia</th>
                        <th class="border px-2 py-1">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(examen, index) in nuevoEstudio.examenes" :key="index">
                        <td><input v-model="examen.nombre_examen" class="w-full p-1 border rounded" /></td>
                        <td><input v-model="examen.unidad" class="w-full p-1 border rounded" /></td>
                        <td><input v-model="examen.valor_referencia" class="w-full p-1 border rounded" /></td>
                        <td>
                            <button @click="eliminarExamen(index)" class="text-red-600 hover:underline">Eliminar</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button @click="agregarExamen" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Agregar Examen
            </button>
        </div>

        <!-- Botones de guardar -->
        <div class="mt-6 text-center">
            <button @click="guardarEstudio" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                Guardar Estudio
            </button>
        </div>
    </div>

                    </div>
                </div>
            </div>
        </div>


    </AuthenticatedLayout>

    
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

const estudios = ref([])
const selectedEstudioId = ref('')
const nuevoEstudio = ref({
  nombre: '',
  leyenda: '',
  examenes: [],
  tipo_muestra: '',
  metodo: '',
  precio: 0
})

onMounted(async () => {
    await cargarEstudios()
})

const cargarEstudios = async () => {
    const response = await axios.get('/api/estudios')
    estudios.value = response.data
}

const cargarEstudio = () => {
    const estudio = estudios.value.find(e => e.id === selectedEstudioId.value)
    if (estudio) {
        nuevoEstudio.value = {
            nombre: estudio.nombre,
            leyenda: estudio.leyenda || '',
            examenes: [...estudio.examenes],
            tipo_muestra: estudio.tipo_muestra || '',
            metodo: estudio.metodo || '',
            precio: estudio.precio || 0

        }
    }
}

const agregarExamen = () => {
nuevoEstudio.value.examenes.push({
  nombre_examen: '',
  unidad: '',
  valor_referencia: ''
})
}

const eliminarExamen = (index) => {
    nuevoEstudio.value.examenes.splice(index, 1)
}

const guardarEstudio = async () => {
    if (selectedEstudioId.value) {
        // Actualizar estudio existente
        await axios.put(`/api/estudios/${selectedEstudioId.value}`, nuevoEstudio.value)
        alert('Estudio actualizado correctamente.')
    } else {
        // Crear nuevo estudio
        await axios.post('/api/estudios', nuevoEstudio.value)
        alert('Nuevo estudio creado correctamente.')
    }
    nuevoEstudio.value = { nombre: '', examenes: [] }
    selectedEstudioId.value = ''
    await cargarEstudios()
}
</script>
