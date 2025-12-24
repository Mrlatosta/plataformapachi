<template>
  <Head title="Cotizaciones" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800">
          Cotizaciones de Estudios
        </h2>
        <span class="text-sm text-gray-500">Sistema de Laboratorio</span>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Notificación -->
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
            :class="{
              'bg-green-50 border-green-400 text-green-800': notification.type === 'success',
              'bg-red-50 border-red-400 text-red-800': notification.type === 'error'
            }"
            class="flex items-center p-4 mb-4 border-l-4 rounded-lg shadow-md"
          >
            <svg v-if="notification.type === 'success'" class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ notification.message }}</span>
          </div>
        </transition>

        <!-- Fecha de cotización -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Información de la Cotización
            </h3>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Fecha de cotización</label>
              <input 
                type="date" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                v-model="form.fecha_cotizacion" 
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Vigencia (días)</label>
              <input 
                type="number" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                v-model="form.vigencia" 
                placeholder="Ej: 30"
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
              Datos del Cliente
            </h3>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="space-y-2 md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Nombre completo / Empresa</label>
              <input 
                type="text" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                v-model="form.cliente.nombre" 
                placeholder="Ingrese el nombre del cliente o empresa"
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
              <label class="block text-sm font-medium text-gray-700">
                Teléfono <span class="text-gray-400 text-xs">(opcional)</span>
              </label>
              <input 
                type="tel" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                v-model="form.cliente.telefono" 
                placeholder="(000) 000-0000"
              />
            </div>
            <div class="space-y-2 md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">
                Dirección <span class="text-gray-400 text-xs">(opcional)</span>
              </label>
              <input 
                type="text" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                v-model="form.cliente.direccion" 
                placeholder="Calle, colonia, ciudad"
              />
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
              Estudios a Cotizar
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
                
                <!-- Lista desplegable -->
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
                  <div class="flex-1">
                    <h4 class="text-xl font-bold text-gray-800 flex items-center">
                      <span class="bg-purple-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm mr-3">
                        {{ index + 1 }}
                      </span>
                      {{ estudio.nombre }}
                    </h4>
                    <div v-if="estudio.examenes && estudio.examenes.length > 0" class="mt-3 ml-11">
                      <p class="text-xs font-semibold text-gray-600 mb-2">Incluye {{ estudio.examenes.length }} examen(es):</p>
                      <div class="flex flex-wrap gap-2">
                        <span 
                          v-for="(examen, idx) in estudio.examenes" 
                          :key="idx"
                          class="inline-flex items-center px-2 py-1 bg-white border border-purple-200 rounded-md text-xs text-gray-700"
                        >
                          <svg class="w-3 h-3 mr-1 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                          </svg>
                          {{ examen.nombre_examen }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <button
                    class="flex items-center px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors font-medium ml-4"
                    @click="eliminarEstudio(index)"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Eliminar
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Precio Unitario ($)</label>
                    <input 
                      type="number" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm font-semibold" 
                      step="0.01" 
                      v-model="estudio.precio"
                    />
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Cantidad</label>
                    <input 
                      type="number" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm" 
                      min="1"
                      v-model="estudio.cantidad"
                    />
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Subtotal ($)</label>
                    <input 
                      type="text" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-sm font-bold text-purple-600" 
                      :value="calcularSubtotal(estudio)"
                      readonly
                    />
                  </div>
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Descripción / Notas <span class="text-gray-400 text-xs">(opcional)</span>
                  </label>
                  <textarea 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm" 
                    rows="2"
                    v-model="estudio.descripcion"
                    placeholder="Información adicional sobre el estudio..."
                  ></textarea>
                </div>
              </div>
            </transition-group>

            <!-- Sin estudios -->
            <div 
              v-if="form.estudios.length === 0"
              class="text-center py-12 text-gray-500"
            >
              <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-lg font-medium">No hay estudios agregados</p>
              <p class="text-sm">Busca y agrega estudios para generar la cotización</p>
            </div>
          </div>
        </div>

        <!-- Resumen y descuento -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
          <div class="bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Resumen de Cotización
            </h3>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">Descuento (%)</label>
                  <input 
                    type="number" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all" 
                    min="0"
                    max="100"
                    step="0.01"
                    v-model="form.descuento"
                    placeholder="0.00"
                  />
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Notas adicionales <span class="text-gray-400 text-xs">(opcional)</span>
                  </label>
                  <textarea 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all" 
                    rows="3"
                    v-model="form.notas"
                    placeholder="Condiciones de pago, términos especiales, etc..."
                  ></textarea>
                </div>
              </div>
              <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-lg p-6 border border-purple-200">
                <div class="space-y-3">
                  <div class="flex justify-between items-center text-gray-700">
                    <span>Subtotal:</span>
                    <span class="font-semibold">${{ subtotal.toFixed(2) }}</span>
                  </div>
                  <div class="flex justify-between items-center text-gray-700">
                    <span>Descuento ({{ form.descuento }}%):</span>
                    <span class="font-semibold text-red-600">-${{ montoDescuento.toFixed(2) }}</span>
                  </div>
                  <hr class="border-purple-300">
                  <div class="flex justify-between items-center text-2xl font-bold text-purple-700">
                    <span>Total:</span>
                    <span>${{ total.toFixed(2) }}</span>
                  </div>
                  <p class="text-xs text-gray-600 italic text-center mt-2">
                    Precios en MXN
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex gap-4 justify-end">
          <button
            @click="limpiarFormulario"
            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-medium flex items-center"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Limpiar
          </button>
          <button
            @click="generarCotizacion"
            :disabled="guardando"
            class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
          >
            <svg v-if="!guardando" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
            </svg>
            <svg v-else class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ guardando ? 'Generando...' : 'Generar Cotización PDF' }}
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

const guardando = ref(false)
const notification = reactive({
  show: false,
  message: '',
  type: 'success'
})

function mostrarNotificacion(mensaje, tipo = 'success') {
  notification.message = mensaje
  notification.type = tipo
  notification.show = true
  
  setTimeout(() => {
    notification.show = false
  }, 4000)
}

const estudiosDisponibles = ref([])
const form = reactive({
  fecha_cotizacion: new Date().toISOString().split('T')[0],
  vigencia: 30,
  cliente: {
    nombre: '',
    email: '',
    telefono: '',
    direccion: '',
  },
  estudios: [],
  descuento: 0,
  notas: '',
})

const busquedaEstudio = ref('')
const estudiosFiltrados = ref([])

// Cálculos
const subtotal = computed(() => {
  return form.estudios.reduce((sum, e) => {
    return sum + (parseFloat(e.precio) || 0) * (parseInt(e.cantidad) || 0)
  }, 0)
})

const montoDescuento = computed(() => {
  return subtotal.value * (parseFloat(form.descuento) || 0) / 100
})

const total = computed(() => {
  return subtotal.value - montoDescuento.value
})

function calcularSubtotal(estudio) {
  const precio = parseFloat(estudio.precio) || 0
  const cantidad = parseInt(estudio.cantidad) || 0
  return (precio * cantidad).toFixed(2)
}

onMounted(async () => {
  try {
    const response = await axios.get('/api/estudios')
    estudiosDisponibles.value = response.data
  } catch (error) {
    console.error('Error al cargar estudios:', error)
    mostrarNotificacion('Error al cargar los estudios disponibles', 'error')
  }
})

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
    precio: parseFloat(estudio.precio) || 0,
    cantidad: 1,
    descripcion: '',
    examenes: estudio.examenes || [],
  })

  mostrarNotificacion(`${estudio.nombre} agregado correctamente`, 'success')
  busquedaEstudio.value = ''
  estudiosFiltrados.value = []
}

function eliminarEstudio(index) {
  const nombreEstudio = form.estudios[index].nombre
  if (confirm(`¿Deseas eliminar "${nombreEstudio}" de la cotización?`)) {
    form.estudios.splice(index, 1)
    mostrarNotificacion('Estudio eliminado', 'success')
  }
}

function limpiarFormulario() {
  if (form.estudios.length > 0) {
    if (!confirm('¿Estás seguro de limpiar toda la información?')) {
      return
    }
  }
  
  form.fecha_cotizacion = new Date().toISOString().split('T')[0]
  form.vigencia = 30
  form.cliente = { nombre: '', email: '', telefono: '', direccion: '' }
  form.estudios = []
  form.descuento = 0
  form.notas = ''
  
  mostrarNotificacion('Formulario limpiado', 'success')
}

async function generarCotizacion() {
  if (guardando.value) return
  
  // Validaciones
  if (!form.cliente.nombre || form.cliente.nombre.trim() === '') {
    mostrarNotificacion('Por favor ingresa el nombre del cliente', 'error')
    return
  }

  if (!form.fecha_cotizacion) {
    mostrarNotificacion('Por favor ingresa la fecha de cotización', 'error')
    return
  }
  
  if (form.estudios.length === 0) {
    mostrarNotificacion('Debes agregar al menos un estudio', 'error')
    return
  }

  // Validar cantidades
  for (let i = 0; i < form.estudios.length; i++) {
    const estudio = form.estudios[i]
    
    if (!estudio.precio || estudio.precio <= 0) {
      mostrarNotificacion(`El estudio "${estudio.nombre}" debe tener un precio válido`, 'error')
      return
    }

    if (!estudio.cantidad || estudio.cantidad <= 0) {
      mostrarNotificacion(`El estudio "${estudio.nombre}" debe tener una cantidad válida`, 'error')
      return
    }
  }

  guardando.value = true

  try {
    const response = await axios.post('/api/cotizaciones', form, {
      responseType: 'blob'
    })
    
    // Descargar el PDF
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const nombreArchivo = `cotizacion-${form.cliente.nombre.replace(/\s+/g, '-')}-${form.fecha_cotizacion}.pdf`
    link.setAttribute('download', nombreArchivo)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    mostrarNotificacion('¡Cotización generada con éxito!', 'success')

    // Limpiar formulario después de 2 segundos
    setTimeout(() => {
      limpiarFormulario()
    }, 2000)

  } catch (error) {
    console.error('Error completo:', error)
    
    if (error.response) {
      const mensaje = error.response.data?.message || 
                     error.response.data?.error || 
                     'Error al generar la cotización'
      
      if (error.response.data?.errors) {
        const errores = Object.values(error.response.data.errors).flat()
        mostrarNotificacion(errores[0] || mensaje, 'error')
      } else {
        mostrarNotificacion(mensaje, 'error')
      }
    } else if (error.request) {
      mostrarNotificacion('No se pudo conectar con el servidor. Verifica tu conexión.', 'error')
    } else {
      mostrarNotificacion('Error al procesar la solicitud. Por favor intenta de nuevo.', 'error')
    }
  } finally {
    guardando.value = false
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
