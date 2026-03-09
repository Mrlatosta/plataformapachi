<template>
  <Head title="Registro de Pacientes" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800">
          Registro de Pacientes
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

        <!-- Formulario de Registro -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
          <div class="bg-gradient-to-r from-emerald-50 to-teal-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
              {{ editando ? 'Editar Paciente' : 'Nuevo Paciente' }}
            </h3>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- Nombre -->
              <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">
                  Nombre completo <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  v-model="form.nombre"
                  placeholder="Nombre completo del paciente"
                />
              </div>

              <!-- Sexo -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Sexo</label>
                <select
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  v-model="form.sexo"
                >
                  <option value="">Seleccione</option>
                  <option>Masculino</option>
                  <option>Femenino</option>
                </select>
              </div>

              <!-- Fecha de nacimiento -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Fecha de nacimiento</label>
                <input
                  type="date"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  v-model="form.fecha_nacimiento"
                />
              </div>

              <!-- Edad (calculada) -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Edad</label>
                <input
                  type="number"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  v-model="form.edad"
                  readonly
                  placeholder="Se calcula automáticamente"
                />
              </div>

              <!-- Email -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Email <span class="text-gray-400 text-xs">(opcional)</span>
                </label>
                <input
                  type="email"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  v-model="form.email"
                  placeholder="correo@ejemplo.com"
                />
              </div>

              <!-- Teléfono -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Teléfono <span class="text-gray-400 text-xs">(opcional)</span>
                </label>
                <input
                  type="tel"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  v-model="form.telefono"
                  placeholder="(000) 000-0000"
                />
              </div>

              <!-- Dirección -->
              <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">
                  Dirección <span class="text-gray-400 text-xs">(opcional)</span>
                </label>
                <input
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  v-model="form.direccion"
                  placeholder="Calle, colonia, ciudad"
                />
              </div>

              <!-- Notas -->
              <div class="space-y-2 md:col-span-3">
                <label class="block text-sm font-medium text-gray-700">
                  Notas <span class="text-gray-400 text-xs">(opcional)</span>
                </label>
                <textarea
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  rows="2"
                  v-model="form.notas"
                  placeholder="Observaciones, alergias, condiciones especiales..."
                ></textarea>
              </div>
            </div>

            <!-- Botones del formulario -->
            <div class="flex gap-3 justify-end mt-6 pt-4 border-t border-gray-200">
              <button
                v-if="editando"
                @click="cancelarEdicion"
                class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-medium flex items-center"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Cancelar
              </button>
              <button
                @click="guardarPaciente"
                :disabled="guardando"
                class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-lg hover:from-emerald-700 hover:to-teal-700 transition-all font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              >
                <svg v-if="!guardando" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ guardando ? 'Guardando...' : (editando ? 'Actualizar Paciente' : 'Registrar Paciente') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Búsqueda de pacientes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              Buscar Pacientes
            </h3>
          </div>
          <div class="p-6">
            <div class="relative max-w-xl">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                type="text"
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="Buscar por nombre del paciente..."
                v-model="busqueda"
                @input="filtrarPacientes"
              />
            </div>
          </div>
        </div>

        <!-- Lista de pacientes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
          <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Pacientes Registrados
              <span class="ml-2 bg-purple-200 text-purple-800 text-xs font-bold px-2.5 py-0.5 rounded-full">
                {{ pacientesFiltrados.length }}
              </span>
            </h3>
          </div>

          <!-- Tabla -->
          <div class="overflow-x-auto">
            <table v-if="pacientesFiltrados.length > 0" class="w-full">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sexo</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Edad</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Teléfono</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                  <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr
                  v-for="paciente in pacientesFiltrados"
                  :key="paciente.id"
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <div class="flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 text-white font-semibold text-sm mr-3 flex-shrink-0">
                        {{ paciente.nombre.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <p class="font-medium text-gray-800">{{ paciente.nombre }}</p>
                        <p v-if="paciente.direccion" class="text-xs text-gray-500">{{ paciente.direccion }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">
                    <span
                      :class="{
                        'bg-blue-100 text-blue-800': paciente.sexo === 'Masculino',
                        'bg-pink-100 text-pink-800': paciente.sexo === 'Femenino',
                        'bg-gray-100 text-gray-500': !paciente.sexo,
                      }"
                      class="px-2 py-1 rounded-full text-xs font-medium"
                    >
                      {{ paciente.sexo || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">
                    {{ paciente.edad ?? 'N/A' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">
                    {{ paciente.telefono || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">
                    {{ paciente.email || 'N/A' }}
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2">
                      <button
                        @click="editarPaciente(paciente)"
                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                        title="Editar"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button
                        @click="eliminarPaciente(paciente)"
                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        title="Eliminar"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Sin pacientes -->
            <div
              v-else
              class="text-center py-12 text-gray-500"
            >
              <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <p class="text-lg font-medium">No hay pacientes registrados</p>
              <p class="text-sm">Registra tu primer paciente usando el formulario de arriba</p>
            </div>
          </div>
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
const editando = ref(false)
const pacienteEditandoId = ref(null)
const busqueda = ref('')
const pacientes = ref([])

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

const formInicial = () => ({
  nombre: '',
  email: '',
  telefono: '',
  fecha_nacimiento: '',
  edad: null,
  sexo: '',
  direccion: '',
  notas: '',
})

const form = reactive(formInicial())

// Calcular edad automáticamente
watch(() => form.fecha_nacimiento, (nuevaFecha) => {
  if (nuevaFecha) {
    const hoy = new Date()
    const nacimiento = new Date(nuevaFecha)
    let edad = hoy.getFullYear() - nacimiento.getFullYear()
    const mesDiff = hoy.getMonth() - nacimiento.getMonth()
    if (mesDiff < 0 || (mesDiff === 0 && hoy.getDate() < nacimiento.getDate())) {
      edad--
    }
    form.edad = edad >= 0 ? edad : 0
  } else {
    form.edad = null
  }
})

// Filtrar pacientes
const pacientesFiltrados = computed(() => {
  const termino = busqueda.value.toLowerCase().trim()
  if (!termino) return pacientes.value
  return pacientes.value.filter(p =>
    p.nombre.toLowerCase().includes(termino)
  )
})

function filtrarPacientes() {
  // El filtrado se hace reactivamente con el computed
}

// Cargar pacientes al montar
onMounted(async () => {
  await cargarPacientes()
})

async function cargarPacientes() {
  try {
    const response = await axios.get('/api/pacientes')
    pacientes.value = response.data
  } catch (error) {
    console.error('Error al cargar pacientes:', error)
    mostrarNotificacion('Error al cargar los pacientes', 'error')
  }
}

async function guardarPaciente() {
  if (guardando.value) return

  // Validación básica
  if (!form.nombre || form.nombre.trim() === '') {
    mostrarNotificacion('El nombre del paciente es obligatorio', 'error')
    return
  }

  guardando.value = true

  try {
    if (editando.value) {
      await axios.put(`/api/pacientes/${pacienteEditandoId.value}`, form)
      mostrarNotificacion('Paciente actualizado correctamente', 'success')
    } else {
      await axios.post('/api/pacientes', form)
      mostrarNotificacion('Paciente registrado correctamente', 'success')
    }

    limpiarFormulario()
    await cargarPacientes()
  } catch (error) {
    console.error('Error al guardar paciente:', error)
    if (error.response?.data?.errors) {
      const errores = Object.values(error.response.data.errors).flat()
      mostrarNotificacion(errores[0] || 'Error al guardar el paciente', 'error')
    } else {
      mostrarNotificacion(error.response?.data?.message || 'Error al guardar el paciente', 'error')
    }
  } finally {
    guardando.value = false
  }
}

function editarPaciente(paciente) {
  editando.value = true
  pacienteEditandoId.value = paciente.id
  form.nombre = paciente.nombre
  form.email = paciente.email || ''
  form.telefono = paciente.telefono || ''
  form.fecha_nacimiento = paciente.fecha_nacimiento ? paciente.fecha_nacimiento.split('T')[0] : ''
  form.edad = paciente.edad
  form.sexo = paciente.sexo || ''
  form.direccion = paciente.direccion || ''
  form.notas = paciente.notas || ''

  // Scroll al formulario
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function eliminarPaciente(paciente) {
  if (!confirm(`¿Estás seguro de eliminar al paciente "${paciente.nombre}"?`)) return

  try {
    await axios.delete(`/api/pacientes/${paciente.id}`)
    mostrarNotificacion('Paciente eliminado correctamente', 'success')
    await cargarPacientes()

    // Si estábamos editando este paciente, cancelar edición
    if (editando.value && pacienteEditandoId.value === paciente.id) {
      cancelarEdicion()
    }
  } catch (error) {
    console.error('Error al eliminar paciente:', error)
    mostrarNotificacion('Error al eliminar el paciente', 'error')
  }
}

function cancelarEdicion() {
  editando.value = false
  pacienteEditandoId.value = null
  limpiarFormulario()
}

function limpiarFormulario() {
  const inicial = formInicial()
  Object.keys(inicial).forEach(key => {
    form[key] = inicial[key]
  })
  editando.value = false
  pacienteEditandoId.value = null
}
</script>

<style scoped>
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
  background: #10b981;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: #059669;
}

.hover\:shadow-md {
  transition: box-shadow 0.3s ease;
}
</style>
