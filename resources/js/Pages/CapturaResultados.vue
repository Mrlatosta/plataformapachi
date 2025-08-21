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
    <input type="date" class="form-control" v-model="form.toma_muestra" />
  </div>
  <div class="col-md-4">
    <label class="form-label">Fecha de reporte</label>
    <input type="date" class="form-control" v-model="form.fecha_reporte" />
  </div>
  <div class="col-md-4">
    <label class="form-label">Fecha de validación</label>
    <input type="date" class="form-control" v-model="form.fecha_validacion" />
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
                  <label class="form-label">Agregar estudio</label>
                  <select class="form-select" v-model="selectedEstudioId">
                    <option value="">Seleccione un estudio</option>
                    <option v-for="estudio in estudiosDisponibles" :key="estudio.id" :value="estudio.id">
                      {{ estudio.nombre }}
                    </option>
                  </select>
                  <button class="btn btn-primary mt-2" @click="agregarEstudio">Agregar</button>
                </div>

                <div v-for="(estudio, index) in form.estudios" :key="index" class="border rounded p-3 mb-4">
                  <h5>{{ estudio.nombre }}</h5>

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

            <!-- Botón para enviar -->
            <div class="text-end">
              <button class="btn btn-success" @click="guardarReporte">Guardar Reporte</button>
            </div>
          </div>

    </AuthenticatedLayout>

</template>

<script setup>
  import { ref, reactive, onMounted } from 'vue'
  import axios from 'axios'
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';


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

  onMounted(async () => {
    const response = await axios.get('/api/estudios')
    estudiosDisponibles.value = response.data
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

      // Descargar el PDF
      const id = response.data.id
      window.open(`/api/reportes/${id}/pdf`, '_blank')
      
      // puedes redireccionar o limpiar el formulario aquí
    } catch (error) {
      console.error(error)
      alert('Error al guardar el reporte')
    }
  }
</script>
