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
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Información de Fechas
            </h3>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="space-y-2">
              <label for="toma-muestra" class="block text-sm font-medium text-gray-700">Toma de muestra</label>
              <input id="toma-muestra" type="datetime-local" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" v-model="form.toma_muestra" />
            </div>
            <div class="space-y-2">
              <label for="fecha-reporte" class="block text-sm font-medium text-gray-700">Fecha de reporte</label>
              <input id="fecha-reporte" type="datetime-local" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" v-model="form.fecha_reporte" />
            </div>
            <div class="space-y-2">
              <label for="fecha-validacion" class="block text-sm font-medium text-gray-700">Fecha de validación</label>
              <input v-if="esAdmin" id="fecha-validacion" type="datetime-local" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" v-model="form.fecha_validacion" />
              <p v-else id="fecha-validacion" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500">
                Se registra cuando el Q.F.B. valida
              </p>
            </div>
            <div class="space-y-2">
              <label for="medico-solicitante" class="block text-sm font-medium text-gray-700">Médico solicitante</label>
              <SelectorMedico id="medico-solicitante" v-model="form.medico_id" :medicos="medicosDisponibles"
                @nombre="(n) => (form.medico_solicitante = n)" @creado="agregarMedico" />
            </div>
          </div>
        </section>

        <!-- Datos del Paciente (T1) -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-visible">
          <div class="bg-gradient-to-r from-emerald-50 to-teal-50 px-6 py-4 border-b border-gray-200 rounded-t-xl">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Datos del Paciente
            </h3>
          </div>
          <div class="p-6 space-y-4">
            <!-- Paciente registrado elegido -->
            <div v-if="pacienteSeleccionado" class="flex flex-wrap items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
              <div class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-600 text-white font-semibold" aria-hidden="true">
                {{ pacienteSeleccionado.nombre.charAt(0).toUpperCase() }}
              </div>
              <div class="flex-1 min-w-[12rem]">
                <p class="font-semibold text-emerald-900">Paciente registrado</p>
                <p class="text-xs text-emerald-800">
                  <template v-if="pacienteSeleccionado.ordenes">
                    {{ pacienteSeleccionado.ordenes }} {{ pacienteSeleccionado.ordenes === 1 ? 'orden anterior' : 'órdenes anteriores' }}
                    <span v-if="pacienteSeleccionado.ultima_visita"> · última el {{ formatoFechaCorta(pacienteSeleccionado.ultima_visita) }}</span>
                  </template>
                  <template v-else-if="pacienteSeleccionado.ordenes === 0">Sin órdenes anteriores</template>
                  <span v-if="anteriores.hayHistorial.value"> · se muestra su resultado anterior junto a cada analito</span>
                </p>
              </div>
              <a :href="route('pacientes.historial', pacienteSeleccionado.id)" target="_blank" rel="noopener"
                class="px-3 py-1.5 text-sm font-medium text-emerald-800 bg-white border border-emerald-300 rounded-lg hover:bg-emerald-100">
                Ver historial
              </a>
              <button type="button" @click="limpiarPacienteSeleccionado"
                class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Cambiar paciente
              </button>
            </div>
            <p v-else class="text-sm text-gray-500">
              Escribe el nombre: si el paciente ya vino antes aparecerá en la lista y se llenan sus datos. Si no, se registra al guardar.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div class="space-y-2 md:col-span-2">
                <label for="paciente-nombre" class="block text-sm font-medium text-gray-700">Nombre completo</label>
                <PacienteBuscador
                  id="paciente-nombre"
                  v-model="form.cliente.nombre"
                  :readonly="!!pacienteSeleccionado"
                  @seleccionar="seleccionarPaciente"
                />
              </div>
              <div class="space-y-2">
                <label for="paciente-nacimiento" class="block text-sm font-medium text-gray-700">Fecha de nacimiento</label>
                <input id="paciente-nacimiento" type="date" v-model="form.cliente.fecha_nacimiento"
                  :readonly="bloqueado('fecha_nacimiento')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  :class="{ 'bg-gray-50': bloqueado('fecha_nacimiento') }" />
              </div>
              <div class="space-y-2">
                <label for="paciente-edad" class="block text-sm font-medium text-gray-700">Edad</label>
                <input id="paciente-edad" type="number" v-model="form.cliente.edad" readonly placeholder="Se calcula sola"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" />
              </div>
              <div class="space-y-2">
                <label for="paciente-sexo" class="block text-sm font-medium text-gray-700">Sexo</label>
                <select id="paciente-sexo" v-model="form.cliente.sexo" :disabled="bloqueado('sexo')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  :class="{ 'bg-gray-50': bloqueado('sexo') }">
                  <option value="">Seleccione</option>
                  <option>Masculino</option>
                  <option>Femenino</option>
                </select>
              </div>
              <div class="space-y-2">
                <label for="paciente-telefono" class="block text-sm font-medium text-gray-700">
                  Teléfono <span class="text-gray-400 text-xs">(para enviar resultados por WhatsApp)</span>
                </label>
                <input id="paciente-telefono" type="tel" inputmode="tel" v-model="form.cliente.telefono" placeholder="998 123 4567"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
              </div>
              <div class="space-y-2">
                <label for="paciente-email" class="block text-sm font-medium text-gray-700">
                  Email <span class="text-gray-400 text-xs">(opcional)</span>
                </label>
                <input id="paciente-email" type="email" v-model="form.cliente.email" placeholder="correo@ejemplo.com"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
              </div>
            </div>
          </div>
        </section>

        <!-- Estudios -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-visible">
          <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200 rounded-t-xl">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
              Estudios y Exámenes
            </h3>
          </div>
          <div class="p-6">
            <!-- Búsqueda de estudios -->
            <div class="mb-6 max-w-2xl">
              <label for="buscar-estudio" class="block text-sm font-medium text-gray-700 mb-2">Agregar estudio</label>
              <div class="relative">
                <input
                  id="buscar-estudio"
                  type="text"
                  autocomplete="off"
                  class="w-full pl-4 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  placeholder="Escribe el nombre del estudio o haz clic para ver todos..."
                  v-model="busquedaEstudio"
                  @focus="mostrarTodosEstudios"
                  @input="filtrarEstudios"
                  @keydown.esc="estudiosFiltrados = []"
                  @blur="cerrarListaEstudios"
                />
                <ul v-if="estudiosFiltrados.length > 0" class="absolute w-full mt-2 bg-white rounded-lg shadow-lg border border-gray-200 max-h-64 overflow-y-auto z-50">
                  <li v-for="estudio in estudiosFiltrados" :key="estudio.id"
                    class="px-4 py-3 hover:bg-purple-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                    @mousedown.prevent="seleccionarEstudio(estudio)">
                    <div class="flex justify-between items-center">
                      <span class="font-medium text-gray-800">{{ estudio.nombre }}</span>
                      <span class="text-sm font-semibold text-purple-600">${{ parseFloat(estudio.precio).toFixed(2) }}</span>
                    </div>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Lista de estudios agregados -->
            <div class="space-y-4">
              <article
                v-for="(estudio, index) in form.estudios"
                :key="`estudio-${estudio.id}`"
                class="rounded-xl p-6 border shadow-sm"
                :class="estudio.incluir_en_reporte ? 'bg-gray-50 border-gray-200' : 'bg-white border-dashed border-gray-300'"
              >
                <div class="flex flex-wrap justify-between items-start gap-3 mb-4">
                  <h4 class="text-xl font-bold text-gray-800 flex items-center">
                    <span class="bg-purple-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm mr-3" aria-hidden="true">{{ index + 1 }}</span>
                    {{ estudio.nombre }}
                  </h4>
                  <div class="flex flex-wrap items-center gap-4">
                    <Interruptor
                      v-model="estudio.incluir_en_reporte"
                      etiqueta="Incluir en reporte"
                      :descripcion="estudio.incluir_en_reporte ? 'Sale en el PDF' : 'No sale en el PDF; captúralo cuando esté listo'"
                    />
                    <button type="button"
                      class="flex items-center px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 font-medium"
                      @click="eliminarEstudio(index)">
                      Quitar
                    </button>
                  </div>
                </div>

                <div v-if="!estudio.incluir_en_reporte" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900">
                  Este estudio queda en la orden como <strong>pendiente</strong>. Cuando tengas el resultado, ábrelo en
                  <em>Lista de reportes → Editar</em>, captúralo y enciende «Incluir en reporte».
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" :class="esAdmin ? 'lg:grid-cols-5' : 'lg:grid-cols-4'">
                  <div class="space-y-1">
                    <label :for="`muestra-${index}`" class="block text-sm font-medium text-gray-700">Tipo de muestra</label>
                    <ListaOpciones :id="`muestra-${index}`" v-model="estudio.tipo_muestra" :de-siempre="estudio.muestra_catalogo"
                      :opciones="estudio.opciones_muestra" :comunes="TIPOS_MUESTRA_COMUNES" :disabled="!esAdmin" />
                  </div>
                  <div class="space-y-1">
                    <label :for="`metodo-${index}`" class="block text-sm font-medium text-gray-700">Método</label>
                    <ListaOpciones :id="`metodo-${index}`" v-model="estudio.metodo" :de-siempre="estudio.metodo_catalogo"
                      :opciones="estudio.opciones_metodo" :disabled="!esAdmin" />
                  </div>
                  <div class="space-y-1">
                    <label :for="`elaboro-${index}`" class="block text-sm font-medium text-gray-700">Elaboró</label>
                    <input :id="`elaboro-${index}`" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm" v-model="estudio.elaboro" />
                  </div>
                  <div v-if="esAdmin" class="space-y-1">
                    <label :for="`valido-${index}`" class="block text-sm font-medium text-gray-700">Validó</label>
                    <input :id="`valido-${index}`" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm" v-model="estudio.valido" />
                  </div>
                  <div class="space-y-1">
                    <label :for="`precio-${index}`" class="block text-sm font-medium text-gray-700">Precio ($)</label>
                    <input :id="`precio-${index}`" type="number" step="0.01" v-model="estudio.precio" :readonly="!permisos.editar_precios"
                      :title="permisos.editar_precios ? '' : 'Precio del catálogo; solo el administrador lo cambia'"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm font-semibold"
                      :class="{ 'bg-gray-50 text-gray-600': !permisos.editar_precios }" />
                  </div>
                </div>

                <div class="mb-4 space-y-1">
                  <label :for="`obs-${index}`" class="block text-sm font-medium text-gray-700">Observaciones del estudio</label>
                  <textarea :id="`obs-${index}`" v-model="estudio.observaciones" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                    placeholder="Opcional. Se imprime a todo el ancho debajo del estudio." />
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                      <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Examen</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Valor de referencia</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider min-w-[20rem]">Resultado</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                      <template v-for="(examen, exIndex) in estudio.examenes" :key="examen.id">
                      <tr class="align-top">
                        <td class="px-4 py-3 text-sm text-gray-900">
                          <label :for="`res-${index}-${exIndex}`">{{ examen.nombre_examen }}</label>
                          <span v-if="examen.unidad" class="block text-xs text-gray-500">{{ examen.unidad }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 max-w-xs">
                          <ul v-if="examen.niveles?.length" class="space-y-0.5 text-xs">
                            <li v-for="nivel in examen.niveles" :key="nivel.id"
                              :class="nivel.es_normal ? 'font-semibold text-gray-800' : ''">
                              {{ textoNivel(nivel) }} · {{ nivel.interpretacion }}
                            </li>
                          </ul>
                          <span v-else>{{ examen.valor_referencia }}</span>
                        </td>
                        <td class="px-4 py-3">
                          <ResultadoCelda
                            :examen="examen"
                            :input-id="`res-${index}-${exIndex}`"
                            :anterior="anteriores.anteriorDe(examen.id, examen.nombre_examen)"
                            @escribir="onEscribir(examen)"
                            @marca="(valor) => aplicarMarcaManual(examen, valor)"
                          />
                        </td>
                      </tr>
                      <FilasRenales :filas="renal.filas[`${index}:${exIndex}`]" />
                      </template>
                    </tbody>
                  </table>
                </div>
              </article>
            </div>

            <div v-if="form.estudios.length === 0" class="text-center py-12">
              <p class="text-gray-500 text-lg">No hay estudios agregados</p>
              <p class="text-gray-400 text-sm mt-2">Busca y selecciona estudios para agregar</p>
            </div>
          </div>
        </section>

        <!-- Total y botones -->
        <section class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="w-full lg:w-auto">
            <label class="flex items-start gap-3 mb-3 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-green-50">
              <input type="checkbox" class="mt-0.5 w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500" v-model="form.aplica_iva" />
              <span>
                <span class="block text-sm font-medium text-gray-700">Aplicar IVA ({{ IVA_PORCENTAJE }}%)</span>
                <span class="block text-xs text-gray-500">Se desglosa en la orden de trabajo y en el reporte.</span>
              </span>
            </label>
            <label class="flex items-center justify-between gap-3 mb-3 p-3 border border-gray-300 rounded-lg">
              <span class="text-sm font-medium text-gray-700">
                Descuento (%)
                <span v-if="!permisos.editar_precios" class="block text-xs font-normal text-gray-500">Solo el administrador aplica descuentos</span>
              </span>
              <input type="number" min="0" max="100" step="0.01" v-model.number="form.descuento" :readonly="!permisos.editar_precios"
                class="w-24 text-sm text-right rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500"
                :class="{ 'bg-gray-50': !permisos.editar_precios }" />
            </label>
            <div v-if="form.aplica_iva || montoDescuento > 0" class="space-y-1 mb-2 text-gray-600">
              <div class="flex items-center justify-between gap-6"><span>Subtotal:</span><span class="font-semibold">${{ totalEstudios.toFixed(2) }}</span></div>
              <div v-if="montoDescuento > 0" class="flex items-center justify-between gap-6"><span>Descuento ({{ form.descuento }}%):</span><span class="font-semibold text-red-600">-${{ montoDescuento.toFixed(2) }}</span></div>
              <div v-if="form.aplica_iva" class="flex items-center justify-between gap-6"><span>IVA ({{ IVA_PORCENTAJE }}%):</span><span class="font-semibold">${{ montoIva.toFixed(2) }}</span></div>
            </div>
            <div class="flex items-center">
              <span class="text-gray-600 text-lg mr-3">Total de la orden:</span>
              <span class="text-3xl font-bold text-green-600">${{ totalConIva.toFixed(2) }}</span>
            </div>
          </div>
          <div class="flex flex-col gap-2 w-full lg:w-auto">
            <div class="flex flex-col sm:flex-row gap-3">
              <button type="button"
                class="flex items-center justify-center px-6 py-4 bg-white border-2 border-blue-500 text-blue-600 rounded-lg hover:bg-blue-50 font-semibold text-lg disabled:opacity-50 disabled:cursor-not-allowed"
                @click="previsualizarReporte" :disabled="generandoPreview || guardando">
                {{ generandoPreview ? 'Generando…' : 'Previsualizar' }}
              </button>
              <button type="button"
                class="flex items-center justify-center px-8 py-4 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 shadow-md font-semibold text-lg disabled:opacity-50 disabled:cursor-not-allowed"
                @click="guardarReporte" :disabled="guardando || generandoPreview">
                {{ guardando ? 'Guardando…' : (esAdmin ? 'Guardar y validar' : 'Guardar para validación') }}
              </button>
            </div>
            <p class="text-xs text-gray-500 sm:text-right">
              {{ esAdmin
                ? 'Lo capturado queda validado con tu usuario.'
                : 'El Q.F.B. revisará y validará los resultados antes de entregarlos.' }}
            </p>
          </div>
        </section>

        <!-- Modal de vista previa -->
        <div v-if="mostrarPreview" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="cerrarPreview"
          role="dialog" aria-modal="true" aria-labelledby="titulo-preview">
          <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[92vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
              <div>
                <h3 id="titulo-preview" class="text-lg font-bold text-gray-800">Vista previa del reporte</h3>
                <p class="text-xs text-gray-500">Todavía no se ha guardado nada. Revisa el documento y decide si continuar editando.</p>
              </div>
              <button type="button" @click="cerrarPreview" class="text-gray-400 hover:text-gray-600" aria-label="Cerrar vista previa">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
            <div class="flex items-center gap-2 px-6 py-3 border-b border-gray-200 bg-gray-50">
              <button type="button" @click="cambiarDocumentoPreview('reporte')" :disabled="generandoPreview"
                class="px-4 py-2 rounded-lg text-sm font-semibold disabled:opacity-50"
                :class="documentoPreview === 'reporte' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-100'">
                Reporte de resultados
              </button>
              <button type="button" @click="cambiarDocumentoPreview('orden')" :disabled="generandoPreview"
                class="px-4 py-2 rounded-lg text-sm font-semibold disabled:opacity-50"
                :class="documentoPreview === 'orden' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-100'">
                Orden de trabajo
              </button>
              <span v-if="generandoPreview" class="text-xs text-gray-500 ml-2">Generando…</span>
            </div>
            <div class="flex-1 bg-gray-100 relative">
              <iframe v-if="previewUrl" :src="previewUrl" class="w-full h-full" title="Vista previa del documento"></iframe>
            </div>
            <div class="flex flex-col sm:flex-row justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-white">
              <button type="button" @click="cerrarPreview" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold">
                Seguir editando
              </button>
              <button type="button" @click="confirmarYGuardar" :disabled="guardando || generandoPreview"
                class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold shadow-md disabled:opacity-50">
                Confirmar y guardar
              </button>
            </div>
          </div>
        </div>

        <!-- Notificación -->
        <div
          v-if="notification.show"
          role="status"
          aria-live="polite"
          :class="[
            'fixed bottom-8 right-8 px-6 py-4 rounded-lg shadow-xl z-50 max-w-md',
            notification.type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
          ]"
        >
          <p :class="['font-semibold', notification.type === 'success' ? 'text-green-800' : 'text-red-800']">
            {{ notification.message }}
          </p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { IVA_PORCENTAJE, calcularTotales } from '@/utils/iva'
import PacienteBuscador from '@/Components/Captura/PacienteBuscador.vue'
import ResultadoCelda from '@/Components/Captura/ResultadoCelda.vue'
import Interruptor from '@/Components/Captura/Interruptor.vue'
import FilasRenales from '@/Components/Captura/FilasRenales.vue'
import ListaOpciones from '@/Components/Captura/ListaOpciones.vue'
import SelectorMedico from '@/Components/Captura/SelectorMedico.vue'
import { analizarFuncionRenal } from '@/utils/funcionRenal'
import {
  TIPOS_MUESTRA_COMUNES,
  aplicarMarcaManual,
  formatoFechaCorta,
  marcarRango,
  recalcularOrden,
  usePermisos,
  useResultadosAnteriores,
} from '@/utils/captura'

const props = defineProps({
  cotizacionPrecargada: { type: Object, default: null },
})

const RESPONSABLE = 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS'

const page = usePage()
const permisos = usePermisos()
const esAdmin = computed(() => !!permisos.value.validar)
const nombreUsuario = computed(() => page.props.auth?.user?.name || '')

const guardando = ref(false)
const pacienteSeleccionado = ref(null)
const anteriores = useResultadosAnteriores()

const notification = reactive({ show: false, message: '', type: 'success' })
let temporizadorAviso = null

function mostrarNotificacion(mensaje, tipo = 'success', duracion = 5000) {
  notification.message = mensaje
  notification.type = tipo
  notification.show = true
  clearTimeout(temporizadorAviso)
  temporizadorAviso = setTimeout(() => { notification.show = false }, duracion)
}

const clienteVacio = () => ({ nombre: '', email: '', telefono: '', fecha_nacimiento: '', edad: '', sexo: '' })

const form = reactive({
  paciente_id: null,
  cotizacion_id: null,
  toma_muestra: '',
  fecha_reporte: '',
  fecha_validacion: '',
  medico_solicitante: '',
  medico_id: null,
  aplica_iva: false,
  descuento: 0,
  cliente: clienteVacio(),
  estudios: [],
})

// Totales
const totalEstudios = computed(() => form.estudios.reduce((sum, e) => sum + (parseFloat(e.precio) || 0), 0))
const totales = computed(() => calcularTotales(totalEstudios.value, form.descuento, form.aplica_iva))
const montoDescuento = computed(() => totales.value.montoDescuento)
const montoIva = computed(() => totales.value.iva)
const totalConIva = computed(() => totales.value.total)

// Vista previa
const mostrarPreview = ref(false)
const generandoPreview = ref(false)
const previewUrl = ref('')
const documentoPreview = ref('reporte')

const estudiosDisponibles = ref([])
const medicosDisponibles = ref([])
const busquedaEstudio = ref('')
const estudiosFiltrados = ref([])

// T12: médico dado de alta desde la captura
const agregarMedico = (medico) => {
  if (!medicosDisponibles.value.some(m => m.id === medico.id)) medicosDisponibles.value.push(medico)
}

const cargarEstudios = async () => {
  try {
    const { data } = await axios.get('/api/estudios')
    estudiosDisponibles.value = data
  } catch (error) {
    console.error('Error al cargar estudios:', error)
    mostrarNotificacion('No se pudieron cargar los estudios. Recarga la página.', 'error')
  }
}

const cargarMedicos = async () => {
  try {
    const { data } = await axios.get('/api/medicos')
    medicosDisponibles.value = data
  } catch (e) {
    console.error('Error al cargar médicos:', e)
  }
}

onMounted(async () => {
  await Promise.all([cargarEstudios(), cargarMedicos()])
  if (props.cotizacionPrecargada) precargarDesdeCotizacion(props.cotizacionPrecargada)
})

// ===== Paciente (T1) =====
const pacienteParaRango = computed(() => ({ sexo: form.cliente.sexo, edad: form.cliente.edad }))

// Con un paciente registrado, sus datos de identidad no se editan aquí (solo los que falten)
const bloqueado = (campo) => !!pacienteSeleccionado.value && !!pacienteSeleccionado.value[campo]

function seleccionarPaciente(paciente) {
  pacienteSeleccionado.value = paciente
  form.paciente_id = paciente.id
  form.cliente.nombre = paciente.nombre
  form.cliente.email = paciente.email || form.cliente.email
  form.cliente.telefono = paciente.telefono || form.cliente.telefono
  form.cliente.fecha_nacimiento = paciente.fecha_nacimiento ? String(paciente.fecha_nacimiento).slice(0, 10) : ''
  form.cliente.sexo = paciente.sexo || ''
  anteriores.cargar(paciente.id)
}

function limpiarPacienteSeleccionado() {
  pacienteSeleccionado.value = null
  form.paciente_id = null
  form.cliente = clienteVacio()
  anteriores.cargar(null)
  setTimeout(() => document.getElementById('paciente-nombre')?.focus(), 0)
}

watch(() => form.cliente.fecha_nacimiento, (nuevaFecha) => {
  if (!nuevaFecha) {
    form.cliente.edad = ''
    return
  }
  const hoy = new Date()
  const nacimiento = new Date(`${nuevaFecha}T00:00:00`)
  let edad = hoy.getFullYear() - nacimiento.getFullYear()
  const mes = hoy.getMonth() - nacimiento.getMonth()
  if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) edad--
  form.cliente.edad = edad >= 0 ? edad : ''
})

// T4: si cambia el sexo o la edad, el intervalo que aplica puede cambiar
watch(pacienteParaRango, () => {
  form.estudios.forEach(e => e.examenes.forEach(ex => {
    if (!ex.marca_manual) marcarRango(ex, pacienteParaRango.value)
  }))
}, { deep: true })

// T10: TFGe y RAC debajo de su analito; se recalculan al capturar o al cambiar edad/sexo
const renal = computed(() => analizarFuncionRenal(form.estudios, pacienteParaRango.value))

// ===== Estudios =====
const listasDeExamenes = () => form.estudios.map(e => e.examenes)

function nuevoEstudio(estudio, precio) {
  return {
    id: estudio.id,
    nombre: estudio.nombre,
    tipo_muestra: (estudio.tipo_muestra && estudio.tipo_muestra !== 'N/A') ? estudio.tipo_muestra : '',
    metodo: (estudio.metodo && estudio.metodo !== 'N/A') ? estudio.metodo : '',
    // T13: lo de siempre del catálogo y las otras opciones para elegir
    muestra_catalogo: estudio.tipo_muestra || '',
    metodo_catalogo: estudio.metodo || '',
    opciones_muestra: estudio.opciones_muestra || [],
    opciones_metodo: estudio.opciones_metodo || [],
    elaboro: esAdmin.value ? RESPONSABLE : nombreUsuario.value,
    valido: RESPONSABLE,
    precio,
    observaciones: '',
    incluir_en_reporte: true,
    examenes: (estudio.examenes || []).map(e => ({
      id: e.id,
      nombre_examen: e.nombre_examen,
      unidad: e.unidad,
      valor_referencia: e.valor_referencia,
      niveles: e.niveles || [],
      resultado: '',
      fuera_rango: false,
      direccion_rango: null,
      calculado: false,
      formula: '',
      marca_manual: false,
    })),
  }
}

function onEscribir(examen) {
  examen.marca_manual = false
  marcarRango(examen, pacienteParaRango.value)
  recalcularOrden(listasDeExamenes(), pacienteParaRango.value, form.estudios)
}

function precargarDesdeCotizacion(cotizacion) {
  form.aplica_iva = Boolean(cotizacion.aplica_iva)
  form.descuento = parseFloat(cotizacion.descuento) || 0
  form.cotizacion_id = cotizacion.id ?? null

  if (cotizacion.paciente) {
    seleccionarPaciente(cotizacion.paciente)
  } else if (cotizacion.nombre_cliente) {
    form.cliente.nombre = cotizacion.nombre_cliente || ''
    form.cliente.email = cotizacion.email || ''
    form.cliente.telefono = cotizacion.telefono || ''
  }

  (cotizacion.estudios || []).forEach(ce => {
    const estudioCompleto = estudiosDisponibles.value.find(e => e.id === ce.estudio?.id) || ce.estudio
    if (!estudioCompleto || form.estudios.some(e => e.id === estudioCompleto.id)) return
    form.estudios.push(nuevoEstudio(estudioCompleto, parseFloat(ce.precio) || parseFloat(estudioCompleto.precio) || 0))
  })

  recalcularOrden(listasDeExamenes(), pacienteParaRango.value, form.estudios)

  if (form.estudios.length) {
    const origen = cotizacion.origen || `Cotización ${cotizacion.folio}`
    mostrarNotificacion(`${origen} cargada: ${form.estudios.length} estudio(s) y datos del paciente`)
  }
}

function filtrarEstudios() {
  const termino = busquedaEstudio.value.toLowerCase().trim()
  estudiosFiltrados.value = termino === ''
    ? [...estudiosDisponibles.value]
    : estudiosDisponibles.value.filter(e => e.nombre.toLowerCase().includes(termino))
}

function mostrarTodosEstudios() {
  estudiosFiltrados.value = [...estudiosDisponibles.value]
}

function cerrarListaEstudios() {
  setTimeout(() => { estudiosFiltrados.value = [] }, 150)
}

function seleccionarEstudio(estudio) {
  busquedaEstudio.value = ''
  estudiosFiltrados.value = []

  if (form.estudios.some(e => e.id === estudio.id)) {
    mostrarNotificacion('Este estudio ya está en la orden', 'error')
    return
  }

  form.estudios.push(nuevoEstudio(estudio, parseFloat(estudio.precio) || 0))
  recalcularOrden(listasDeExamenes(), pacienteParaRango.value, form.estudios)
  mostrarNotificacion(`${estudio.nombre} agregado`, 'success', 2500)
}

function eliminarEstudio(index) {
  const nombreEstudio = form.estudios[index].nombre
  if (confirm(`¿Quitar "${nombreEstudio}" de la orden?`)) {
    form.estudios.splice(index, 1)
    recalcularOrden(listasDeExamenes(), pacienteParaRango.value, form.estudios)
  }
}

const textoNivel = (nivel) => {
  const n = (v) => (v === null || v === undefined || v === '' ? null : Number(v))
  const desde = n(nivel.desde)
  const hasta = n(nivel.hasta)
  if (desde !== null && hasta !== null) return `${desde} – ${hasta}`
  if (desde !== null) return `≥ ${desde}`
  if (hasta !== null) return `≤ ${hasta}`
  return '—'
}

// ===== Validación y guardado =====
function validarFormulario() {
  const error = (mensaje) => { mostrarNotificacion(mensaje, 'error'); return false }

  if (!form.cliente.nombre?.trim()) return error('Escribe el nombre del paciente')
  if (!form.cliente.fecha_nacimiento) return error('Falta la fecha de nacimiento del paciente')
  if (!form.cliente.sexo) return error('Selecciona el sexo del paciente')
  if (!form.toma_muestra) return error('Falta la fecha de toma de muestra')
  if (!form.fecha_reporte) return error('Falta la fecha de reporte')
  if (esAdmin.value && !form.fecha_validacion) return error('Falta la fecha de validación')
  if (form.estudios.length === 0) return error('Agrega al menos un estudio')

  for (const estudio of form.estudios) {
    if (esAdmin.value && (!estudio.precio || estudio.precio <= 0)) {
      return error(`El estudio "${estudio.nombre}" debe tener un precio válido`)
    }

    const tieneResultados = estudio.examenes.some(ex => ex.resultado && String(ex.resultado).trim() !== '')
    if (estudio.incluir_en_reporte && !tieneResultados) {
      return error(`"${estudio.nombre}" no tiene resultados. Captúralos o apaga «Incluir en reporte» si sale otro día.`)
    }
  }

  return true
}

async function cargarPreview(documento) {
  generandoPreview.value = true
  try {
    const response = await axios.post('/api/reportes/preview', { ...form, documento }, { responseType: 'blob' })
    liberarPreview()
    previewUrl.value = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    documentoPreview.value = documento
    return true
  } catch (error) {
    let mensaje = 'No se pudo generar la vista previa'
    if (error.response?.data instanceof Blob) {
      try {
        const datos = JSON.parse(await error.response.data.text())
        mensaje = datos.errors ? Object.values(datos.errors).flat()[0] : (datos.message || mensaje)
      } catch (e) { /* respuesta que no es JSON */ }
    }
    mostrarNotificacion(mensaje, 'error')
    return false
  } finally {
    generandoPreview.value = false
  }
}

async function previsualizarReporte() {
  if (generandoPreview.value || guardando.value || !validarFormulario()) return
  if (await cargarPreview('reporte')) mostrarPreview.value = true
}

async function cambiarDocumentoPreview(documento) {
  if (documento === documentoPreview.value || generandoPreview.value) return
  await cargarPreview(documento)
}

function liberarPreview() {
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = ''
  }
}

function cerrarPreview() {
  mostrarPreview.value = false
  documentoPreview.value = 'reporte'
  liberarPreview()
}

async function confirmarYGuardar() {
  cerrarPreview()
  await guardarReporte()
}

const cerrarConEscape = (e) => { if (e.key === 'Escape' && mostrarPreview.value) cerrarPreview() }
onMounted(() => window.addEventListener('keydown', cerrarConEscape))
onBeforeUnmount(() => {
  liberarPreview()
  window.removeEventListener('keydown', cerrarConEscape)
  clearTimeout(temporizadorAviso)
})

function limpiarFormulario() {
  form.paciente_id = null
  form.cotizacion_id = null
  form.cliente = clienteVacio()
  form.estudios = []
  form.toma_muestra = ''
  form.fecha_reporte = ''
  form.fecha_validacion = ''
  form.medico_solicitante = ''
  form.medico_id = null
  form.aplica_iva = false
  form.descuento = 0
  pacienteSeleccionado.value = null
  anteriores.cargar(null)
}

async function guardarReporte() {
  if (guardando.value || !validarFormulario()) return
  guardando.value = true

  try {
    const { data } = await axios.post('/api/reportes', form)
    const pendientes = form.estudios.filter(e => !e.incluir_en_reporte).length
    const extra = pendientes ? ` ${pendientes} estudio(s) quedaron pendientes para capturarse después.` : ''

    mostrarNotificacion(
      esAdmin.value
        ? `Reporte ${data.folio} guardado y validado.${extra} Descárgalo desde Lista de reportes.`
        : `Reporte ${data.folio} guardado. Queda pendiente de validar por el Q.F.B.${extra}`,
      'success',
      7000,
    )
    limpiarFormulario()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (error) {
    if (error.response) {
      const errores = error.response.data?.errors
      mostrarNotificacion(errores ? Object.values(errores).flat()[0] : (error.response.data?.message || 'Error al guardar el reporte'), 'error')
    } else {
      mostrarNotificacion('No se pudo conectar con el servidor. Verifica tu conexión.', 'error')
    }
  } finally {
    guardando.value = false
  }
}
</script>
