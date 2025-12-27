<template>
    <Head title="Gestión de Estudios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                🔬 Gestión de Estudios
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-lg sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                                <span class="text-blue-600 mr-3">📋</span>
                                Gestión de Estudios Clínicos
                            </h2>
                            <button 
                                v-if="selectedEstudioId" 
                                @click="crearNuevoEstudio"
                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white font-semibold rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                            >
                                + Nuevo Estudio
                            </button>
                        </div>

                        <!-- Selector de Estudios -->
                        <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                🔍 Selecciona un Estudio para Editar
                            </label>
                            <select 
                                v-model="selectedEstudioId" 
                                @change="cargarEstudio"
                                class="w-full p-3 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            >
                                <option value="">-- Crear Nuevo Estudio --</option>
                                <option v-for="estudio in estudios" :key="estudio.id" :value="estudio.id">
                                    {{ estudio.nombre }}
                                </option>
                            </select>
                        </div>

                        <!-- Información del Estudio -->
                        <div class="mb-6 p-6 bg-white border-2 border-gray-200 rounded-lg shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <span class="text-purple-600 mr-2">📝</span>
                                Información General
                            </h3>
                            
                            <!-- Nombre del Estudio -->
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nombre del Estudio *
                                </label>
                                <input 
                                    v-model="nuevoEstudio.nombre" 
                                    type="text" 
                                    class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                    placeholder="Ej: Biometría Hemática Completa"
                                    required
                                />
                            </div>

                            <!-- Grid de 2 columnas para campos -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <!-- Tipo de muestra -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        🧪 Tipo de Muestra
                                    </label>
                                    <input 
                                        v-model="nuevoEstudio.tipo_muestra" 
                                        type="text" 
                                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Ej: Sangre, Orina, etc."
                                    />
                                </div>

                                <!-- Método -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        🔬 Método
                                    </label>
                                    <input 
                                        v-model="nuevoEstudio.metodo" 
                                        type="text" 
                                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Ej: Automatizado, Manual, etc."
                                    />
                                </div>
                            </div>

                            <!-- Precio -->
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    💰 Precio del Estudio ($) *
                                </label>
                                <input 
                                    v-model="nuevoEstudio.precio" 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                    placeholder="0.00"
                                    required
                                />
                            </div>

                            <!-- Leyenda -->
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    📄 Leyenda / Notas
                                </label>
                                <textarea 
                                    v-model="nuevoEstudio.leyenda" 
                                    rows="3"
                                    class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                    placeholder="Instrucciones especiales, preparación del paciente, etc."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Tabla de exámenes -->
                        <div class="mb-6 p-6 bg-gradient-to-r from-gray-50 to-blue-50 border-2 border-gray-200 rounded-lg shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <span class="text-green-600 mr-2">🧬</span>
                                    Exámenes Incluidos
                                </h3>
                                <button 
                                    @click="agregarExamen" 
                                    class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center"
                                >
                                    <span class="mr-2">+</span> Agregar Examen
                                </button>
                            </div>

                            <div class="overflow-x-auto bg-white rounded-lg shadow">
                                <table class="w-full">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                                            <th class="px-4 py-3 text-left font-semibold">Examen</th>
                                            <th class="px-4 py-3 text-left font-semibold">Unidad</th>
                                            <th class="px-4 py-3 text-left font-semibold">Valor de Referencia</th>
                                            <th class="px-4 py-3 text-center font-semibold">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr 
                                            v-for="(examen, index) in nuevoEstudio.examenes" 
                                            :key="index"
                                            class="border-b hover:bg-blue-50 transition-colors"
                                        >
                                            <td class="px-4 py-3">
                                                <input 
                                                    v-model="examen.nombre_examen" 
                                                    class="w-full p-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                                    placeholder="Nombre del examen"
                                                />
                                            </td>
                                            <td class="px-4 py-3">
                                                <input 
                                                    v-model="examen.unidad" 
                                                    class="w-full p-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                                    placeholder="mg/dL, g/dL, etc."
                                                />
                                            </td>
                                            <td class="px-4 py-3">
                                                <input 
                                                    v-model="examen.valor_referencia" 
                                                    class="w-full p-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                                    placeholder="Rango normal"
                                                />
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button 
                                                    @click="eliminarExamen(index)" 
                                                    class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 shadow hover:shadow-lg transform hover:-translate-y-0.5"
                                                >
                                                    🗑️ Eliminar
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="nuevoEstudio.examenes.length === 0">
                                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                                No hay exámenes agregados. Haz clic en "Agregar Examen" para comenzar.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-6 border-t-2 border-gray-200">
                            <button 
                                @click="guardarEstudio" 
                                class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white font-bold rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center"
                            >
                                <span class="mr-2">💾</span>
                                {{ selectedEstudioId ? 'Actualizar Estudio' : 'Guardar Estudio' }}
                            </button>
                            
                            <button 
                                v-if="selectedEstudioId"
                                @click="confirmarEliminarEstudio" 
                                class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-lg hover:from-red-700 hover:to-red-800 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center"
                            >
                                <span class="mr-2">🗑️</span>
                                Eliminar Estudio
                            </button>

                            <button 
                                @click="cancelarEdicion" 
                                class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white font-bold rounded-lg hover:from-gray-600 hover:to-gray-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center"
                            >
                                <span class="mr-2">❌</span>
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación para eliminar -->
        <div 
            v-if="mostrarModalEliminar" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="mostrarModalEliminar = false"
        >
            <div class="bg-white rounded-lg shadow-2xl p-8 max-w-md mx-4 transform transition-all">
                <div class="text-center">
                    <div class="text-6xl mb-4">⚠️</div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">¿Eliminar Estudio?</h3>
                    <p class="text-gray-600 mb-6">
                        ¿Estás seguro de que deseas eliminar el estudio <strong>"{{ nuevoEstudio.nombre }}"</strong>? 
                        Esta acción no se puede deshacer.
                    </p>
                    <div class="flex gap-4 justify-center">
                        <button 
                            @click="eliminarEstudio" 
                            class="px-6 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-all duration-200 shadow-lg hover:shadow-xl"
                        >
                            Sí, Eliminar
                        </button>
                        <button 
                            @click="mostrarModalEliminar = false" 
                            class="px-6 py-3 bg-gray-500 text-white font-bold rounded-lg hover:bg-gray-600 transition-all duration-200 shadow-lg hover:shadow-xl"
                        >
                            Cancelar
                        </button>
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
const mostrarModalEliminar = ref(false)
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

const crearNuevoEstudio = () => {
    selectedEstudioId.value = ''
    nuevoEstudio.value = {
        nombre: '',
        leyenda: '',
        examenes: [],
        tipo_muestra: '',
        metodo: '',
        precio: 0
    }
}

const cancelarEdicion = () => {
    if (confirm('¿Deseas cancelar? Los cambios no guardados se perderán.')) {
        crearNuevoEstudio()
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
    // Validaciones
    if (!nuevoEstudio.value.nombre || nuevoEstudio.value.nombre.trim() === '') {
        alert('⚠️ Por favor, ingresa el nombre del estudio.')
        return
    }
    
    if (!nuevoEstudio.value.precio || nuevoEstudio.value.precio <= 0) {
        alert('⚠️ Por favor, ingresa un precio válido para el estudio.')
        return
    }

    try {
        if (selectedEstudioId.value) {
            // Actualizar estudio existente
            await axios.put(`/api/estudios/${selectedEstudioId.value}`, nuevoEstudio.value)
            alert('✅ Estudio actualizado correctamente.')
        } else {
            // Crear nuevo estudio
            await axios.post('/api/estudios', nuevoEstudio.value)
            alert('✅ Nuevo estudio creado correctamente.')
        }
        
        // Limpiar formulario
        nuevoEstudio.value = { 
            nombre: '', 
            leyenda: '', 
            examenes: [], 
            tipo_muestra: '', 
            metodo: '', 
            precio: 0 
        }
        selectedEstudioId.value = ''
        await cargarEstudios()
    } catch (error) {
        console.error('Error al guardar estudio:', error)
        alert('❌ Error al guardar el estudio. Por favor, intenta nuevamente.')
    }
}

const confirmarEliminarEstudio = () => {
    mostrarModalEliminar.value = true
}

const eliminarEstudio = async () => {
    try {
        await axios.delete(`/api/estudios/${selectedEstudioId.value}`)
        mostrarModalEliminar.value = false
        alert('✅ Estudio eliminado correctamente.')
        
        // Limpiar formulario
        nuevoEstudio.value = { 
            nombre: '', 
            leyenda: '', 
            examenes: [], 
            tipo_muestra: '', 
            metodo: '', 
            precio: 0 
        }
        selectedEstudioId.value = ''
        await cargarEstudios()
    } catch (error) {
        console.error('Error al eliminar estudio:', error)
        alert('❌ Error al eliminar el estudio. Por favor, intenta nuevamente.')
        mostrarModalEliminar.value = false
    }
}
</script>
