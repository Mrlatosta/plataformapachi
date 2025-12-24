<template>
  <Head title="Captura de Resultados" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800">
          Captura de Resultados
        </h2>
        <span class="text-sm text-gray-500">Sistema de Laboratorio</span>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Fechas y Médico -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Información de Fechas
            </h3>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Toma de muestra</label>
              <input 
                type="datetime-local" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                v-model="form.toma_muestra" 
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Fecha de reporte</label>
              <input 
                type="datetime-local" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                v-model="form.fecha_reporte" 
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Fecha de validación</label>
              <input 
                type="datetime-local" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                v-model="form.fecha_validacion" 
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Médico solicitante <span class="text-gray-400 text-xs">(opcional)</span>
              </label>
              <input 
                type="text" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                v-model="form.medico_solicitante" 
                placeholder="Ingrese el nombre del médico"
              />
            </div>
          </div>
        </div>

        <!-- Datos del Cliente -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
          <div class="bg-gradient-to-r from-emerald-50 to-teal-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Datos del Paciente
            </h3>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="space-y-2 md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Nombre completo</label>
              <input 
                type="text" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                v-model="form.cliente.nombre" 
                placeholder="Ingrese el nombre del paciente"
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Email <span class="text-gray-400 text-xs">(opcional)</span>
              </label>
              <input 
                type="email" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                v-model="form.cliente.email" 
                placeholder="correo@ejemplo.com"
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Fecha de nacimiento</label>
              <input 
                type="date" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                v-model="form.cliente.fecha_nacimiento" 
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Edad</label>
              <input 
                type="number" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                v-model="form.cliente.edad" 
                readonly
                placeholder="Se calcula automáticamente"
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Sexo</label>
              <select 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                v-model="form.cliente.sexo"
              >
                <option value="">Seleccione</option>
                <option>Masculino</option>
                <option>Femenino</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Estudios -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
          <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
              Estudios y Exámenes
            </h3>
          </div>
          <div class="p-6">
            <!-- Búsqueda de estudios -->
            <div class="mb-6 max-w-2xl">
              <label class="block text-sm font-medium text-gray-700 mb-2">Buscar estudio</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input
                  type="text"
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                  placeholder="Escribe el nombre del estudio o haz clic para ver todos..."
                  v-model="busquedaEstudio"
                  @focus="mostrarTodosEstudios"
                  @input="filtrarEstudios"
                />
                
                <!-- Lista desplegable mejorada -->
                <transition
                  enter-active-class="transition ease-out duration-100"
                  enter-from-class="transform opacity-0 scale-95"
                  enter-to-class="transform opacity-100 scale-100"
                  leave-active-class="transition ease-in duration-75"
                  leave-from-class="transform opacity-100 scale-100"
                  leave-to-class="transform opacity-0 scale-95"
                >
                  <ul
                    v-if="estudiosFiltrados.length > 0"
                    class="absolute w-full mt-2 bg-white rounded-lg shadow-lg border border-gray-200 max-h-64 overflow-y-auto z-50"
                  >
                    <li
                      v-for="estudio in estudiosFiltrados"
                      :key="estudio.id"
                      class="px-4 py-3 hover:bg-purple-50 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
                      @click="seleccionarEstudio(estudio)"
                    >
                      <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-800">{{ estudio.nombre }}</span>
                        <span class="text-sm font-semibold text-purple-600">${{ parseFloat(estudio.precio).toFixed(2) }}</span>
                      </div>
                    </li>
                  </ul>
                </transition>
              </div>
            </div>

            <!-- Lista de estudios agregados -->
            <transition-group name="list" tag="div" class="space-y-4">
              <div 
                v-for="(estudio, index) in form.estudios" 
                :key="`estudio-${index}`" 
                class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-all"
              >
                <div class="flex justify-between items-start mb-4">
                  <h4 class="text-xl font-bold text-gray-800 flex items-center">
                    <span class="bg-purple-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm mr-3">
                      {{ index + 1 }}
                    </span>
                    {{ estudio.nombre }}
                  </h4>
                  <button
                    class="flex items-center px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors font-medium"
                    @click="eliminarEstudio(index)"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Eliminar
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Tipo de muestra</label>
                    <input 
                      type="text" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm" 
                      v-model="estudio.tipo_muestra" 
                    />
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Método</label>
                    <input 
                      type="text" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm" 
                      v-model="estudio.metodo" 
                    />
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Elaboró</label>
                    <input 
                      type="text" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm" 
                      v-model="estudio.elaboro" 
                    />
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Validó</label>
                    <input 
                      type="text" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm" 
                      v-model="estudio.valido" 
                    />
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Precio ($)</label>
                    <input 
                      type="number" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm font-semibold" 
                      step="0.01" 
                      v-model="estudio.precio" 
                    />
                  </div>
                </div>

                <!-- Tabla de exámenes modernizada -->
                <div class="overflow-hidden rounded-lg border border-gray-200">
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-100">
                        <tr>
                          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Examen</th>
                          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Unidad</th>
                          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Valor referencia</th>
                          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Resultado</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr 
                          v-for="(examen, exIndex) in estudio.examenes" 
                          :key="exIndex"
                          class="hover:bg-gray-50 transition-colors"
                        >
                          <td class="px-4 py-3 text-sm text-gray-900">{{ examen.nombre_examen }}</td>
                          <td class="px-4 py-3 text-sm text-gray-600">{{ examen.unidad }}</td>
                          <td class="px-4 py-3 text-sm text-gray-600">{{ examen.valor_referencia }}</td>
                          <td class="px-4 py-3">
                            <div class="flex items-center space-x-3">
                              <input 
                                type="text" 
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm" 
                                v-model="examen.resultado" 
                                placeholder="Ingrese resultado"
                              />
                              <label class="flex items-center cursor-pointer">
                                <input 
                                  type="checkbox" 
                                  class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500" 
                                  v-model="examen.fuera_rango" 
                                />
                                <span class="ml-2 text-xs font-medium text-gray-700">F.R.</span>
                              </label>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </transition-group>

            <!-- Mensaje cuando no hay estudios -->
            <div v-if="form.estudios.length === 0" class="text-center py-12">
              <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-gray-500 text-lg">No hay estudios agregados</p>
              <p class="text-gray-400 text-sm mt-2">Busca y selecciona estudios para agregar</p>
            </div>
          </div>
        </div>

        <!-- Total y botones -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center">
            <span class="text-gray-600 text-lg mr-3">Total de la orden:</span>
            <span class="text-3xl font-bold text-green-600">${{ totalEstudios.toFixed(2) }}</span>
          </div>
          <button
            class="flex items-center px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all shadow-md hover:shadow-lg font-semibold text-lg disabled:opacity-50 disabled:cursor-not-allowed"
            @click="guardarReporte"
            :disabled="guardando"
          >
            <svg v-if="!guardando" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ guardando ? 'Guardando...' : 'Guardar Reporte' }}
          </button>
        </div>

        <!-- Toast de notificación -->
        <transition
          enter-active-class="transition ease-out duration-300"
          enter-from-class="transform translate-y-2 opacity-0"
          enter-to-class="transform translate-y-0 opacity-100"
          leave-active-class="transition ease-in duration-200"
          leave-from-class="transform translate-y-0 opacity-100"
          leave-to-class="transform translate-y-2 opacity-0"
        >
          <div 
            v-if="notification.show" 
            :class="[
              'fixed bottom-8 right-8 px-6 py-4 rounded-lg shadow-xl z-50 max-w-md',
              notification.type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
            ]"
          >
            <div class="flex items-start">
              <svg 
                v-if="notification.type === 'success'" 
                class="w-6 h-6 text-green-600 mr-3" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg 
                v-else 
                class="w-6 h-6 text-red-600 mr-3" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p :class="[
                  'font-semibold',
                  notification.type === 'success' ? 'text-green-800' : 'text-red-800'
                ]">
                  {{ notification.message }}
                </p>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

// Estado de guardado
const guardando = ref(false)

// Sistema de notificaciones
const notification = reactive({
  show: false,
  message: '',
  type: 'success' // success | error
})

function mostrarNotificacion(mensaje, tipo = 'success') {
  notification.message = mensaje
  notification.type = tipo
  notification.show = true
  
  setTimeout(() => {
    notification.show = false
  }, 4000)
}

// 💰 Cálculo automático del total
const totalEstudios = computed(() => {
  return form.estudios.reduce((sum, e) => sum + (parseFloat(e.precio) || 0), 0)
})

const estudiosDisponibles = ref([])
const selectedEstudioId = ref('')
const form = reactive({
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
})
const busquedaEstudio = ref('')
const estudiosFiltrados = ref([])

onMounted(async () => {
  try {
    const response = await axios.get('/api/estudios')
    estudiosDisponibles.value = response.data
  } catch (error) {
    console.error('Error al cargar estudios:', error)
    mostrarNotificacion('Error al cargar los estudios disponibles', 'error')
  }
})

watch(() => form.cliente.fecha_nacimiento, (nuevaFecha) => {
  if (nuevaFecha) {
    const hoy = new Date()
    const nacimiento = new Date(nuevaFecha)
    let edad = hoy.getFullYear() - nacimiento.getFullYear()
    const mes = hoy.getMonth() - nacimiento.getMonth()

    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
      edad--
    }

    form.cliente.edad = edad
  } else {
    form.cliente.edad = ''
  }
})

function agregarEstudio() {
  if (!selectedEstudioId.value) return
  const estudio = estudiosDisponibles.value.find(e => e.id == selectedEstudioId.value)
  if (!estudio) return

  form.estudios.push({
    id: estudio.id,
    nombre: estudio.nombre,
    tipo_muestra: (estudio.tipo_muestra && estudio.tipo_muestra !== 'N/A') ? estudio.tipo_muestra : '',
    metodo: (estudio.metodo && estudio.metodo !== 'N/A') ? estudio.metodo : '',
    elaboro: 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS',
    valido: 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS',
    precio: parseFloat(estudio.precio) || 0,
    examenes: estudio.examenes.map(e => ({
      id: e.id,
      nombre_examen: e.nombre_examen,
      unidad: e.unidad,
      valor_referencia: e.valor_referencia,
      resultado: '',
      fuera_rango: false,
    })),
  })

  selectedEstudioId.value = ''
}

async function guardarReporte() {
  if (guardando.value) return
  
  // Validaciones completas
  if (!form.cliente.nombre || form.cliente.nombre.trim() === '') {
    mostrarNotificacion('Por favor ingresa el nombre del paciente', 'error')
    return
  }

  if (!form.cliente.fecha_nacimiento) {
    mostrarNotificacion('Por favor ingresa la fecha de nacimiento del paciente', 'error')
    return
  }

  if (!form.cliente.sexo) {
    mostrarNotificacion('Por favor selecciona el sexo del paciente', 'error')
    return
  }

  if (!form.toma_muestra) {
    mostrarNotificacion('Por favor ingresa la fecha de toma de muestra', 'error')
    return
  }

  if (!form.fecha_reporte) {
    mostrarNotificacion('Por favor ingresa la fecha de reporte', 'error')
    return
  }

  if (!form.fecha_validacion) {
    mostrarNotificacion('Por favor ingresa la fecha de validación', 'error')
    return
  }
  
  if (form.estudios.length === 0) {
    mostrarNotificacion('Debes agregar al menos un estudio', 'error')
    return
  }

  // Validar que todos los estudios tengan al menos un resultado
  for (let i = 0; i < form.estudios.length; i++) {
    const estudio = form.estudios[i]
    
    if (!estudio.precio || estudio.precio <= 0) {
      mostrarNotificacion(`El estudio "${estudio.nombre}" debe tener un precio válido`, 'error')
      return
    }

    const tieneResultados = estudio.examenes.some(examen => 
      examen.resultado && examen.resultado.trim() !== ''
    )
    
    if (!tieneResultados) {
      mostrarNotificacion(`El estudio "${estudio.nombre}" debe tener al menos un resultado capturado`, 'error')
      return
    }
  }

  guardando.value = true

  try {
    const response = await axios.post('/api/reportes', form)
    mostrarNotificacion('¡Reporte guardado con éxito!', 'success')

    const { id, folio } = response.data

    // Descargar el PDF
    const pdfResponse = await axios.get(`/api/reportes/${id}/pdf`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([pdfResponse.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `reporte-${folio}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    // Descarga también la Orden de Trabajo
    const ordenResponse = await axios.get(`/api/reportes/${id}/orden`, {
      responseType: 'blob'
    })
    const urlOrden = window.URL.createObjectURL(new Blob([ordenResponse.data]))
    const linkOrden = document.createElement('a')
    linkOrden.href = urlOrden
    linkOrden.setAttribute('download', `orden-trabajo-${folio}.pdf`)
    document.body.appendChild(linkOrden)
    linkOrden.click()
    document.body.removeChild(linkOrden)

    // Limpiar formulario después de guardar exitosamente
    setTimeout(() => {
      form.cliente = { nombre: '', email: '', fecha_nacimiento: '', edad: '', sexo: '' }
      form.estudios = []
      form.toma_muestra = ''
      form.fecha_reporte = ''
      form.fecha_validacion = ''
      form.medico_solicitante = ''
    }, 2000)

  } catch (error) {
    console.error('Error completo:', error)
    
    // Manejar diferentes tipos de errores
    if (error.response) {
      // El servidor respondió con un código de error
      const mensaje = error.response.data?.message || 
                     error.response.data?.error || 
                     'Error al guardar el reporte'
      
      // Si hay errores de validación del servidor
      if (error.response.data?.errors) {
        const errores = Object.values(error.response.data.errors).flat()
        mostrarNotificacion(errores[0] || mensaje, 'error')
      } else {
        mostrarNotificacion(mensaje, 'error')
      }
    } else if (error.request) {
      // La petición fue hecha pero no hubo respuesta
      mostrarNotificacion('No se pudo conectar con el servidor. Verifica tu conexión.', 'error')
    } else {
      // Algo pasó al configurar la petición
      mostrarNotificacion('Error al procesar la solicitud. Por favor intenta de nuevo.', 'error')
    }
  } finally {
    guardando.value = false
  }
}

function filtrarEstudios() {
  const termino = busquedaEstudio.value.toLowerCase().trim()
  if (termino === '') {
    estudiosFiltrados.value = [...estudiosDisponibles.value]
    return
  }
  estudiosFiltrados.value = estudiosDisponibles.value.filter(e =>
    e.nombre.toLowerCase().includes(termino)
  )
}

function mostrarTodosEstudios() {
  estudiosFiltrados.value = [...estudiosDisponibles.value]
}

function seleccionarEstudio(estudio) {
  // Evita duplicados
  if (form.estudios.some(e => e.id === estudio.id)) {
    mostrarNotificacion('Este estudio ya fue agregado', 'error')
    busquedaEstudio.value = ''
    estudiosFiltrados.value = []
    return
  }

  form.estudios.push({
    id: estudio.id,
    nombre: estudio.nombre,
    tipo_muestra: (estudio.tipo_muestra && estudio.tipo_muestra !== 'N/A') ? estudio.tipo_muestra : '',
    metodo: (estudio.metodo && estudio.metodo !== 'N/A') ? estudio.metodo : '',
    elaboro: 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS',
    valido: 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS',
    precio: parseFloat(estudio.precio) || 0,
    examenes: estudio.examenes.map(e => ({
      id: e.id,
      nombre_examen: e.nombre_examen,
      unidad: e.unidad,
      valor_referencia: e.valor_referencia,
      resultado: '',
      fuera_rango: false,
    })),
  })

  mostrarNotificacion(`${estudio.nombre} agregado correctamente`, 'success')
  busquedaEstudio.value = ''
  estudiosFiltrados.value = []
}

function eliminarEstudio(index) {
  const nombreEstudio = form.estudios[index].nombre
  if (confirm(`¿Deseas eliminar "${nombreEstudio}" del reporte?`)) {
    form.estudios.splice(index, 1)
    mostrarNotificacion('Estudio eliminado', 'success')
  }
}
</script>

<style scoped>
/* Animaciones para la lista de estudios */
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* Scrollbar personalizado */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: #a855f7;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: #9333ea;
}

/* Animación de hover suave */
.hover\:shadow-md {
  transition: box-shadow 0.3s ease;
}

/* Efecto de pulse para el spinner */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>