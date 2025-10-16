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
  
          <div class="container mt-4">
            <h2 class="mb-4">Captura de Resultados</h2>

            <div class="row mb-4">
      <div class="col-md-4">
      <label class="form-label">Toma de muestra</label>
      <input type="datetime-local" class="form-control" v-model="form.toma_muestra" />
    </div>
    <div class="col-md-4">
      <label class="form-label">Fecha de reporte</label>
      <input type="datetime-local" class="form-control" v-model="form.fecha_reporte" />
    </div>
    <div class="col-md-4">
      <label class="form-label">Fecha de validación</label>
      <input type="datetime-local" class="form-control" v-model="form.fecha_validacion" />
    </div>

  <div class="col-md-4">
  <label class="form-label">Médico solicitante</label>
  <input type="text" class="form-control" v-model="form.medico_solicitante" />
</div>

</div>


            <!-- Datos del cliente -->
            <div class="card mb-4">
              <div class="card-header">Datos del Cliente</div>
              <div class="card-body row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nombre</label>
                  <input type="text" class="form-control" v-model="form.cliente.nombre" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" v-model="form.cliente.email" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha de nacimiento</label>
                  <input type="date" class="form-control" v-model="form.cliente.fecha_nacimiento" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Edad</label>
                  <input type="number" class="form-control" v-model="form.cliente.edad" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Sexo</label>
                  <select class="form-select" v-model="form.cliente.sexo">
                    <option value="">Seleccione</option>
                    <option>Masculino</option>
                    <option>Femenino</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Selección de estudios -->
            <div class="card mb-4">
              <div class="card-header">Estudios</div>
              <div class="card-body">
                
                <div class="mb-3">
                  <!-- <label class="form-label">Agregar estudio</label> -->

                  
                                              <!-- 🔍 Autocompletado de estudios -->
              <div class="mb-3 position-relative" style="max-width: 500px;">
                <label class="form-label">Buscar estudio</label>
                <input
                  type="text"
                  class="form-control"
                  placeholder="Escribe el nombre del estudio o haz clic para ver todos..."
                  v-model="busquedaEstudio"
                  @focus="mostrarTodosEstudios"
                  @input="filtrarEstudios"
                />

                <!-- Lista de sugerencias con scroll -->
                <ul
                  v-if="estudiosFiltrados.length > 0"
                  class="list-group position-absolute w-100 mt-1 shadow"
                  style="z-index: 1000; max-height: 180px; overflow-y: auto;"
                >
                  <li
                    v-for="estudio in estudiosFiltrados"
                    :key="estudio.id"
                    class="list-group-item list-group-item-action"
                    @click="seleccionarEstudio(estudio)"
                    style="cursor: pointer;"
                  >
                    <div class="d-flex justify-content-between align-items-center">
                      <span>{{ estudio.nombre }}</span>
                      <small class="text-muted">${{ parseFloat(estudio.precio).toFixed(2) }}</small>
                    </div>
                    
                  </li>
                </ul>
              </div>


                  <button class="btn btn-primary mt-2" @click="agregarEstudio">Agregar</button>
                </div>

                <div v-for="(estudio, index) in form.estudios" :key="index" class="border rounded p-3 mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="m-0">{{ estudio.nombre }}</h5>
                    <button class="btn btn-sm btn-outline-danger" @click="eliminarEstudio(index)">
                      🗑️ Eliminar
                    </button>
                  </div>

                  <div class="row mb-2">
                    <div class="col-md-3">
                      <label class="form-label">Tipo de muestra</label>
                      <input type="text" class="form-control" v-model="estudio.tipo_muestra" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Método</label>
                      <input type="text" class="form-control" v-model="estudio.metodo" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Elaboró</label>
                      <input type="text" class="form-control" v-model="estudio.elaboro" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Validó</label>
                      <input type="text" class="form-control" v-model="estudio.valido" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Precio ($)</label>
                      <input type="number" class="form-control" step="0.01" v-model="estudio.precio" />
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                      <thead>
                        <tr>
                          <th>Examen</th>
                          <th>Unidad</th>
                          <th>Valor referencia</th>
                          <th>Resultado</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(examen, exIndex) in estudio.examenes" :key="exIndex">
                          <td>{{ examen.nombre_examen }}</td>
                          <td>{{ examen.unidad }}</td>
                          <td>{{ examen.valor_referencia }}</td>
                          <td>
                          <input type="text" class="form-control" v-model="examen.resultado" />
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" v-model="examen.fuera_rango" />
                            <label class="form-check-label">F.R.</label>
                          </div>
                        </td>

                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- 💵 Total de la orden -->
            <div class="card">
              <div class="card-body text-end">
                <h5 class="fw-bold">
                  Total: ${{ totalEstudios.toFixed(2) }}
                </h5>
              </div>
            </div>

            <!-- Botón para enviar -->
            <div class="text-end" style="padding-bottom: 200px; margin-top: 10px;">
              <button class="btn btn-success" @click="guardarReporte">Guardar Reporte</button>
            </div>
          </div>

    </AuthenticatedLayout>

</template>

<script setup>
  import { ref, reactive, onMounted, watch } from 'vue'
  import axios from 'axios'
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
  import { computed } from 'vue'
  import { Head } from '@inertiajs/vue3' // ✅ <--- AGREGA ESTA LÍNEA

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
    const response = await axios.get('/api/estudios')
    estudiosDisponibles.value = response.data
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
      tipo_muestra: estudio.tipo_muestra || '', // ← ahora viene del backend
      metodo: estudio.metodo || '',             // ← ahora viene del backend
      elaboro: 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS',
      valido: 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS',
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
  try {
    const response = await axios.post('/api/reportes', form)
    alert('Reporte guardado con éxito')

    const { id, folio } = response.data;

    // Descargar el PDF
    const pdfResponse = await axios.get(`/api/reportes/${id}/pdf`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([pdfResponse.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `reporte-${folio}.pdf`) // ✅ usa el folio real
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

      } catch (error) {
        console.error(error)
        alert('Error al guardar el reporte')
  }
}


  function filtrarEstudios() {
  const termino = busquedaEstudio.value.toLowerCase().trim()
  if (termino === '') {
    // Si no escribió nada, muestra todos
    estudiosFiltrados.value = [...estudiosDisponibles.value]
    return
  }
  estudiosFiltrados.value = estudiosDisponibles.value.filter(e =>
    e.nombre.toLowerCase().includes(termino)
  )
}

  // 🆕 Mostrar todos al hacer foco en el input
  function mostrarTodosEstudios() {
    estudiosFiltrados.value = [...estudiosDisponibles.value]
  }

function seleccionarEstudio(estudio) {
  // Evita duplicados
  if (form.estudios.some(e => e.id === estudio.id)) {
    alert('Este estudio ya fue agregado.')
    busquedaEstudio.value = ''
    estudiosFiltrados.value = []
    return
  }

  form.estudios.push({
    id: estudio.id,
    nombre: estudio.nombre,
    tipo_muestra: estudio.tipo_muestra || '',
    metodo: estudio.metodo || '',
    elaboro: 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS',
    valido: 'Q.F.B ÁNGEL AUGUSTO PÉREZ ARIAS',
    precio: parseFloat(estudio.precio) || 0, // 💰 Precio sugerido editable
    examenes: estudio.examenes.map(e => ({
      id: e.id,
      nombre_examen: e.nombre_examen,
      unidad: e.unidad,
      valor_referencia: e.valor_referencia,
      resultado: '',
      fuera_rango: false,
    })),
  })

  busquedaEstudio.value = ''
  estudiosFiltrados.value = []
}

function eliminarEstudio(index) {
  if (confirm('¿Deseas eliminar este estudio del reporte?')) {
    form.estudios.splice(index, 1)
  }
}



</script>

<style>
.list-group-item:hover {
  background-color: #e9f0ff;
}

.list-group-item small {
  font-size: 11px;
}



</style>