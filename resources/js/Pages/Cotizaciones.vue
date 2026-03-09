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

        <!-- Tabs principales: Nueva Cotización / Cotizaciones Guardadas -->
        <div class="flex bg-gray-100 rounded-xl p-1.5 shadow-inner">
          <button
            @click="vistaActual = 'nueva'"
            :class="[
              'flex-1 flex items-center justify-center px-6 py-3 rounded-lg text-sm font-semibold transition-all duration-200',
              vistaActual === 'nueva'
                ? 'bg-white text-purple-700 shadow-md'
                : 'text-gray-500 hover:text-gray-700'
            ]"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Nueva Cotización
          </button>
          <button
            @click="vistaActual = 'lista'; cargarCotizaciones()"
            :class="[
              'flex-1 flex items-center justify-center px-6 py-3 rounded-lg text-sm font-semibold transition-all duration-200',
              vistaActual === 'lista'
                ? 'bg-white text-purple-700 shadow-md'
                : 'text-gray-500 hover:text-gray-700'
            ]"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Cotizaciones Guardadas
            <span v-if="contadorPendientes > 0" class="ml-2 px-2 py-0.5 text-xs font-bold bg-amber-100 text-amber-700 rounded-full">
              {{ contadorPendientes }}
            </span>
          </button>
        </div>

        <!-- ==================== VISTA: NUEVA COTIZACIÓN ==================== -->
        <template v-if="vistaActual === 'nueva'">

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
            <div class="p-6">
              <!-- Tabs: Existente / Nuevo -->
              <div class="flex mb-6 bg-gray-100 rounded-lg p-1">
                <button
                  @click="modoPaciente = 'existente'"
                  :class="[
                    'flex-1 flex items-center justify-center px-4 py-2.5 rounded-md text-sm font-medium transition-all duration-200',
                    modoPaciente === 'existente'
                      ? 'bg-white text-emerald-700 shadow-sm'
                      : 'text-gray-500 hover:text-gray-700'
                  ]"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                  Buscar Paciente Existente
                </button>
                <button
                  @click="modoPaciente = 'nuevo'; limpiarPacienteSeleccionado()"
                  :class="[
                    'flex-1 flex items-center justify-center px-4 py-2.5 rounded-md text-sm font-medium transition-all duration-200',
                    modoPaciente === 'nuevo'
                      ? 'bg-white text-emerald-700 shadow-sm'
                      : 'text-gray-500 hover:text-gray-700'
                  ]"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  </svg>
                  Nuevo Cliente
                </button>
              </div>

              <!-- Modo: Buscar paciente existente -->
              <div v-if="modoPaciente === 'existente'">
                <div class="relative max-w-2xl mb-4">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </div>
                  <input
                    type="text"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                    placeholder="Escribe el nombre del paciente para buscar..."
                    v-model="busquedaPaciente"
                    @input="filtrarPacientes"
                    @focus="filtrarPacientes"
                  />

                  <transition
                    enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95"
                  >
                    <ul
                      v-if="pacientesFiltrados.length > 0 && !pacienteSeleccionado"
                      class="absolute w-full mt-2 bg-white rounded-lg shadow-lg border border-gray-200 max-h-64 overflow-y-auto z-50"
                    >
                      <li
                        v-for="paciente in pacientesFiltrados"
                        :key="paciente.id"
                        class="px-4 py-3 hover:bg-emerald-50 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
                        @click="seleccionarPaciente(paciente)"
                      >
                        <div class="flex justify-between items-center">
                          <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 text-white font-semibold text-xs mr-3">
                              {{ paciente.nombre.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                              <span class="font-medium text-gray-800">{{ paciente.nombre }}</span>
                              <span v-if="paciente.telefono" class="text-xs text-gray-500 ml-2">{{ paciente.telefono }}</span>
                            </div>
                          </div>
                          <span
                            :class="{
                              'bg-blue-100 text-blue-700': paciente.sexo === 'Masculino',
                              'bg-pink-100 text-pink-700': paciente.sexo === 'Femenino',
                            }"
                            class="text-xs font-medium px-2 py-0.5 rounded-full"
                            v-if="paciente.sexo"
                          >
                            {{ paciente.sexo }}
                          </span>
                        </div>
                      </li>
                    </ul>
                  </transition>
                </div>

                <!-- Paciente seleccionado badge -->
                <div v-if="pacienteSeleccionado" class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
                  <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 text-white font-semibold">
                    {{ pacienteSeleccionado.nombre.charAt(0).toUpperCase() }}
                  </div>
                  <div class="flex-1">
                    <p class="font-semibold text-emerald-800">{{ pacienteSeleccionado.nombre }}</p>
                    <p class="text-xs text-emerald-600">
                      <span v-if="pacienteSeleccionado.telefono">{{ pacienteSeleccionado.telefono }}</span>
                      <span v-if="pacienteSeleccionado.email"> &middot; {{ pacienteSeleccionado.email }}</span>
                    </p>
                  </div>
                  <button
                    @click="limpiarPacienteSeleccionado"
                    class="p-2 text-emerald-600 hover:bg-emerald-100 rounded-lg transition-colors"
                    title="Cambiar paciente"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Campos del cliente -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="space-y-2 md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700">Nombre completo / Empresa</label>
                  <input 
                    type="text" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                    :class="{ 'bg-gray-50': pacienteSeleccionado }"
                    v-model="form.cliente.nombre" 
                    placeholder="Ingrese el nombre del cliente o empresa"
                    :readonly="!!pacienteSeleccionado"
                  />
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Email <span class="text-gray-400 text-xs">(opcional)</span>
                  </label>
                  <input 
                    type="email" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                    :class="{ 'bg-gray-50': pacienteSeleccionado }"
                    v-model="form.cliente.email" 
                    placeholder="correo@ejemplo.com"
                    :readonly="!!pacienteSeleccionado"
                  />
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Teléfono <span class="text-gray-400 text-xs">(opcional)</span>
                  </label>
                  <input 
                    type="tel" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                    :class="{ 'bg-gray-50': pacienteSeleccionado }"
                    v-model="form.cliente.telefono" 
                    placeholder="(000) 000-0000"
                    :readonly="!!pacienteSeleccionado"
                  />
                </div>
                <div class="space-y-2 md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Dirección <span class="text-gray-400 text-xs">(opcional)</span>
                  </label>
                  <input 
                    type="text" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                    :class="{ 'bg-gray-50': pacienteSeleccionado }"
                    v-model="form.cliente.direccion" 
                    placeholder="Calle, colonia, ciudad"
                    :readonly="!!pacienteSeleccionado"
                  />
                </div>
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
              @click="guardarCotizacion"
              :disabled="guardando"
              class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              <svg v-if="!guardando" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
              </svg>
              <svg v-else class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ guardando ? 'Guardando...' : 'Guardar Cotización' }}
            </button>
          </div>

        </template>

        <!-- ==================== VISTA: COTIZACIONES GUARDADAS ==================== -->
        <template v-if="vistaActual === 'lista'">

          <!-- Filtros -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 flex flex-col sm:flex-row gap-4">
              <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input
                  type="text"
                  class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                  placeholder="Buscar por folio, nombre del cliente..."
                  v-model="filtroBusqueda"
                />
              </div>
              <select
                v-model="filtroEstado"
                class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
              >
                <option value="todos">Todos los estados</option>
                <option value="pendiente_pago">Pendiente de Pago</option>
                <option value="pendiente_captura">Pendiente de Captura</option>
                <option value="completada">Completada</option>
              </select>
            </div>
          </div>

          <!-- Cargando -->
          <div v-if="cargandoLista" class="text-center py-12">
            <svg class="animate-spin w-10 h-10 mx-auto text-purple-500 mb-3" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-500">Cargando cotizaciones...</p>
          </div>

          <!-- Lista vacía -->
          <div v-else-if="cotizacionesFiltradas.length === 0" class="text-center py-16">
            <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-lg font-medium text-gray-600">No hay cotizaciones</p>
            <p class="text-sm text-gray-500 mt-1">Crea una nueva cotización para empezar</p>
            <button
              @click="vistaActual = 'nueva'"
              class="mt-4 px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all font-medium"
            >
              Crear Cotización
            </button>
          </div>

          <!-- Lista de cotizaciones -->
          <div v-else class="space-y-4">
            <div
              v-for="cotizacion in cotizacionesFiltradas"
              :key="cotizacion.id"
              class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-all"
            >
              <div class="p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                  <!-- Info principal -->
                  <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                      <h3 class="text-lg font-bold text-gray-800">{{ cotizacion.folio }}</h3>
                      <span :class="badgeEstado(cotizacion.estado)" class="px-3 py-1 text-xs font-bold rounded-full">
                        {{ etiquetaEstado(cotizacion.estado) }}
                      </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-gray-600">
                      <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ cotizacion.nombre_cliente }}
                      </span>
                      <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ formatoFecha(cotizacion.fecha_cotizacion) }}
                      </span>
                      <span class="flex items-center" v-if="cotizacion.estudios && cotizacion.estudios.length > 0">
                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        {{ cotizacion.estudios.length }} estudio(s)
                      </span>
                    </div>
                    <!-- Lista de estudios -->
                    <div v-if="cotizacion.estudios && cotizacion.estudios.length > 0" class="mt-3 flex flex-wrap gap-2">
                      <span
                        v-for="ce in cotizacion.estudios"
                        :key="ce.id"
                        class="inline-flex items-center px-2.5 py-1 bg-purple-50 border border-purple-200 rounded-md text-xs font-medium text-purple-700"
                      >
                        {{ ce.estudio?.nombre || 'Estudio' }}
                      </span>
                    </div>
                  </div>

                  <!-- Total y acciones -->
                  <div class="flex flex-col items-end gap-3">
                    <div class="text-right">
                      <p class="text-2xl font-bold text-purple-700">${{ parseFloat(cotizacion.total).toFixed(2) }}</p>
                      <p class="text-xs text-gray-500" v-if="parseFloat(cotizacion.descuento) > 0">
                        Descuento: {{ cotizacion.descuento }}%
                      </p>
                    </div>
                    <div class="flex gap-2 flex-wrap justify-end">
                      <!-- Cambiar estado -->
                      <template v-if="cotizacion.estado === 'pendiente_pago'">
                        <button
                          @click="cambiarEstado(cotizacion.id, 'pendiente_captura')"
                          class="px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-xs font-semibold flex items-center"
                          title="Marcar como pagada"
                        >
                          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          Marcar Pagada
                        </button>
                      </template>
                      <template v-if="cotizacion.estado === 'pendiente_captura'">
                        <button
                          @click="irACaptura(cotizacion)"
                          class="px-3 py-2 bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-colors text-xs font-semibold flex items-center"
                          title="Ir a capturar resultados"
                        >
                          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                          Capturar Resultados
                        </button>
                        <button
                          @click="cambiarEstado(cotizacion.id, 'completada')"
                          class="px-3 py-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors text-xs font-semibold flex items-center"
                          title="Marcar como completada"
                        >
                          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                          Completar
                        </button>
                      </template>
                      <!-- Descargar PDF -->
                      <button
                        @click="descargarPDF(cotizacion.id)"
                        class="px-3 py-2 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors text-xs font-semibold flex items-center"
                        title="Descargar PDF"
                      >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        PDF
                      </button>
                      <!-- Eliminar -->
                      <button
                        @click="eliminarCotizacion(cotizacion.id, cotizacion.folio)"
                        class="px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors text-xs font-semibold flex items-center"
                        title="Eliminar cotización"
                      >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Eliminar
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </template>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'

// Vista activa
const vistaActual = ref('lista')

const guardando = ref(false)
const cargandoLista = ref(false)
const notification = reactive({
  show: false,
  message: '',
  type: 'success'
})

// Estado del paciente
const modoPaciente = ref('existente')
const busquedaPaciente = ref('')
const pacientesDisponibles = ref([])
const pacientesFiltrados = ref([])
const pacienteSeleccionado = ref(null)

// Estado de cotizaciones guardadas
const cotizacionesGuardadas = ref([])
const filtroBusqueda = ref('')
const filtroEstado = ref('todos')

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
  paciente_id: null,
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

// Contador de pendientes para el badge del tab
const contadorPendientes = computed(() => {
  return cotizacionesGuardadas.value.filter(c => c.estado !== 'completada').length
})

// Cotizaciones filtradas
const cotizacionesFiltradas = computed(() => {
  let resultado = [...cotizacionesGuardadas.value]
  
  if (filtroEstado.value !== 'todos') {
    resultado = resultado.filter(c => c.estado === filtroEstado.value)
  }
  
  if (filtroBusqueda.value.trim()) {
    const termino = filtroBusqueda.value.toLowerCase().trim()
    resultado = resultado.filter(c =>
      c.folio.toLowerCase().includes(termino) ||
      c.nombre_cliente.toLowerCase().includes(termino)
    )
  }
  
  return resultado
})

function calcularSubtotal(estudio) {
  const precio = parseFloat(estudio.precio) || 0
  const cantidad = parseInt(estudio.cantidad) || 0
  return (precio * cantidad).toFixed(2)
}

onMounted(async () => {
  try {
    const [estudiosRes, pacientesRes] = await Promise.all([
      axios.get('/api/estudios'),
      axios.get('/api/pacientes'),
    ])
    estudiosDisponibles.value = estudiosRes.data
    pacientesDisponibles.value = pacientesRes.data
  } catch (error) {
    console.error('Error al cargar datos:', error)
    mostrarNotificacion('Error al cargar datos iniciales', 'error')
  }
  
  // Cargar cotizaciones guardadas
  await cargarCotizaciones()
})

async function cargarCotizaciones() {
  cargandoLista.value = true
  try {
    const response = await axios.get('/api/cotizaciones')
    cotizacionesGuardadas.value = response.data
  } catch (error) {
    console.error('Error al cargar cotizaciones:', error)
    mostrarNotificacion('Error al cargar las cotizaciones', 'error')
  } finally {
    cargandoLista.value = false
  }
}

// Funciones de pacientes
function filtrarPacientes() {
  const termino = busquedaPaciente.value.toLowerCase().trim()
  if (termino === '') {
    pacientesFiltrados.value = [...pacientesDisponibles.value]
    return
  }
  pacientesFiltrados.value = pacientesDisponibles.value.filter(p =>
    p.nombre.toLowerCase().includes(termino)
  )
}

function seleccionarPaciente(paciente) {
  pacienteSeleccionado.value = paciente
  form.paciente_id = paciente.id
  form.cliente.nombre = paciente.nombre
  form.cliente.email = paciente.email || ''
  form.cliente.telefono = paciente.telefono || ''
  form.cliente.direccion = paciente.direccion || ''
  busquedaPaciente.value = ''
  pacientesFiltrados.value = []
  mostrarNotificacion(`Paciente "${paciente.nombre}" seleccionado`, 'success')
}

function limpiarPacienteSeleccionado() {
  pacienteSeleccionado.value = null
  form.paciente_id = null
  form.cliente = { nombre: '', email: '', telefono: '', direccion: '' }
  busquedaPaciente.value = ''
  pacientesFiltrados.value = []
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
  form.paciente_id = null
  form.cliente = { nombre: '', email: '', telefono: '', direccion: '' }
  form.estudios = []
  form.descuento = 0
  form.notas = ''
  pacienteSeleccionado.value = null
  busquedaPaciente.value = ''
  modoPaciente.value = 'existente'
  
  mostrarNotificacion('Formulario limpiado', 'success')
}

// ==================== GUARDAR COTIZACIÓN ====================
async function guardarCotizacion() {
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
    const response = await axios.post('/api/cotizaciones', form)
    
    mostrarNotificacion(`Cotización ${response.data.cotizacion.folio} guardada exitosamente`, 'success')

    // Resetear formulario 
    form.fecha_cotizacion = new Date().toISOString().split('T')[0]
    form.vigencia = 30
    form.paciente_id = null
    form.cliente = { nombre: '', email: '', telefono: '', direccion: '' }
    form.estudios = []
    form.descuento = 0
    form.notas = ''
    pacienteSeleccionado.value = null
    busquedaPaciente.value = ''
    modoPaciente.value = 'existente'

    // Recargar lista y cambiar a vista de lista
    await cargarCotizaciones()
    vistaActual.value = 'lista'

  } catch (error) {
    console.error('Error al guardar:', error)
    
    if (error.response) {
      const mensaje = error.response.data?.message || 'Error al guardar la cotización'
      
      if (error.response.data?.errors) {
        const errores = Object.values(error.response.data.errors).flat()
        mostrarNotificacion(errores[0] || mensaje, 'error')
      } else {
        mostrarNotificacion(mensaje, 'error')
      }
    } else if (error.request) {
      mostrarNotificacion('No se pudo conectar con el servidor.', 'error')
    } else {
      mostrarNotificacion('Error al procesar la solicitud.', 'error')
    }
  } finally {
    guardando.value = false
  }
}

// ==================== ACCIONES SOBRE COTIZACIONES ====================
async function cambiarEstado(id, nuevoEstado) {
  const etiquetas = {
    'pendiente_captura': 'pendiente de captura',
    'completada': 'completada'
  }
  
  if (!confirm(`¿Cambiar el estado a "${etiquetas[nuevoEstado]}"?`)) return

  try {
    await axios.put(`/api/cotizaciones/${id}/estado`, { estado: nuevoEstado })
    mostrarNotificacion(`Estado actualizado a "${etiquetas[nuevoEstado]}"`, 'success')
    await cargarCotizaciones()
  } catch (error) {
    console.error('Error al cambiar estado:', error)
    mostrarNotificacion('Error al cambiar el estado', 'error')
  }
}

function irACaptura(cotizacion) {
  router.visit(`/captura-resultados?cotizacion_id=${cotizacion.id}`)
}

async function descargarPDF(id) {
  try {
    const response = await axios.get(`/api/cotizaciones/${id}/pdf`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `cotizacion-${id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    mostrarNotificacion('PDF descargado correctamente', 'success')
  } catch (error) {
    console.error('Error al descargar PDF:', error)
    mostrarNotificacion('Error al descargar el PDF', 'error')
  }
}

async function eliminarCotizacion(id, folio) {
  if (!confirm(`¿Estás seguro de eliminar la cotización ${folio}? Esta acción no se puede deshacer.`)) return

  try {
    await axios.delete(`/api/cotizaciones/${id}`)
    mostrarNotificacion(`Cotización ${folio} eliminada`, 'success')
    await cargarCotizaciones()
  } catch (error) {
    console.error('Error al eliminar:', error)
    mostrarNotificacion('Error al eliminar la cotización', 'error')
  }
}

// ==================== HELPERS ====================
function badgeEstado(estado) {
  const estilos = {
    'pendiente_pago': 'bg-amber-100 text-amber-800 border border-amber-300',
    'pendiente_captura': 'bg-blue-100 text-blue-800 border border-blue-300',
    'completada': 'bg-green-100 text-green-800 border border-green-300',
  }
  return estilos[estado] || 'bg-gray-100 text-gray-800'
}

function etiquetaEstado(estado) {
  const etiquetas = {
    'pendiente_pago': 'Pendiente de Pago',
    'pendiente_captura': 'Pendiente de Captura',
    'completada': 'Completada',
  }
  return etiquetas[estado] || estado
}

function formatoFecha(fecha) {
  if (!fecha) return ''
  const d = new Date(fecha)
  return d.toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}
</script>

<style scoped>
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

.hover\:shadow-md {
  transition: box-shadow 0.3s ease;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: .5; }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
